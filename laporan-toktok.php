<?php
$menu = 'laptoktok';
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil seluruh anggota, serta total iuran dan sukarela mereka
$stmt = $pdo->query("
    SELECT 
        a.id AS anggota_id,
        a.nama,
        a.jabatan,
        COALESCE(SUM(i.toktok), 0) AS total_toktok,
        COALESCE(SUM(i.sukarela), 0) AS total_sukarela
    FROM anggotas a
    LEFT JOIN iuran i ON a.id = i.anggota_id
    GROUP BY a.id, a.nama, a.jabatan
    ORDER BY a.nama ASC
");
$anggotaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Tok-tok Ripe | Panitia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<!-- Modal Export -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Data Toktok
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 30vh;">
                <iframe src="export_toktok.php" style="width: 100%; height: 100%; border: none;"></iframe>
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
                            <h3 class="mb-0">Laporan Tok-tok Ripe</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Laporan Tok-tok Ripe</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">
                            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="bi bi-download"></i> Export Data
                            </button>

                            <!-- <a href="toktok_add.php" class="btn btn-success mb-3"><i class="bi bi-plus-circle"></i> Tambah Pembayaran</a> -->
                            <table class="table table-bordered" id="toktokTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Anggota</th>
                                        <th>Toktok Ripe (Rp)</th>
                                        <th>Sukarela (Rp)</th>
                                        <th>Total Pembayaran (Rp)</th>
                                        <th>Keterangan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    foreach ($anggotaList as $row):
                                        $toktokRipe = 250000;
                                        $totalToktok = (int)$row['total_toktok'];
                                        $totalSukarela = (int)$row['total_sukarela'];

                                        // Tentukan status
                                        if ($totalToktok >= $toktokRipe) {
                                            $status = "Lunas";
                                            $rowClass = "status-lunas";
                                        } elseif ($totalToktok > 0 && $totalToktok < $toktokRipe) {
                                            $status = "Cicilan";
                                            $rowClass = "status-cicilan";
                                        } else {
                                            $status = "Belum Bayar";
                                            $rowClass = "status-belum";
                                        }
                                    ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td>
                                                <b class="<?= $rowClass ?>"><?= htmlspecialchars($row['nama']) ?></b>
                                            </td>
                                            <td><?= number_format($toktokRipe, 0, ',', '.') ?></td>
                                            <td><?= number_format($totalSukarela, 0, ',', '.') ?></td>
                                            <td><?= number_format($totalToktok, 0, ',', '.') ?></td>
                                            <td>
                                                <?php if ($totalToktok > 0): ?>
                                                    <a target="_blank" href="toktok_detail.php?id=<?= $row['anggota_id'] ?>" class="btn btn-link p-0">Detail Pembayaran</a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td><strong><?= $status ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
</body>

</html>