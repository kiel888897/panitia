<?php
$menu = 'lappemasukan';
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

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
</head>

<!-- Modal Export -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Data Pemasukan PTS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 30vh;">
                <iframe src="export_pemasukan.php" style="width: 100%; height: 100%; border: none;"></iframe>
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

                            <button type="button" class="btn btn-outline-info mb-3" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="bi bi-download"></i> Export Data
                            </button>

                            <?php
                            // --- TOKTOK & SUKARELA ---
                            $stmt = $pdo->query("
    SELECT 
        COALESCE(SUM(i.toktok), 0) AS total_toktok,
        COALESCE(SUM(i.sukarela), 0) AS total_sukarela,
        COUNT(DISTINCT a.id) AS jumlah_anggota
    FROM anggotas a
    LEFT JOIN iuran i ON a.id = i.anggota_id
");
                            $data = $stmt->fetch(PDO::FETCH_ASSOC);

                            $jumlahAnggota = $data['jumlah_anggota'];
                            $totalToktok = (int)$data['total_toktok'];
                            $totalSukarela = (int)$data['total_sukarela'];

                            $toktokRipe = 250000;
                            $jumlahToktok = $toktokRipe * $jumlahAnggota;
                            $piutangToktok = $jumlahToktok - $totalToktok;


                            // --- BAJU PTS ---
                            $stmt2 = $pdo->query("
    SELECT 
        COALESCE(SUM(oi.qty),0) AS total_qty,
        (COALESCE(SUM(oi.qty),0) * 100000) AS total_pesanan,
        COALESCE(SUM(bb_tot.total_bayar), 0) AS total_bayar
    FROM anggota a
    JOIN order_items oi ON oi.order_id = a.id
    LEFT JOIN (
        SELECT anggota_id, SUM(jumlah) AS total_bayar
        FROM bayar_baju
        GROUP BY anggota_id
    ) bb_tot ON bb_tot.anggota_id = a.id
");
                            $baju = $stmt2->fetch(PDO::FETCH_ASSOC);

                            $totalPesanan = (int)$baju['total_pesanan'];
                            $totalBayar = (int)$baju['total_bayar'];
                            $piutangBaju = $totalPesanan - $totalBayar;


                            // --- OUTPUT TABEL RINGKASAN ---
                            echo "<table class='table table-bordered table-sm align-middle'>
<thead class='table-secondary text-center'>
<tr>
  <th>#</th>
  <th>Item / Uraian</th>
  <th>Jumlah (Rp)</th>
  <th>Pembayaran (Rp)</th>
  <th>Piutang (Rp)</th>
</tr>
</thead>
<tbody>
<tr>
  <td class='text-center'>1</td>
  <td>Toktok Ripe ({$jumlahAnggota} orang)</td>
  <td class='text-end'>" . number_format($jumlahToktok, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($totalToktok, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($piutangToktok, 0, ',', '.') . "</td>
</tr>
<tr>
  <td class='text-center'>2</td>
  <td>Sukarela</td>
  <td class='text-end'>" . number_format($totalSukarela, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($totalSukarela, 0, ',', '.') . "</td>
  <td class='text-end'>-</td>
</tr>
<tr>
  <td class='text-center'>3</td>
  <td>Baju PTS</td>
  <td class='text-end'>" . number_format($totalPesanan, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($totalBayar, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($piutangBaju, 0, ',', '.') . "</td>
</tr>
</tbody>
</table>";
                            ?>

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