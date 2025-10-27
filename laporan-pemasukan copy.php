<?php
$menu = 'lappemasukan';
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}


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
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Pemasukan PTS | Panitia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
    <style>
        .status-lunas {
            color: green;
            font-weight: bold;
        }

        .status-cicilan {
            color: orange;
            font-weight: bold;
        }

        .status-belum {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<!-- Modal Export -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Data Silua PTS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 30vh;">
                <iframe src="export_silua.php" style="width: 100%; height: 100%; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="">
        <?php include 'header.php'; ?>
        <?php include 'sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Laporan Pemasukan PTS</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Laporan Pemasukan PTS</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">

                            <div class="text-center mb-4">
                                <button id="exportExcel" class="btn btn-success me-2">
                                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                                </button>
                                <button id="exportPDF" class="btn btn-danger">
                                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                                </button>
                            </div>

                            <hr class="my-4">

                            <div id="previewArea" class="mt-4">
                                <!-- Tabel preview akan muncul di sini -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include 'footer.php'; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="assets/js/adminlte.js"></script>
    <script src="assets/js/sides.js"></script>

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
                        decoration: 'underline',
                        alignment: 'center'
                    } : {
                        text: '-',
                        color: 'gray',
                        alignment: 'center'
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
                        detailCell,
                        {
                            text: status,
                            alignment: 'center',
                            color: warna
                        }
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
                    }
                ]);

                docContent.push({
                    table: {
                        headerRows: 1,
                        widths: ['5%', '*', '15%', '15%', '10%', '10%'],
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
    <script>
        function renderPreview() {
            const container = document.getElementById("previewArea");
            container.innerHTML = ""; // Kosongkan dulu

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

                // Judul Section
                const header = document.createElement("h5");
                header.textContent = sec.label;
                header.className = "mt-4 mb-2 text-primary";
                container.appendChild(header);

                // Buat tabel
                const table = document.createElement("table");
                table.className = "table table-bordered table-striped table-sm align-middle";
                const thead = document.createElement("thead");
                thead.innerHTML = `
            <tr class="table-secondary text-center">
                <th style="width:5%">#</th>
                <th>Nama</th>
                <th style="width:15%">Silua (Rp)</th>
                <th style="width:15%">Total Bayar (Rp)</th>
                <th style="width:10%">Detail</th>
                <th style="width:10%">Status</th>
            </tr>
        `;
                table.appendChild(thead);

                const tbody = document.createElement("tbody");
                let totalSilua = 0;
                let totalBayar = 0;
                let i = 1;

                list.forEach(row => {
                    const silua = parseInt(row.total_silua) || 0;
                    const bayar = parseInt(row.total_bayar) || 0;
                    totalSilua += silua;
                    totalBayar += bayar;

                    let status = "Belum Bayar";
                    let warna = "text-danger";
                    if (bayar >= silua && silua > 0) {
                        status = "Lunas";
                        warna = "text-success";
                    } else if (bayar > 0 && bayar < silua) {
                        status = "Cicilan";
                        warna = "text-warning";
                    }

                    // Kolom Detail (hanya aktif kalau sudah ada pembayaran)
                    const detailHTML = (bayar > 0) ?
                        `<a href="${baseURL + row.id}" target="_blank" class="btn btn-link p-0 text-decoration-none text-primary">Detail</a>` :
                        `<span class="text-muted">-</span>`;

                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                <td class="text-center">${i++}</td>
                <td>${row.nama}</td>
                <td class="text-end">${fmt(silua)}</td>
                <td class="text-end">${fmt(bayar)}</td>
                <td class="text-center">${detailHTML}</td>
                <td class="text-center fw-bold ${warna}">${status}</td>
            `;
                    tbody.appendChild(tr);
                });

                // Baris total
                const trTotal = document.createElement("tr");
                trTotal.className = "fw-bold";
                trTotal.innerHTML = `
            <td></td>
            <td class="text-center">TOTAL</td>
            <td class="text-end">${fmt(totalSilua)}</td>
            <td class="text-end">${fmt(totalBayar)}</td>
            <td></td>
            <td></td>
        `;
                tbody.appendChild(trTotal);

                table.appendChild(tbody);
                container.appendChild(table);
            });
        }

        // Render langsung saat halaman dimuat
        renderPreview();
    </script>
</body>

</html>