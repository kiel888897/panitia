<?php
$menu = 'lapkaos';
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
$stmt = $pdo->query("
    SELECT 
        a.id AS anggota_id,
        a.nama,
        a.hp,
        a.alamat,
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
    ORDER BY 
        CASE
            WHEN COALESCE(bb_tot.total_bayar,0) >= (COALESCE(SUM(oi.qty),0) * 100000) THEN 1
            WHEN COALESCE(bb_tot.total_bayar,0) > 0 THEN 2
            ELSE 3
        END,
        a.nama ASC
");

$bajus = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Baju PTS | Panitia</title>
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
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Data Baju PTS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 30vh;">
                <iframe src="export_baju.php" style="width: 100%; height: 100%; border: none;"></iframe>
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
                            <h3 class="mb-0">Laporan Baju PTS</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Laporan Baju PTS</li>
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
                                        <th>Nama</th>
                                        <th>Pesanan</th>
                                        <th>Total Qty</th>
                                        <th>Total Pesanan (Rp)</th>
                                        <th>Total Pembayaran (Rp)</th>
                                        <th>Keterangan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;

                                    $totalQtyAll = 0;
                                    $totalPesananAll = 0;
                                    $totalBayarAll = 0;
                                    $rowClass = ''; // definisikan supaya tidak undefined

                                    foreach ($bajus as $row):
                                        $totalQtyAll += (int)$row['total_qty'];
                                        $totalPesananAll += (int)$row['total_pesanan'];
                                        $totalBayarAll += (int)$row['total_bayar'];

                                        if ($row['total_bayar'] >= $row['total_pesanan']) {
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
                                            <td>
                                                <b class="<?= $rowClass ?>"> <?= htmlspecialchars($row['nama']) ?></b>
                                            </td>

                                            <td>
                                                <?php
                                                // Tambahkan warna abu pada tanda ×
                                                $pesanan = str_replace('@', '<span style="color:#198754;">@</span>', $row['pesanan']);

                                                // Jika kosong, tampilkan tanda "-"
                                                echo $pesanan ? $pesanan : '-';
                                                ?>
                                            </td>
                                            <td><?= (int)$row['total_qty'] ?></td>

                                            <td>Rp <?= number_format($row['total_pesanan'], 0, ',', '.') ?></td>
                                            <!-- Tidak ada kolom harga di order_items; tampilkan '-' atau ubah bila sudah ada harga -->

                                            <td>Rp <?= number_format($row['total_bayar'], 0, ',', '.') ?></td>
                                            <td>
                                                <?php if ($row['total_bayar']  > 0): ?>
                                                    <a target="_blank" href="baju_detail.php?id=<?= $row['anggota_id'] ?>&qty=<?= $row['total_qty'] ?>" class="btn btn-link p-0">Lihat Detail</a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td><strong><?= $status ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end"><b>Total Semua:</b></th>
                                        <th><b><?= $totalQtyAll ?></b></th>
                                        <th><b>Rp <?= number_format($totalPesananAll, 0, ',', '.') ?></b></th>
                                        <th><b>Rp <?= number_format($totalBayarAll, 0, ',', '.') ?></b></th>
                                        <th colspan="2"></th>
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