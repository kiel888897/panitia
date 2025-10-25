<?php
require_once 'db.php';

// Ambil data silua + total pembayaran
$stmt = $pdo->query("
    SELECT 
        a.id,
        a.nama,
        a.jumlah AS total_silua,
        a.keterangan,
        COALESCE(SUM(b.jumlah), 0) AS total_bayar
    FROM silua a
    LEFT JOIN bayar_silua b ON a.id = b.silua_id
    GROUP BY a.id, a.nama, a.jumlah, a.keterangan
    ORDER BY a.keterangan, a.nama ASC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan per kategori
$kelompok = [
    'hula' => [],
    'boru' => [],
    'bere' => []
];

foreach ($rows as $r) {
    $k = strtolower(trim($r['keterangan']));
    if ($k === 'hula') {
        $kelompok['hula'][] = $r;
    } elseif ($k === 'boru') {
        $kelompok['boru'][] = $r;
    } elseif ($k === 'bere') {
        $kelompok['bere'][] = $r;
    } else {
        // Jika ada nilai keterangan lain, bisa dimasukkan ke salah satu atau diabaikan.
        // Untuk sekarang, kita abaikan.
    }
}

// Siapkan data JSON untuk JS
$jsonData = json_encode($kelompok);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Export Data Silua - Laporan Dana Silua</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }
    </style>
</head>

