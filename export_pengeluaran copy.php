<?php


require_once 'db.php';

$stmt = $pdo->query("
    SELECT 
        p.id,
        p.tanggal,
        a.nama_role AS seksi,
        p.nama AS uraian,
        p.keterangan,
        p.jumlah,
        p.nota,
        p.bayar
    FROM pengeluaran p
    LEFT JOIN admin_role a ON p.seksi = a.id
    ORDER BY p.tanggal ASC
");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Pengeluaran PTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>

<body class="p-4 text-center">
    <h3 class="mb-3">Laporan Pengeluaran PTS</h3>
    <div class="mb-3">
        <button id="exportExcel" class="btn btn-success me-2"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
        <button id="exportPDF" class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
    </div>

    <script>
        // Safety: gunakan JSON_HEX flags agar aman untuk inline JavaScript
        const data = <?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

        // Base URL otomatis mengikuti domain & path aktif
        const baseURL = "<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])) ?>/uploads/";

        const tanggal = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');

        function toInt(v) {
            const n = parseInt(v);
            return isNaN(n) ? 0 : n;
        }

        // ==========================
        // EXPORT EXCEL
        // ==========================
        document.getElementById("exportExcel").addEventListener("click", function() {
            const wb = XLSX.utils.book_new();
            const wsData = [
                ["Laporan Pengeluaran PTS"],
                ["Tanggal:", new Date().toLocaleDateString('id-ID')],
                [],
                ["No", "Tanggal", "Seksi", "Uraian", "Jumlah (Rp)", "Keterangan", "Nota", "Pembayaran"]
            ];
            let i = 1,
                total = 0;
            data.forEach(r => {
                total += toInt(r.jumlah);
                const notaLink = r.nota ? baseURL + r.nota : '-';
                const bayarLink = r.bayar ? baseURL + r.bayar : '-';
                wsData.push([i++, r.tanggal, r.seksi, r.uraian, toInt(r.jumlah), cleanHTML(r.keterangan) || '-', notaLink, bayarLink]);
            });
            wsData.push([], [, "TOTAL", "", "", total, "", "", ""]);
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            XLSX.utils.book_append_sheet(wb, ws, "Pengeluaran");
            XLSX.writeFile(wb, `laporan_pengeluaran_${tanggal}.xlsx`);
        });

        // ==========================
        // EXPORT PDF - Kelompok per seksi
        // ==========================
        document.getElementById("exportPDF").addEventListener("click", function() {
            // 1️⃣ Kelompokkan data berdasarkan seksi
            const grouped = {};
            data.forEach(r => {
                const seksi = r.seksi || "Tanpa Seksi";
                if (!grouped[seksi]) grouped[seksi] = [];
                grouped[seksi].push(r);
            });

            const content = [{
                    text: "Laporan Pengeluaran PTS",
                    style: "header"
                },
                {
                    text: 'Tanggal: ' + new Date().toLocaleDateString('id-ID'),
                    alignment: 'right',
                    margin: [0, 0, 0, 10],
                    fontSize: 9,
                    italics: true
                }
            ];

            let grandTotal = 0;
            // 2️⃣ Loop setiap seksi
            for (const [seksi, items] of Object.entries(grouped)) {
                const body = [
                    [{
                            text: "#",
                            bold: true
                        },
                        {
                            text: "Tanggal",
                            bold: true
                        },
                        {
                            text: "Uraian",
                            bold: true
                        },
                        {
                            text: "Jumlah (Rp)",
                            bold: true
                        },
                        {
                            text: "Keterangan",
                            bold: true
                        },
                        {
                            text: "Nota",
                            bold: true
                        },
                        {
                            text: "Bayar",
                            bold: true
                        }
                    ]
                ];

                let i = 1,
                    subtotal = 0;

                items.forEach(r => {
                    subtotal += toInt(r.jumlah);
                    grandTotal += toInt(r.jumlah);

                    body.push([{
                            text: i++,
                            alignment: 'center'
                        },
                        {
                            text: r.tanggal || '-',
                            alignment: 'center'
                        },
                        {
                            text: cleanHTML(r.uraian) || '-',
                            alignment: 'left'
                        },
                        {
                            text: 'Rp ' + toInt(r.jumlah).toLocaleString('id-ID'),
                            alignment: 'right'
                        },
                        {
                            text: cleanHTML(r.keterangan) || '-',
                            alignment: 'left'
                        },
                        {
                            text: r.nota ? 'Lihat' : '-',
                            link: r.nota ? baseURL + r.nota : undefined,
                            color: r.nota ? 'blue' : undefined,
                            decoration: r.nota ? 'underline' : undefined,
                            alignment: 'center'
                        },
                        {
                            text: r.bayar ? 'Lihat' : '-',
                            link: r.bayar ? baseURL + r.bayar : undefined,
                            color: r.bayar ? 'blue' : undefined,
                            decoration: r.bayar ? 'underline' : undefined,
                            alignment: 'center'
                        }
                    ]);
                });

                // Tambahkan subtotal seksi
                body.push([{
                        text: "",
                        colSpan: 2
                    }, {},
                    {
                        text: "TOTAL " + seksi,
                        bold: true,
                        alignment: 'right',
                        colSpan: 1
                    },
                    {
                        text: 'Rp ' + subtotal.toLocaleString('id-ID'),
                        bold: true,
                        alignment: 'right'
                    },
                    {}, {}, {}
                ]);

                // Tambahkan ke konten PDF
                content.push({
                    text: `Seksi: ${seksi}`,
                    style: "subheader",
                    alignment: 'left',
                    margin: [0, 10, 0, 4]
                }, {
                    table: {
                        headerRows: 1,
                        widths: ['5%', '15%', '25%', '15%', '20%', '10%', '10%'],
                        body: body
                    },
                    layout: 'lightHorizontalLines'
                });
            }

            // Tambahkan total keseluruhan
            content.push({
                text: `TOTAL KESELURUHAN: Rp ${grandTotal.toLocaleString('id-ID')}`,
                bold: true,
                alignment: 'right',
                margin: [0, 10, 0, 0]
            });

            // 3️⃣ Buat PDF
            const doc = {
                content,
                styles: {
                    header: {
                        fontSize: 14,
                        bold: true,
                        marginBottom: 8
                    },
                    subheader: {
                        fontSize: 11,
                        bold: true
                    }
                },
                defaultStyle: {
                    fontSize: 9,
                    alignment: 'center'
                },
                pageOrientation: 'landscape',
                pageSize: 'A4'
            };

            pdfMake.createPdf(doc).download(`laporan_pengeluaran_${tanggal}.pdf`);
        });
    </script>
    <script>
        function cleanHTML(str) {
            if (!str) return '';
            return str.replace(/<[^>]*>/g, '').trim(); // hapus semua tag HTML
        }
    </script>
</body>

</html>