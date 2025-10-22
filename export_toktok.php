<?php
require_once 'db.php';

// Ambil data
$stmt = $pdo->query("
    SELECT 
        a.id AS anggota_id,
        a.nama,
        COALESCE(SUM(i.toktok), 0) AS total_toktok,
        COALESCE(SUM(i.sukarela), 0) AS total_sukarela
    FROM anggotas a
    LEFT JOIN iuran i ON a.id = i.anggota_id
    GROUP BY a.id, a.nama
    ORDER BY a.nama ASC
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
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

        // ===== EXCEL EXPORT =====
        document.getElementById("exportExcel").addEventListener("click", function() {
            const wb = XLSX.utils.book_new();
            const wsData = [
                ["No", "Nama Anggota", "Toktok Ripe (Rp)", "Sukarela (Rp)", "Total Pembayaran (Rp)", "Keterangan", "Status"]
            ];
            let i = 1;
            let grandToktok = 0;
            let grandSukarela = 0;
            let grandTotal = 0;

            data.forEach(row => {
                const totalToktok = parseInt(row.total_toktok);
                const totalSukarela = parseInt(row.total_sukarela);
                const status = totalToktok >= toktokRipe ? "Lunas" : (totalToktok > 0 ? "Cicilan" : "Belum Bayar");
                const ket = totalToktok > 0 ? `Detail Pembayaran (${baseURL + row.anggota_id})` : "-";

                wsData.push([i++, row.nama, toktokRipe, totalSukarela, totalToktok, ket, status]);

                grandToktok += toktokRipe;
                grandSukarela += totalSukarela;
                grandTotal += totalToktok;
            });

            // Tambahkan baris total
            wsData.push([]);
            wsData.push([
                "", "TOTAL", grandToktok, grandSukarela, grandTotal, "", ""
            ]);

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            XLSX.utils.book_append_sheet(wb, ws, "Laporan Toktok");

            // Auto width kolom
            const colWidths = wsData[0].map((_, idx) => ({
                wch: Math.max(...wsData.map(row => String(row[idx] || "").length)) + 2
            }));
            ws['!cols'] = colWidths;

            const today = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');
            XLSX.writeFile(wb, `laporan_tok-tok-ripe_${today}.xlsx`);
        });


        // ===== PDF EXPORT =====
        document.getElementById("exportPDF").addEventListener("click", function() {
            const bodyData = [
                [{
                        text: '#',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Nama Anggota',
                        bold: true,
                        alignment: 'left'
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
                ]
            ];

            let i = 1;
            let grandToktok = 0;
            let grandSukarela = 0;
            let grandTotal = 0;

            data.forEach(row => {
                const totalToktok = parseInt(row.total_toktok);
                const totalSukarela = parseInt(row.total_sukarela);

                let status = "";
                let warna = "black"; // default warna teks
                if (totalToktok >= toktokRipe) {
                    status = "Lunas";
                    warna = "green";
                } else if (totalToktok > 0) {
                    status = "Cicilan";
                    warna = "orange";
                } else {
                    status = "Belum Bayar";
                    warna = "black";
                }

                const linkCell = totalToktok > 0 ?
                    {
                        text: 'Detail Pembayaran',
                        link: baseURL + row.anggota_id,
                        color: 'blue',
                        decoration: 'underline'
                    } :
                    {
                        text: '-',
                        color: 'gray'
                    };

                bodyData.push([{
                        text: i++,
                        alignment: 'center'
                    },
                    {
                        text: row.nama,
                        alignment: 'left',
                        color: warna
                    }, // nama berwarna sesuai status
                    {
                        text: toktokRipe.toLocaleString('id-ID'),
                        alignment: 'center'
                    },
                    {
                        text: totalSukarela.toLocaleString('id-ID'),
                        alignment: 'center'
                    },
                    {
                        text: totalToktok.toLocaleString('id-ID'),
                        alignment: 'center'
                    },
                    linkCell,
                    {
                        text: status,
                        alignment: 'center',
                        color: warna
                    } // status berwarna juga
                ]);

                grandToktok += toktokRipe;
                grandSukarela += totalSukarela;
                grandTotal += totalToktok;
            });


            // Tambahkan baris total
            bodyData.push([{
                    text: '',
                    border: [false, false, false, false]
                },
                {
                    text: 'TOTAL',
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: grandToktok.toLocaleString('id-ID'),
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: grandSukarela.toLocaleString('id-ID'),
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: grandTotal.toLocaleString('id-ID'),
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
            pdfMake.createPdf(docDefinition).download(`laporan_tok-tok-ripe_${today}.pdf`);
        });
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</body>

</html>