<?php
$menu = 'lapkupon';
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data kupon & pembayaran
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
$kupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Kupon PTS | Panitia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .status-lunas {
            color: green !important;
            font-weight: 600;
        }

        .status-cicilan {
            color: orange !important;
            font-weight: 600;
        }

        .status-belum {
            color: #000 !important;
            font-weight: 600;
        }
    </style>
</head>

<!-- Modal Export -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Data Kupon PTS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 30vh;">
                <iframe src="export_kupon.php" style="width: 100%; height: 100%; border: none;"></iframe>
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
                            <h3 class="mb-0">Laporan Kupon PTS</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Laporan Kupon</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">
                            <button type="button" class="btn btn-outline-info mb-3" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="bi bi-download"></i> Export Data
                            </button>

                            <table class="table table-bordered" id="kuponTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama</th>
                                        <th>Nomor Kupon</th>
                                        <th>Jumlah Kupon</th>
                                        <th>Kembali Kupon</th>
                                        <th>Total Tagihan (Rp)</th>
                                        <th>Total Pembayaran (Rp)</th>
                                        <th>Keterangan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    foreach ($kupons as $row):
                                        if ($row['total_bayar'] >= $row['total_tagihan']) {
                                            $status = "Lunas";
                                            $rowClass = "status-lunas";
                                        } elseif ($row['total_bayar'] > 0) {
                                            $status = "Cicilan";
                                            $rowClass = "status-cicilan";
                                        } else {
                                            $status = "Belum Bayar";
                                            $rowClass = "status-belum";
                                        }
                                    ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><b class="<?= $rowClass ?>"><?= htmlspecialchars($row['nama']) ?></b></td>
                                            <td><?= htmlspecialchars($row['nomor_kupon']) ?></td>
                                            <td><?= (int)$row['jumlah_kupon'] ?></td>
                                            <td><?= (int)$row['kembali_kupon'] ?></td>
                                            <td>Rp <?= number_format($row['total_tagihan'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
                                            <td>
                                                <?php if ($row['total_bayar'] > 0): ?>
                                                    <a target="_blank" href="kupon_detail.php?id=<?= $row['id_kupon'] ?>" class="btn btn-link p-0">Lihat Detail</a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?= $status ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-end">Total Keseluruhan:</th>
                                        <th>
                                            Rp <?= number_format(array_sum(array_column($kupons, 'total_tagihan')), 0, ',', '.') ?>
                                        </th>
                                        <th>
                                            Rp <?= number_format(array_sum(array_column($kupons, 'total_bayar')), 0, ',', '.') ?>
                                        </th>
                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="assets/js/adminlte.js"></script>
    <script src="assets/js/sides.js"></script>


</body>

</html>