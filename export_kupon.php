<?php
require_once 'db.php';

// Ambil data kupon + pembayaran
$stmt = $pdo->query("
    SELECT 
        k.id AS id_kupon,
        k.nama,
        k.nomor_kupon,
        k.jumlah AS jumlah_kupon,
        k.kembali AS kembali_kupon,
        (k.jumlah - k.kembali) * 50000 AS total_tagihan,
        COALESCE(SUM(bk.bayar), 0) AS total_bayar
    FROM kupon k
    LEFT JOIN bayar_kupon bk ON k.id = bk.id_kupon
    GROUP BY k.id, k.nama, k.nomor_kupon, k.jumlah, k.kembali
    ORDER BY k.nama ASC
");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Export Data Kupon</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- JS Libraries -->
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
    <h3 class="mb-3">Export Data Kupon</h3>
    <div class="mb-3">
        <button id="exportExcel" class="btn btn-success me-2"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
        <button id="exportPDF" class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
    </div>

    <script>
        const data = <?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
        const tanggal = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');

        function toInt(v) {
            const n = parseInt(v);
            return isNaN(n) ? 0 : n;
        }

        // ===== EXPORT EXCEL =====
        document.getElementById("exportExcel").addEventListener("click", function() {
            try {
                if (typeof XLSX === 'undefined') {
                    alert("Library XLSX belum termuat.");
                    return;
                }

                const wb = XLSX.utils.book_new();
                const wsData = [];

                wsData.push(["Laporan Kupon Bajar PTS"]);
                wsData.push(["Tanggal:", new Date().toLocaleDateString('id-ID')]);
                wsData.push([]);
                wsData.push(["No", "Nama", "Nomor Kupon", "Jumlah", "Kembali", "Total Tagihan (Rp)", "Total Bayar (Rp)", "Status"]);

                let i = 1,
                    totalJumlah = 0,
                    totalKembali = 0,
                    totalTagihan = 0,
                    totalBayar = 0;

                data.forEach(row => {
                    const jumlah = toInt(row.jumlah_kupon);
                    const kembali = toInt(row.kembali_kupon);
                    const tagihan = toInt(row.total_tagihan);
                    const bayar = toInt(row.total_bayar);

                    let status = "Belum Bayar";
                    if (bayar >= tagihan && tagihan > 0) status = "Lunas";
                    else if (bayar > 0) status = "Cicilan";

                    wsData.push([
                        i++,
                        row.nama,
                        row.nomor_kupon,
                        jumlah,
                        kembali,
                        tagihan,
                        bayar,
                        status
                    ]);

                    totalJumlah += jumlah;
                    totalKembali += kembali;
                    totalTagihan += tagihan;
                    totalBayar += bayar;
                });

                wsData.push([]);
                wsData.push(["", "TOTAL", "", totalJumlah, totalKembali, totalTagihan, totalBayar, ""]);

                const ws = XLSX.utils.aoa_to_sheet(wsData);
                ws['!cols'] = wsData[3].map((_, i) => ({
                    wch: Math.max(...wsData.map(r => (r[i] ? String(r[i]).length : 0))) + 2
                }));

                XLSX.utils.book_append_sheet(wb, ws, "Kupon");
                const filename = `laporan_kupon_${tanggal}.xlsx`;
                XLSX.writeFile(wb, filename);
            } catch (err) {
                console.error("Excel export error:", err);
                alert("Terjadi kesalahan saat export Excel.");
            }
        });

        // ===== EXPORT PDF =====
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
                        text: 'Nomor Kupon',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Jumlah',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Kembali',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Total Tagihan (Rp)',
                        bold: true,
                        alignment: 'center'
                    },
                    {
                        text: 'Total Bayar (Rp)',
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
                totalJumlah = 0,
                totalKembali = 0,
                totalTagihan = 0,
                totalBayar = 0;

            data.forEach(row => {
                const jumlah = toInt(row.jumlah_kupon);
                const kembali = toInt(row.kembali_kupon);
                const tagihan = toInt(row.total_tagihan);
                const bayar = toInt(row.total_bayar);

                let status = "Belum Bayar";
                let warna = "black";
                if (bayar >= tagihan && tagihan > 0) {
                    status = "Lunas";
                    warna = "green";
                } else if (bayar > 0) {
                    status = "Cicilan";
                    warna = "orange";
                }

                bodyData.push([{
                        text: i++,
                        alignment: 'center'
                    },
                    {
                        text: row.nama,
                        alignment: 'left',
                        color: warna
                    },
                    {
                        text: row.nomor_kupon,
                        alignment: 'center'
                    },
                    {
                        text: jumlah,
                        alignment: 'center'
                    },
                    {
                        text: kembali,
                        alignment: 'center'
                    },
                    {
                        text: 'Rp ' + tagihan.toLocaleString('id-ID'),
                        alignment: 'center'
                    },
                    {
                        text: 'Rp ' + bayar.toLocaleString('id-ID'),
                        alignment: 'center'
                    },
                    {
                        text: status,
                        alignment: 'center',
                        color: warna
                    }
                ]);

                totalJumlah += jumlah;
                totalKembali += kembali;
                totalTagihan += tagihan;
                totalBayar += bayar;
            });

            // Baris total
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
                    text: totalJumlah,
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: totalKembali,
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Rp ' + totalTagihan.toLocaleString('id-ID'),
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Rp ' + totalBayar.toLocaleString('id-ID'),
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: '',
                    colSpan: 1
                }
            ]);

            const docDefinition = {
                content: [{
                        text: 'Laporan Kupon Bajar PTS',
                        style: 'header'
                    },
                    {
                        text: 'Tanggal: ' + new Date().toLocaleDateString('id-ID'),
                        alignment: 'right',
                        italics: true,
                        margin: [0, 0, 0, 10],
                        fontSize: 10
                    },
                    {
                        table: {
                            headerRows: 1,
                            widths: ['5%', '20%', '15%', '10%', '10%', '15%', '15%', '10%'],
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

            const filename = `laporan_kupon_${tanggal}.pdf`;
            pdfMake.createPdf(docDefinition).download(filename);
        });
    </script>
</body>

</html>