<?php
// =======================================
// export_pengeluaran.php
// =======================================

require_once 'db.php';

// Ambil parameter seksi (optional)
$filterSeksi = isset($_GET['seksi']) ? (int) $_GET['seksi'] : null;

if ($filterSeksi) {
    $stmt = $pdo->prepare("
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
        WHERE p.seksi = :seksi
        ORDER BY p.tanggal ASC
    ");
    $stmt->execute(['seksi' => $filterSeksi]);
} else {
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
        ORDER BY a.nama_role, p.tanggal ASC
    ");
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Judul laporan
$judulLaporan = 'Laporan Pengeluaran Bona Taon 2026';
if ($filterSeksi) {
    $judulLaporan .= ' - Seksi ' . ($data[0]['seksi'] ?? 'Tidak Dikenal');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($judulLaporan) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>

<body class="p-4 text-center">
    <h3 class="mb-3"><?= htmlspecialchars($judulLaporan) ?></h3>
    <div class="mb-3">
        <button id="exportExcel" class="btn btn-success me-2">
            <i class="bi bi-file-earmark-excel"></i> Export Excel
        </button>
        <button id="exportPDF" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
    </div>

    <script>
        // Data dari PHP (aman untuk inline JS)
        const data = <?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
        const baseURL = "<?= htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])) ?>/uploads/";
        const tanggal = new Date().toLocaleDateString('id-ID').replace(/\//g, '-');
        const isFiltered = <?= $filterSeksi ? 'true' : 'false' ?>;
        const judulLaporan = <?= json_encode($judulLaporan) ?>;

        function toInt(v) {
            const n = parseInt(v);
            return isNaN(n) ? 0 : n;
        }

        function cleanHTML(str) {
            if (!str) return '';
            return str.replace(/<[^>]*>/g, '').trim();
        }

        // ==========================
        // EXPORT EXCEL
        // ==========================
        document.getElementById("exportExcel").addEventListener("click", function() {
            const wb = XLSX.utils.book_new();

            if (isFiltered) {
                // === satu seksi ===
                const wsData = [
                    [judulLaporan],
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
                    wsData.push([
                        i++, r.tanggal, r.seksi, cleanHTML(r.uraian),
                        toInt(r.jumlah), cleanHTML(r.keterangan), notaLink, bayarLink
                    ]);
                });
                wsData.push([], ["", "", "", "TOTAL", total, "", "", ""]);
                const ws = XLSX.utils.aoa_to_sheet(wsData);
                XLSX.utils.book_append_sheet(wb, ws, "Pengeluaran");
            } else {
                // === multi-seksi ===
                const grouped = {};
                data.forEach(r => {
                    const seksi = r.seksi || "Tanpa Seksi";
                    if (!grouped[seksi]) grouped[seksi] = [];
                    grouped[seksi].push(r);
                });

                for (const [seksi, items] of Object.entries(grouped)) {
                    const wsData = [
                        ["Laporan Pengeluaran Bona Taon 2026 - " + seksi],
                        ["Tanggal:", new Date().toLocaleDateString('id-ID')],
                        [],
                        ["No", "Tanggal", "Uraian", "Jumlah (Rp)", "Keterangan", "Nota", "Bayar"]
                    ];
                    let i = 1,
                        total = 0;
                    items.forEach(r => {
                        total += toInt(r.jumlah);
                        const notaLink = r.nota ? baseURL + r.nota : '-';
                        const bayarLink = r.bayar ? baseURL + r.bayar : '-';
                        wsData.push([
                            i++, r.tanggal, cleanHTML(r.uraian),
                            toInt(r.jumlah), cleanHTML(r.keterangan), notaLink, bayarLink
                        ]);
                    });
                    wsData.push([], ["", "", "TOTAL", total, "", "", ""]);
                    const ws = XLSX.utils.aoa_to_sheet(wsData);
                    XLSX.utils.book_append_sheet(wb, ws, seksi.substring(0, 31));
                }
            }

            const namaFileExcel = isFiltered ?
                `laporan_pengeluaran_${data[0]?.seksi || 'seksi_tidak_dikenal'}_${tanggal}.xlsx` :
                `laporan_pengeluaran_semua_seksi_${tanggal}.xlsx`;
            XLSX.writeFile(wb, namaFileExcel);

        });

        // ==========================
        // EXPORT PDF
        // ==========================
        document.getElementById("exportPDF").addEventListener("click", function() {
            const content = [];
            let grandTotal = 0; // <=== untuk total keseluruhan
            const tanggalCetak = new Date().toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
            if (isFiltered) {
                // === satu seksi ===
                content.push({
                    text: judulLaporan,
                    style: "header"
                });
                content.push({
                    text: 'Tanggal: ' + tanggalCetak,
                    alignment: 'right',
                    margin: [0, 0, 0, 10],
                    italics: true,
                    fontSize: 9
                });
                addTableToPDF(content, data);
            } else {
                // === multi-seksi ===
                const grouped = {};
                data.forEach(r => {
                    const seksi = r.seksi || "Tanpa Seksi";
                    if (!grouped[seksi]) grouped[seksi] = [];
                    grouped[seksi].push(r);
                });

                content.push({
                    text: "Laporan Pengeluaran Bona Taon 2026",
                    style: "header"
                });

                content.push({
                    text: 'Tanggal: ' + tanggalCetak,
                    alignment: 'right',
                    margin: [0, 0, 0, 10],
                    italics: true,
                    fontSize: 9
                });
                for (const [seksi, items] of Object.entries(grouped)) {
                    content.push({
                        text: `\nSeksi: ${seksi}`,
                        bold: true,
                        alignment: 'left',
                        margin: [0, 5, 0, 3]
                    });
                    const subtotal = addTableToPDF(content, items);
                    grandTotal += subtotal; // tambahkan subtotal ke total keseluruhan
                }

                // === Tambahkan total keseluruhan di bawah semua seksi ===
                content.push({
                    text: "\nTOTAL KESELURUHAN",
                    bold: true,
                    fontSize: 11,
                    alignment: 'center',
                    margin: [0, 10, 0, 3]
                });
                content.push({
                    text: 'Rp ' + grandTotal.toLocaleString('id-ID'),
                    bold: true,
                    fontSize: 12,
                    alignment: 'center',
                    color: 'green'
                });
            }

            const doc = {
                content: content,
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

            const namaFilePDF = isFiltered ?
                `laporan_pengeluaran_${data[0]?.seksi || 'seksi_tidak_dikenal'}_${tanggal}.pdf` :
                `laporan_pengeluaran_semua_seksi_${tanggal}.pdf`;
            pdfMake.createPdf(doc).download(namaFilePDF);

        });

        // ===== Helper: Tambahkan tabel PDF =====
        function addTableToPDF(content, items) {
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
                total = 0;

            items.forEach(r => {
                total += toInt(r.jumlah);
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

            body.push([{
                    text: "",
                    colSpan: 2
                }, {},
                {
                    text: "TOTAL",
                    bold: true,
                    alignment: 'center'
                },
                {
                    text: 'Rp ' + total.toLocaleString('id-ID'),
                    bold: true,
                    alignment: 'right'
                },
                {}, {}, {}
            ]);

            content.push({
                table: {
                    headerRows: 1,
                    widths: ['2%', '10%', '25%', '15%', '*', '5%', '5%'],
                    body: body
                },
                layout: 'lightHorizontalLines'
            });

            return total; // <=== kembalikan total untuk ditambahkan ke grandTotal
        }
    </script>
</body>

</html>