<body class="p-4">
    <div class="container">
        <h3 class="mb-3 text-center">Export Data Silua</h3>

        <div class="text-center mb-4">
            <button id="exportExcel" class="btn btn-success me-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </button>
            <button id="exportPDF" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <p class="mb-0"><strong>Informasi:</strong> Tombol export akan menghasilkan file Excel (3 sheets: Hula-hula / Boru / Bere & Ibebere) dan file PDF (satu file berisi ketiga tabel).</p>
            </div>
        </div>
    </div>

    <script>
        // Data dari PHP
        const kelompok = <?php echo $jsonData; ?>;

        // Base URL untuk link detail: silua_detail.php?id=
        const baseURL = "<?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])) ?>/silua_detail.php?id=";

        // Helper: format number to IDR string
        const fmt = (n) => {
            return Number(n).toLocaleString('id-ID');
        };

        // ===== EXCEL EXPORT (3 sheet) =====
        document.getElementById("exportExcel").addEventListener("click", function() {
            const wb = XLSX.utils.book_new();

            const sheetsOrder = [{
                    key: 'hula',
                    title: 'Hula-hula'
                },
                {
                    key: 'boru',
                    title: 'Boru'
                },
                {
                    key: 'bere',
                    title: 'Bere & Ibebere'
                }
            ];

            const todaySlug = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');

            sheetsOrder.forEach(section => {
                const list = kelompok[section.key] || [];
                const wsData = [];
                // Header
                wsData.push(["No", "Nama", "Silua (Rp)", "Total Bayar (Rp)", "Keterangan", "Status", "Detail Link"]);

                let i = 1;
                let totalSilua = 0;
                let totalBayar = 0;

                list.forEach(row => {
                    const silua = parseInt(row.total_silua) || 0;
                    const bayar = parseInt(row.total_bayar) || 0;
                    totalSilua += silua;
                    totalBayar += bayar;

                    let status = "Belum Bayar";
                    if (bayar >= silua && silua > 0) status = "Lunas";
                    else if (bayar > 0 && bayar < silua) status = "Cicilan";

                    const link = bayar > 0 ? (baseURL + row.id) : "-";

                    wsData.push([i++, row.nama, silua, bayar, row.keterangan, status, link]);
                });

                // Tambah baris total
                wsData.push([]);
                wsData.push(["", "TOTAL", totalSilua, totalBayar, "", "", ""]);

                const ws = XLSX.utils.aoa_to_sheet(wsData);

                // Auto width kolom
                const colWidths = wsData[0].map((_, idx) => ({
                    wch: Math.max(...wsData.map(r => String(r[idx] || "").length)) + 2
                }));
                ws['!cols'] = colWidths;

                XLSX.utils.book_append_sheet(wb, ws, section.title);
            });

            XLSX.writeFile(wb, `laporan_dana_silua_${todaySlug}.xlsx`);
        });

        // ===== PDF EXPORT =====
        document.getElementById("exportPDF").addEventListener("click", function() {
            // pdfMake body: setiap section akan punya header tabel sendiri
            const docContent = [];
            const title = "Laporan Dana Silua";

            docContent.push({
                text: title,
                style: 'title',
                margin: [0, 0, 0, 8]
            });
            docContent.push({
                text: "Tanggal: " + new Date().toLocaleDateString('id-ID'),
                style: 'date',
                alignment: 'right',
                margin: [0, 0, 0, 10]
            });

            const sections = [{
                    key: 'hula',
                    label: 'Hula-hula'
                },
                {
                    key: 'boru',
                    label: 'Boru'
                },
                {
                    key: 'bere',
                    label: 'Bere & Ibebere'
                }
            ];

            sections.forEach(sec => {
                const list = kelompok[sec.key] || [];

                // Section title
                docContent.push({
                    text: sec.label,
                    style: 'sectionHeader',
                    margin: [0, 6, 0, 6]
                });

                // Table header
                const body = [];
                body.push([{
                        text: '#',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Nama',
                        bold: true,
                        alignment: 'left'
                    },
                    {
                        text: 'Silua (Rp)',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Total Bayar (Rp)',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Keterangan',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Status',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Detail',
                        bold: true,
                        alignment: 'center'
                    }
                ]);

                let i = 1;
                let totalSilua = 0;
                let totalBayar = 0;

                list.forEach(row => {
                    const silua = parseInt(row.total_silua) || 0;
                    const bayar = parseInt(row.total_bayar) || 0;
                    totalSilua += silua;
                    totalBayar += bayar;

                    let status = "Belum Bayar";
                    let warna = "black";
                    if (bayar >= silua && silua > 0) {
                        status = "Lunas";
                        warna = "green";
                    } else if (bayar > 0 && bayar < silua) {
                        status = "Cicilan";
                        warna = "orange";
                    }

                    const detailCell = bayar > 0 ? {
                        text: 'Detail',
                        link: baseURL + row.id,
                        color: 'blue',
                        decoration: 'underline'
                    } : {
                        text: '-',
                        color: 'gray'
                    };

                    body.push([{
                            text: i++,
                            alignment: 'center'
                        },
                        {
                            text: row.nama,
                            alignment: 'left',
                            color: warna
                        },
                        {
                            text: fmt(silua),
                            alignment: 'center'
                        },
                        {
                            text: fmt(bayar),
                            alignment: 'center'
                        },
                        {
                            text: row.keterangan,
                            alignment: 'center'
                        },
                        {
                            text: status,
                            alignment: 'center',
                            color: warna
                        },
                        detailCell
                    ]);
                });

                // Baris total
                body.push([{
                        text: '',
                        border: [false, false, false, false]
                    },
                    {
                        text: 'TOTAL',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: fmt(totalSilua),
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: fmt(totalBayar),
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: '',
                        border: [false, false, false, false]
                    },
                    {
                        text: '',
                        border: [false, false, false, false]
                    },
                    {
                        text: '',
                        border: [false, false, false, false]
                    }
                ]);

                docContent.push({
                    table: {
                        headerRows: 1,
                        widths: ['5%', '30%', '15%', '15%', '15%', '10%', '10%'],
                        body: body
                    },
                    layout: 'lightHorizontalLines',
                    margin: [0, 0, 0, 6]
                });
            });

            const docDefinition = {
                content: docContent,
                styles: {
                    title: {
                        fontSize: 16,
                        bold: true,
                        alignment: 'center'
                    },
                    date: {
                        fontSize: 10,
                        italics: true
                    },
                    sectionHeader: {
                        fontSize: 13,
                        bold: true,
                        margin: [0, 6, 0, 6]
                    }
                },
                defaultStyle: {
                    fontSize: 10
                },
                pageOrientation: 'landscape',
                pageSize: 'A4'
            };

            const today = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');
            pdfMake.createPdf(docDefinition).download(`laporan_dana_silua_${today}.pdf`);
        });
    </script>
</body>

</html>