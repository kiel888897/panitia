<?php
require_once 'db.php';

// Ambil data
$stmt = $pdo->query("
    SELECT 
        a.id AS anggota_id,
        a.nama,
        SUM(oi.qty) AS total_qty,
        (SUM(oi.qty) * 100000) AS total_pesanan,
        GROUP_CONCAT(CONCAT(oi.size, ' ×', oi.qty) SEPARATOR ', ') AS pesanan,
        COALESCE(SUM(bb.jumlah), 0) AS total_bayar
    FROM anggota a
    JOIN order_items oi ON oi.order_id = a.id
    LEFT JOIN bayar_baju bb ON bb.anggota_id = a.id
    GROUP BY a.id
    ORDER BY a.nama ASC
");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Export Data Baju PTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body class="p-4 text-center">
    <h3 class="mb-3">Export Data Baju PTS</h3>
    <button id="exportExcel" class="btn btn-success me-2">
        <i class="bi bi-file-earmark-excel"></i> Export Excel
    </button>
    <button id="exportPDF" class="btn btn-danger">
        <i class="bi bi-file-earmark-pdf"></i> Export PDF
    </button>

    <script>
        const data = <?php echo json_encode($data); ?>;
        const baseURL = "<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])) ?>/baju_detail.php?id=";

        // ===== EXCEL EXPORT =====
        document.getElementById("exportExcel").addEventListener("click", function() {
            const wb = XLSX.utils.book_new();
            const wsData = [
                ["No", "Nama Anggota", "Pesanan", "Total Qty", "Total Pesanan (Rp)", "Total Pembayaran (Rp)", "Keterangan", "Status"]
            ];
            let i = 1;

            data.forEach(row => {
                const totalPesanan = parseInt(row.total_pesanan) || 0;
                const totalBayar = parseInt(row.total_bayar) || 0;
                const status = totalBayar >= totalPesanan && totalPesanan > 0 ? "Lunas" :
                    totalBayar > 0 ? "Cicilan" : "Belum Bayar";
                const ket = totalBayar > 0 ? `Detail Pembayaran (${baseURL + row.anggota_id})` : "-";

                wsData.push([
                    i++,
                    row.nama,
                    row.pesanan || "-",
                    row.total_qty || 0,
                    totalPesanan,
                    totalBayar,
                    ket,
                    status
                ]);
            });

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            XLSX.utils.book_append_sheet(wb, ws, "Laporan Baju PTS");

            // Auto width kolom
            const colWidths = wsData[0].map((_, idx) => ({
                wch: Math.max(...wsData.map(row => String(row[idx] || "").length)) + 2
            }));
            ws['!cols'] = colWidths;

            XLSX.writeFile(wb, "laporan_baju_pts.xlsx");
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
                        text: 'Pesanan',
                        bold: true,
                        alignment: 'left'
                    },
                    {
                        text: 'Total Qty',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Total Pesanan (Rp)',
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
            data.forEach(row => {
                const totalPesanan = parseInt(row.total_pesanan) || 0;
                const totalBayar = parseInt(row.total_bayar) || 0;
                const status = totalBayar >= totalPesanan && totalPesanan > 0 ? "Lunas" :
                    totalBayar > 0 ? "Cicilan" : "Belum Bayar";

                const linkCell = totalBayar > 0 ? {
                    text: 'Detail Pembayaran',
                    link: baseURL + row.anggota_id,
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
                        text: row.nama,
                        alignment: 'left'
                    },
                    {
                        text: row.pesanan || '-',
                        alignment: 'left'
                    },
                    {
                        text: row.total_qty || 0,
                        alignment: 'center'
                    },
                    {
                        text: totalPesanan.toLocaleString('id-ID'),
                        alignment: 'center'
                    },
                    {
                        text: totalBayar.toLocaleString('id-ID'),
                        alignment: 'center'
                    },
                    linkCell,
                    {
                        text: status,
                        alignment: 'center'
                    }
                ]);
            });

            const docDefinition = {
                content: [{
                        text: 'Laporan Baju PTS',
                        style: 'header'
                    },
                    {
                        table: {
                            headerRows: 1,
                            widths: ['5%', '20%', '25%', '8%', '12%', '12%', '10%', '8%'],
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
                    fontSize: 9,
                    alignment: 'center'
                },
                pageOrientation: 'landscape',
                pageSize: 'A4'
            };

            pdfMake.createPdf(docDefinition).download('laporan_baju_pts.pdf');
        });
    </script>
</body>

</html>