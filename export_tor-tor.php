<?php
require_once 'db.php';

// Ambil semua data sumbangan tor-tor
$stmt = $pdo->query("
    SELECT id, nama, jumlah, keterangan, photo, tanggal
    FROM sumbangan
    WHERE jenis = 'tor-tor'
    ORDER BY tanggal DESC
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data ekspor (bersihkan keterangan & buat url photo jika ada)
$export = [];
$hostBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
foreach ($rows as $r) {
    // Hapus tag HTML dan decode entitas (&nbsp;)
    $keterangan = trim(html_entity_decode(strip_tags($r['keterangan'] ?? '')));

    // photo url jika file ada
    $photo_url = null;
    if (!empty($r['photo']) && file_exists(__DIR__ . '/uploads/' . $r['photo'])) {
        $photo_url = $hostBase . 'uploads/' . rawurlencode($r['photo']);
    }

    $export[] = [
        'id' => $r['id'],
        'nama' => $r['nama'],
        'jumlah' => (int)$r['jumlah'],
        'keterangan' => $keterangan,
        'photo' => $r['photo'] ?? '',
        'photo_url' => $photo_url,
        'tanggal' => $r['tanggal']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Export Tor-tor | Panitia PTS</title>

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
    <h3 class="mb-3">Export Data Tor-tor</h3>
    <div class="mb-3">
        <button id="exportExcel" class="btn btn-success me-2"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
        <button id="exportPDF" class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
    </div>

    <script>
        // Data yang sudah diproses di PHP (keterangan bersih & photo_url bila ada)
        const data = <?php echo json_encode($export, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const tanggal = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');

        // HELPERS
        function formatRp(n) {
            return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID');
        }

        function safeDate(d) {
            const dt = new Date(d);
            if (isNaN(dt)) return '';
            return dt.toLocaleDateString('id-ID');
        }

        // EXCEL
        document.getElementById('exportExcel').addEventListener('click', () => {
            if (!data.length) {
                alert('Tidak ada data untuk diekspor.');
                return;
            }

            // Bangun array untuk XLSX (AOA)
            const aoa = [];
            aoa.push(['Laporan Tor-tor']);
            aoa.push(['Tanggal:', new Date().toLocaleDateString('id-ID')]);
            aoa.push([]);
            aoa.push(['No', 'Nama', 'Jumlah (Rp)', 'Keterangan', 'Link Bukti', 'Tanggal']);

            let totalJumlah = 0;
            let i = 1;
            data.forEach(row => {
                const jumlah = Number(row.jumlah) || 0;
                totalJumlah += jumlah;

                // photo link as full URL or '-' if none
                const photoText = row.photo_url ? row.photo_url : '-';

                aoa.push([
                    i++,
                    row.nama || '-',
                    jumlah,
                    row.keterangan || '-',
                    photoText,
                    safeDate(row.tanggal)
                ]);
            });

            aoa.push([]);
            aoa.push(['', 'TOTAL', totalJumlah, '', '', '']);

            // create workbook
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(aoa);

            // format columns width
            ws['!cols'] = [{
                wch: 5
            }, {
                wch: 30
            }, {
                wch: 18
            }, {
                wch: 50
            }, {
                wch: 40
            }, {
                wch: 14
            }];

            // set number format for the "Jumlah" column (index 2)
            const range = XLSX.utils.decode_range(ws['!ref']);
            for (let R = 4; R <= range.e.r; ++R) {
                // column C (idx 2)
                const cell = ws[XLSX.utils.encode_cell({
                    c: 2,
                    r: R
                })];
                if (cell && typeof cell.v === 'number') cell.z = '#,##0';
                // also format total row (if exists)
            }

            XLSX.utils.book_append_sheet(wb, ws, 'Tor-tor');
            XLSX.writeFile(wb, `laporan_tor_tor_${tanggal}.xlsx`);
        });

        // PDF
        document.getElementById('exportPDF').addEventListener('click', () => {
            if (!data.length) {
                alert('Tidak ada data untuk diekspor.');
                return;
            }

            // table header
            const body = [];
            body.push([{
                    text: 'No',
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Nama',
                    bold: true
                },
                {
                    text: 'Jumlah (Rp)',
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Keterangan',
                    bold: true
                },
                {
                    text: 'Bukti',
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Tanggal',
                    bold: true,
                    alignment: 'center'
                }
            ]);

            let totalJumlah = 0;
            let i = 1;
            data.forEach(row => {
                const jumlah = Number(row.jumlah) || 0;
                totalJumlah += jumlah;

                // bukti: clickable link if available
                const buktiCell = row.photo_url ? {
                    text: 'Lihat',
                    link: row.photo_url,
                    target: '_blank',
                    color: 'blue',
                    decoration: 'underline',
                    alignment: 'center'
                } : {
                    text: '-',
                    alignment: 'center',
                    color: 'gray'
                };

                body.push([{
                        text: i++,
                        alignment: 'center'
                    },
                    {
                        text: row.nama || '-',
                        alignment: 'left'
                    },
                    {
                        text: formatRp(jumlah),
                        alignment: 'right'
                    },
                    {
                        text: row.keterangan || '-',
                        alignment: 'left'
                    },
                    buktiCell,
                    {
                        text: safeDate(row.tanggal),
                        alignment: 'center'
                    }
                ]);
            });

            // total row
            body.push([{
                    text: 'Total',
                    colSpan: 2,
                    bold: true,
                    alignment: 'center'
                }, {},
                {
                    text: formatRp(totalJumlah),
                    colSpan: 4,
                    bold: true,
                    alignment: 'left'
                }, {}, {}, {}
            ]);

            const docDefinition = {
                pageOrientation: 'portrait',
                pageSize: 'A4',
                content: [{
                        text: 'LAPORAN TOR-TOR',
                        style: 'header'
                    },
                    {
                        text: 'Tanggal: ' + new Date().toLocaleDateString('id-ID'),
                        alignment: 'right',
                        margin: [0, 0, 0, 10],
                        italics: true,
                        fontSize: 10
                    },
                    {
                        table: {
                            headerRows: 1,
                            widths: ['4%', '20%', '15%', '*', '8%', '16%'],
                            body: body
                        },
                        layout: 'lightHorizontalLines'
                    }
                ],
                styles: {
                    header: {
                        fontSize: 14,
                        bold: true,
                        alignment: 'center',
                        marginBottom: 6
                    }
                },
                defaultStyle: {
                    fontSize: 10
                }
            };

            pdfMake.createPdf(docDefinition).download(`laporan_tor_tor_${tanggal}.pdf`);
        });
    </script>
</body>

</html>