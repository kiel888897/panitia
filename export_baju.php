<?php
require_once 'db.php';

// Ambil data (pakai query yang meng-aggregate bayar_baju dulu supaya tidak tergandakan)
$stmt = $pdo->query("
    SELECT 
        a.id AS anggota_id,
        a.nama,
        COALESCE(SUM(oi.qty),0) AS total_qty,
        (COALESCE(SUM(oi.qty),0) * 100000) AS total_pesanan,
        GROUP_CONCAT(CONCAT(oi.qty,oi.size) SEPARATOR ', ') AS pesanan,
        COALESCE(bb_tot.total_bayar, 0) AS total_bayar
    FROM anggota a
    JOIN order_items oi ON oi.order_id = a.id
    LEFT JOIN (
        SELECT anggota_id, SUM(jumlah) AS total_bayar
        FROM bayar_baju
        GROUP BY anggota_id
    ) bb_tot ON bb_tot.anggota_id = a.id
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Libraries (pastikan versi tersedia) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }
    </style>
</head>

<body class="p-4 text-center">
    <h3 class="mb-3">Export Data Baju PTS</h3>
    <div class="mb-3">
        <button id="exportExcel" class="btn btn-success me-2"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
        <button id="exportPDF" class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
    </div>

    <script>
        // Safety: gunakan JSON_SAFE flags agar string aman untuk JS
        const data = <?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
        const baseURL = "<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])) ?>/baju_detail.php?id=";
        const tanggal = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');

        // Quick debug: kalau export tidak jalan, lihat console (F12)
        console.log("Data count:", data.length);
        // contoh check beberapa item
        if (data.length) console.log("Sample:", data[0]);

        // helper: safe parse int
        function toInt(v) {
            const n = parseInt(v);
            return isNaN(n) ? 0 : n;
        }

        // ===== EXCEL EXPORT =====
        document.getElementById("exportExcel").addEventListener("click", function() {
            try {
                if (typeof XLSX === 'undefined') {
                    console.error("XLSX not loaded");
                    alert("Library XLSX belum ter-load. Coba refresh halaman.");
                    return;
                }

                const wb = XLSX.utils.book_new();

                // Header + tanggal baris
                const headerTitle = ["Laporan Baju PTS"];
                const tanggalRow = ["Tanggal:", new Date().toLocaleDateString('id-ID')];
                const cols = ["No", "Nama Anggota", "Pesanan", "Total Qty", "Total Pesanan (Rp)", "Total Pembayaran (Rp)", "Keterangan", "Status"];

                const wsData = [];
                wsData.push(headerTitle);
                wsData.push(tanggalRow);
                wsData.push([]);
                wsData.push(cols);

                let i = 1;
                let totalQty = 0,
                    totalPesananAll = 0,
                    totalBayarAll = 0;

                data.forEach(row => {
                    const totalPesanan = toInt(row.total_pesanan);
                    const totalBayar = toInt(row.total_bayar);
                    const totalQtyRow = toInt(row.total_qty);
                    const status = (totalBayar >= totalPesanan && totalPesanan > 0) ? "Lunas" : (totalBayar > 0 ? "Cicilan" : "Belum Bayar");
                    const ket = totalBayar > 0 ? `Detail Pembayaran (${baseURL + row.anggota_id})` : "-";

                    wsData.push([
                        i++,
                        row.nama || "-",
                        row.pesanan || "-",
                        totalQtyRow,
                        totalPesanan,
                        totalBayar,
                        ket,
                        status
                    ]);

                    totalQty += totalQtyRow;
                    totalPesananAll += totalPesanan;
                    totalBayarAll += totalBayar;
                });

                // total row and empty spacer
                wsData.push([]);
                wsData.push(["", "TOTAL", "", totalQty, totalPesananAll, totalBayarAll, "", ""]);

                const ws = XLSX.utils.aoa_to_sheet(wsData);

                // Format rupiah columns: set number format for columns E and F (0-indexed: 4 and 5)
                // We'll set cell z = '#,##0' for basic thousands separator — Excel will treat as number
                const range = XLSX.utils.decode_range(ws['!ref']);
                for (let R = 4; R <= range.e.r; ++R) { // start from data rows (index 4 = header rows before)
                    // col E (idx 4)
                    const cellE = ws[XLSX.utils.encode_cell({
                        c: 4,
                        r: R
                    })];
                    if (cellE && typeof cellE.v === 'number') cellE.z = '#,##0';
                    // col F (idx 5)
                    const cellF = ws[XLSX.utils.encode_cell({
                        c: 5,
                        r: R
                    })];
                    if (cellF && typeof cellF.v === 'number') cellF.z = '#,##0';
                }

                // Auto width columns
                const colWidths = wsData[3].map((_, idx) => ({
                    wch: Math.max(...wsData.map(row => String(row[idx] || "").length)) + 2
                }));
                ws['!cols'] = colWidths;

                XLSX.utils.book_append_sheet(wb, ws, "Baju PTS");

                const filename = `laporan_baju_pts_${tanggal}.xlsx`;
                XLSX.writeFile(wb, filename);
                console.log("Excel saved:", filename);
            } catch (err) {
                console.error("Export Excel error:", err);
                alert("Terjadi kesalahan saat export Excel. Cek console untuk detail.");
            }
        });

        // ===== PDF EXPORT =====
        document.getElementById("exportPDF").addEventListener("click", function() {
            try {
                if (typeof pdfMake === 'undefined') {
                    console.error("pdfMake not loaded");
                    alert("Library pdfMake belum ter-load. Coba refresh halaman.");
                    return;
                }

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

                let i = 1,
                    totalQty = 0,
                    totalPesananAll = 0,
                    totalBayarAll = 0;

                data.forEach(row => {
                    const totalPesanan = toInt(row.total_pesanan);
                    const totalBayar = toInt(row.total_bayar);
                    const totalQtyRow = toInt(row.total_qty);

                    let status = "";
                    let warna = "black"; // default
                    if (totalBayar >= totalPesanan && totalPesanan > 0) {
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
                            text: row.nama || '-',
                            alignment: 'left',
                            color: warna
                        }, // warna nama sesuai status
                        {
                            text: row.pesanan || '-',
                            alignment: 'left'
                        },
                        {
                            text: totalQtyRow,
                            alignment: 'center'
                        },
                        {
                            text: 'Rp ' + totalPesanan.toLocaleString('id-ID'),
                            alignment: 'center'
                        },
                        {
                            text: 'Rp ' + totalBayar.toLocaleString('id-ID'),
                            alignment: 'center'
                        },
                        linkCell,
                        {
                            text: status,
                            alignment: 'center',
                            color: warna
                        } // warna juga di kolom status
                    ]);

                    totalQty += totalQtyRow;
                    totalPesananAll += totalPesanan;
                    totalBayarAll += totalBayar;
                });

                // total row
                bodyData.push([{
                        text: '',
                        colSpan: 2
                    }, {},
                    {
                        text: 'TOTAL',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: totalQty,
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Rp ' + totalPesananAll.toLocaleString('id-ID'),
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Rp ' + totalBayarAll.toLocaleString('id-ID'),
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: '',
                        colSpan: 2
                    }, {}
                ]);

                const docDefinition = {
                    content: [{
                            text: `Laporan Baju PTS`,
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
                                widths: ['5%', '20%', '25%', '8%', '12%', '12%', '10%', '8%'],
                                body: bodyData
                            },
                            layout: 'lightHorizontalLines'
                        }
                    ],
                    styles: {
                        header: {
                            fontSize: 14,
                            bold: true,
                            marginBottom: 8
                        }
                    },
                    defaultStyle: {
                        fontSize: 9,
                        alignment: 'center'
                    },
                    pageOrientation: 'landscape',
                    pageSize: 'A4'
                };

                const filename = `laporan_baju_pts_${tanggal}.pdf`;
                pdfMake.createPdf(docDefinition).download(filename);
                console.log("PDF saved:", filename);
            } catch (err) {
                console.error("Export PDF error:", err);
                alert("Terjadi kesalahan saat export PDF. Cek console untuk detail.");
            }
        });
    </script>
</body>

</html>