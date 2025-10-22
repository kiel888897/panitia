<?php
$menu = 'sumbangan-produk';
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM sumbangan WHERE jenis = 'produk' ORDER BY tanggal DESC");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Sumbangan Produk | Panitia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="">
        <?php include 'header.php'; ?>
        <?php include 'sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Laporan Sumbangan Produk</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Laporan Sumbangan Produk</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">

                            <?php if (empty($data)): ?>
                                <div class="alert alert-info">Belum ada data sumbangan produk.</div>
                            <?php else: ?>
                                <div class="mb-3 text-end">
                                    <button id="btnExcel" class="btn btn-success btn-sm me-2">
                                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                                    </button>
                                    <button id="btnPDF" class="btn btn-danger btn-sm">
                                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table id="lapTable" class="table table-bordered table-striped align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama</th>
                                                <th>Keterangan</th>
                                                <th>Foto</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1;
                                            foreach ($data as $row): ?>
                                                <tr>
                                                    <td><?= $no++; ?></td>
                                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                                    <td><?= $row['keterangan']; ?></td>
                                                    <td>
                                                        <?php if (!empty($row['photo']) && file_exists("uploads/{$row['photo']}")): ?>
                                                            <a href="<?= 'uploads/' . htmlspecialchars($row['photo']); ?>" target="_blank">
                                                                <?= htmlspecialchars($row['photo']); ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">Tidak ada</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>

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
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/build/pdfmake.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.7/build/vfs_fonts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-buttons@2.4.2/js/buttons.print.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
        integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ="
        crossorigin="anonymous"></script>
    <script src="assets/js/adminlte.js"></script>
    <script src="assets/js/sides.js"></script>

    <script>
        $(function() {
            const table = $('#lapTable').DataTable({
                pageLength: 25,
                lengthChange: false,
                searching: true,
                ordering: true,
                order: [
                    [4, 'desc']
                ],
                language: {
                    search: "Cari:",
                    paginate: {
                        previous: "Prev",
                        next: "Next"
                    },
                    emptyTable: "Tidak ada data untuk ditampilkan"
                },
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'excelHtml5',
                        title: 'Laporan Sumbangan Produk',
                        text: 'Export ke Excel',
                        className: 'd-none',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        }
                    },
                    {
                        extend: 'pdfHtml5',
                        title: 'Laporan Sumbangan Produk',
                        text: 'Export ke PDF',
                        className: 'd-none',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4]
                        },
                        customize: function(doc) {
                            // Style umum
                            doc.content[0].alignment = 'center';
                            doc.styles.tableHeader.alignment = 'center';
                            doc.styles.tableHeader.fontSize = 11;
                            doc.defaultStyle.fontSize = 10;
                            doc.pageMargins = [40, 40, 40, 40];

                            // Set lebar kolom tabel
                            doc.content[1].table.widths = ['5%', '25%', '30%', '25%', '15%'];

                            var body = doc.content[1].table.body;

                            for (var i = 1; i < body.length; i++) {
                                // Ambil cell kolom Foto
                                var cellNode = table.cell(i - 1, 3).node();
                                var cellHTML = $(cellNode).html().trim();
                                var cellText = $(cellNode).text().trim();

                                var href = '';
                                var text = '';

                                // Kalau ada tag <a>, ambil href-nya
                                if ($(cellNode).find('a').length) {
                                    href = $(cellNode).find('a').attr('href');
                                    text = $(cellNode).find('a').text().trim();
                                }
                                // Kalau tidak ada <a> tapi teksnya kelihatan seperti link
                                else if (cellText.match(/\.(jpg|jpeg|png|gif)$/i)) {
                                    href = cellText;
                                    text = cellText;
                                }

                                // Masukkan ke PDF
                                if (href) {
                                    body[i][3] = {
                                        text: text,
                                        link: href.startsWith('http') ? href : window.location.origin + '/' + href,
                                        color: 'blue',
                                        decoration: 'underline'
                                    };
                                } else {
                                    body[i][3] = {
                                        text: 'Tidak ada',
                                        color: '#555'
                                    };
                                }

                                // Alignment per kolom
                                body[i][0].alignment = 'center';
                                body[i][1].alignment = 'left';
                                body[i][2].alignment = 'left';
                                body[i][3].alignment = 'left';
                                body[i][4].alignment = 'center';
                                body[i].forEach(cell => cell.margin = [2, 4, 2, 4]); // padding cell
                            }

                            // Tambah style header tabel
                            doc.styles.tableHeader.fillColor = '#2d3e50';
                            doc.styles.tableHeader.color = 'white';
                        }


                    }
                ]
            });

            $('#btnExcel').on('click', function() {
                table.button('.buttons-excel').trigger();
            });
            $('#btnPDF').on('click', function() {
                table.button('.buttons-pdf').trigger();
            });
        });
    </script>
</body>

</html>