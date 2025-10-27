<?php
require_once 'db.php';

// Ambil data lengkap dengan jabatan
$stmt = $pdo->query("
    SELECT 
        a.id AS anggota_id,
        a.nama,
        a.jabatan,
        COALESCE(SUM(i.toktok), 0) AS total_toktok,
        COALESCE(SUM(i.sukarela), 0) AS total_sukarela
    FROM anggotas a
    LEFT JOIN iuran i ON a.id = i.anggota_id
    GROUP BY a.id, a.nama, a.jabatan
    ORDER BY FIELD(a.jabatan, 'hula', 'boru', 'bere'), a.nama
");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Export Data Toktok</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>

<body class="p-4 text-center">
    <h3 class="mb-3">Export Data Toktok Ripe</h3>
    <button id="exportExcel" class="btn btn-success me-2">
        <i class="bi bi-file-earmark-excel"></i> Export Excel
    </button>
    <button id="exportPDF" class="btn btn-danger">
        <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </button>

    <script>
        const data = <?php echo json_encode($data); ?>;
        const baseURL = "<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])) ?>/toktok_detail.php?id=";
        const toktokRipe = 250000;

        const orders = {
            hula: 'Hula-hula',
            boru: 'Boru',
            bere: 'Bere & Ibebere'
        };

        const grouped = {
            hula: [],
            boru: [],
            bere: []
        };
        data.forEach(r => {
            const key = r.jabatan ? r.jabatan.toLowerCase() : 'lainnya';
            if (grouped[key]) grouped[key].push(r);
        });

        // ========== EXCEL EXPORT ==========
        document.getElementById("exportExcel").addEventListener("click", function() {
            const wb = XLSX.utils.book_new();
            const wsData = [
                ["No", "Nama Anggota", "Toktok Ripe (Rp)", "Sukarela (Rp)", "Total Pembayaran (Rp)", "Keterangan", "Status"]
            ];

            let i = 1;
            let grandToktok = 0,
                grandSukarela = 0,
                grandTotal = 0;

            for (const key in orders) {
                const rows = grouped[key];
                if (!rows.length) continue;

                wsData.push([]);
                wsData.push([orders[key]]); // Header kelompok

                let subTok = 0,
                    subSuk = 0,
                    subTot = 0;

                rows.forEach(r => {
                    const totalToktok = parseInt(r.total_toktok);
                    const totalSukarela = parseInt(r.total_sukarela);
                    const totalBayar = totalToktok + totalSukarela;

                    const status = totalBayar >= toktokRipe ? "Lunas" : (totalBayar > 0 ? "Cicilan" : "Belum Bayar");
                    const ket = totalBayar > 0 ? `Detail Pembayaran (${baseURL + r.anggota_id})` : "-";

                    wsData.push([i++, r.nama, toktokRipe, totalSukarela, totalBayar, ket, status]);

                    subTok += toktokRipe;
                    subSuk += totalSukarela;
                    subTot += totalBayar;
                });

                wsData.push(["", `SUBTOTAL ${orders[key]}`, subTok, subSuk, subTot, "", ""]);
                grandToktok += subTok;
                grandSukarela += subSuk;
                grandTotal += subTot;
            }

            // TOTAL AKHIR
            wsData.push([]);
            wsData.push(["", "TOTAL SELURUHNYA", grandToktok, grandSukarela, grandTotal, "", ""]);

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            XLSX.utils.book_append_sheet(wb, ws, "Laporan Toktok");

            // Auto-width kolom
            const colWidths = wsData[0].map((_, idx) => ({
                wch: Math.max(...wsData.map(row => String(row[idx] || "").length)) + 2
            }));
            ws['!cols'] = colWidths;

            const today = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');
            XLSX.writeFile(wb, `laporan_toktok_ripe_${today}.xlsx`);
        });


        // ========== PDF EXPORT ==========
        document.getElementById("exportPDF").addEventListener("click", function() {
            const bodyData = [];

            // HEADER KOLUMNYA MUNCUL SEKALI DI AWAL
            const headerRow = [{
                    text: '#',
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Nama Anggota',
                    bold: true
                },
                {
                    text: 'Toktok Ripe (Rp)',
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Sukarela (Rp)',
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Total Pembayaran (Rp)',
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
                }
            ];
            bodyData.push(headerRow);

            let i = 1;
            let grandToktok = 0,
                grandSukarela = 0,
                grandTotal = 0;

            for (const key in orders) {
                const rows = grouped[key];
                if (!rows.length) continue;

                // Header kelompok
                bodyData.push([{
                        text: orders[key],
                        colSpan: 7,
                        bold: true,
                        fillColor: '#eeeeee',
                        alignment: 'left'
                    },
                    {}, {}, {}, {}, {}, {}
                ]);

                let subTok = 0,
                    subSuk = 0,
                    subTot = 0;

                rows.forEach(r => {
                    const totalToktok = parseInt(r.total_toktok);
                    const totalSukarela = parseInt(r.total_sukarela);
                    const totalBayar = totalToktok + totalSukarela;

                    let status = "",
                        warna = "black";
                    if (totalBayar >= toktokRipe) {
                        status = "Lunas";
                        warna = "green";
                    } else if (totalBayar > 0) {
                        status = "Cicilan";
                        warna = "orange";
                    } else {
                        status = "Belum Bayar";
                        warna = "black";
                    }

                    const linkCell = totalBayar > 0 ? {
                        text: 'Detail Pembayaran',
                        link: baseURL + r.anggota_id,
                        color: 'blue',
                        decoration: 'underline'
                    } : {
                        text: '-',
                        color: 'gray'
                    };

                    bodyData.push([{
                            text: i++,
                            alignment: 'center'
                        },
                        {
                            text: r.nama,
                            alignment: 'left'
                        },
                        {
                            text: toktokRipe.toLocaleString('id-ID'),
                            alignment: 'right'
                        },
                        {
                            text: totalSukarela.toLocaleString('id-ID'),
                            alignment: 'right'
                        },
                        {
                            text: totalBayar.toLocaleString('id-ID'),
                            alignment: 'right'
                        },
                        linkCell,
                        {
                            text: status,
                            color: warna,
                            alignment: 'center'
                        }
                    ]);

                    subTok += toktokRipe;
                    subSuk += totalSukarela;
                    subTot += totalBayar;
                });

                // SUBTOTAL
                bodyData.push([{
                        text: '',
                        border: [false, false, false, false]
                    },
                    {
                        text: `SUBTOTAL ${orders[key]}`,
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: subTok.toLocaleString('id-ID'),
                        bold: true,
                        alignment: 'right'
                    },
                    {
                        text: subSuk.toLocaleString('id-ID'),
                        bold: true,
                        alignment: 'right'
                    },
                    {
                        text: subTot.toLocaleString('id-ID'),
                        bold: true,
                        alignment: 'right'
                    },
                    {}, {}
                ]);

                grandToktok += subTok;
                grandSukarela += subSuk;
                grandTotal += subTot;
            }

            // TOTAL AKHIR
            bodyData.push([{
                    text: '',
                },
                {
                    text: 'TOTAL SELURUHNYA',
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Rp ' + grandToktok.toLocaleString('id-ID'),
                    bold: true,
                    alignment: 'right'
                },
                {
                    text: 'Rp ' + grandSukarela.toLocaleString('id-ID'),
                    bold: true,
                    alignment: 'right'
                },
                {
                    text: 'Rp ' + grandTotal.toLocaleString('id-ID'),
                    bold: true,
                    alignment: 'right'
                },
                {
                    text: '',
                },
                {
                    text: '',
                }
            ]);


            const docDefinition = {
                content: [{
                        text: 'Laporan Toktok Ripe',
                        style: 'header'
                    },
                    {
                        text: 'Tanggal: ' + new Date().toLocaleDateString('id-ID'),
                        margin: [0, 0, 0, 10],
                        alignment: 'right',
                        italics: true,
                        fontSize: 10
                    },
                    {
                        table: {
                            headerRows: 1,
                            widths: ['5%', '25%', '15%', '15%', '15%', '15%', '10%'],
                            body: bodyData
                        },
                        layout: 'lightHorizontalLines'
                    }
                ],
                styles: {
                    header: {
                        fontSize: 16,
                        bold: true,
                        marginBottom: 10
                    }
                },
                defaultStyle: {
                    fontSize: 10,
                    alignment: 'center'
                },
                pageOrientation: 'landscape',
                pageSize: 'A4'
            };

            const today = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');
            pdfMake.createPdf(docDefinition).download(`laporan_toktok_ripe_${today}.pdf`);
        });
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</body>

</html>