<?php
require_once 'db.php';

$stmt = $pdo->prepare("SELECT * FROM sumbangan WHERE jenis = 'produk' ORDER BY tanggal DESC");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Export Data Sumbangan Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>

<body class="p-4 text-center">
    <h3 class="mb-3">Export Data Sumbangan Produk</h3>
    <button id="exportExcel" class="btn btn-success me-2">
        <i class="bi bi-file-earmark-excel"></i> Export Excel
    </button>
    <button id="exportPDF" class="btn btn-danger">
        <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </button>

    <script>
        const data = <?php echo json_encode($data); ?>;
        const uploadPath = "<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])) ?>/uploads/";

        // ===== EXCEL EXPORT =====
        document.getElementById("exportExcel").addEventListener("click", function() {
            const wb = XLSX.utils.book_new();
            const wsData = [
                ["No", "Nama", "Keterangan", "Foto", "Tanggal"]
            ];
            let i = 1;

            data.forEach(row => {
                const cleanKet = row.keterangan ? row.keterangan.replace(/<[^>]*>?/gm, '').trim() : "";
                const foto = row.photo ? (uploadPath + row.photo) : "-";
                const tanggal = new Date(row.tanggal).toLocaleDateString('id-ID');
                wsData.push([i++, row.nama, cleanKet, foto, tanggal]);
            });

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            XLSX.utils.book_append_sheet(wb, ws, "Sumbangan Produk");

            XLSX.writeFile(wb, "laporan_sumbangan_produk.xlsx");
        });

        // ===== PDF EXPORT =====
        document.getElementById("exportPDF").addEventListener("click", function() {
            const bodyData = [
                [{
                        text: 'No',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Nama',
                        bold: true,
                        alignment: 'left'
                    },
                    {
                        text: 'Keterangan',
                        bold: true,
                        alignment: 'left'
                    },
                    {
                        text: 'Foto',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Tanggal',
                        bold: true,
                        alignment: 'center'
                    }
                ]
            ];

            let i = 1;
            data.forEach(row => {
                const cleanKet = row.keterangan ? row.keterangan.replace(/<[^>]*>?/gm, '').trim() : "";
                const foto = row.photo ? {
                    text: row.photo,
                    link: uploadPath + row.photo,
                    color: 'blue',
                    decoration: 'underline',
                    alignment: 'left'
                } : {
                    text: '-',
                    color: 'gray'
                };

                const tanggal = new Date(row.tanggal).toLocaleDateString('id-ID');

                bodyData.push([{
                        text: i++,
                        alignment: 'center'
                    },
                    {
                        text: row.nama,
                        alignment: 'left'
                    },
                    {
                        text: cleanKet,
                        alignment: 'left'
                    },
                    foto,
                    {
                        text: tanggal,
                        alignment: 'center'
                    }
                ]);
            });

            const docDefinition = {
                content: [{
                        text: 'Laporan Sumbangan Produk',
                        style: 'header'
                    },
                    {
                        table: {
                            headerRows: 1,
                            widths: ['5%', '20%', '35%', '20%', '20%'],
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

            pdfMake.createPdf(docDefinition).download('laporan_sumbangan_produk.pdf');
        });
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</body>

</html>