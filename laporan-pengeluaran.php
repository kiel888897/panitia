<?php
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// --- Mapping seksi ---
$mappingSeksi = [
    'bendahara' => 2,
    'acara' => 3,
    'dana' => 4,
    'perlengkapan' => 5,
    'konsumsi' => 6
];

$seksiNamaMap = [
    'bendahara' => 'Bendahara',
    'acara' => 'Acara',
    'dana' => 'Dana',
    'konsumsi' => 'Konsumsi',
    'perlengkapan' => 'Perlengkapan'
];

// --- Ambil parameter ---
$filter = $_GET['seksi'] ?? null;
$filterSeksi = null;
$namaSeksi = null;
$menu = 'lappengeluaran'; // default

if ($filter && isset($mappingSeksi[strtolower($filter)])) {
    $filterSeksi = $mappingSeksi[strtolower($filter)];
    $menu = 'lap' . strtolower($filter);
    $namaSeksi = $seksiNamaMap[strtolower($filter)];
}

// --- Query berdasarkan seksi (jika ada) ---
if ($filterSeksi) {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.tanggal,
            p.seksi,
            a.nama_role AS seksi_nama,
            p.nama,
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
    // tampilkan semua
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.tanggal,
            p.seksi,
            a.nama_role AS seksi_nama,
            p.nama,
            p.keterangan,
            p.jumlah,
            p.nota,
            p.bayar
        FROM pengeluaran p
        LEFT JOIN admin_role a ON p.seksi = a.id
        ORDER BY p.tanggal ASC
    ");
}

$pengeluaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total
$total = 0;
foreach ($pengeluaran as $row) {
    $total += $row['jumlah'];
}
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Pengeluaran PTS | Panitia</title>
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
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Data Pengeluaran PTS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 30vh;">
                <?php
                $iframeSrc = 'export_pengeluaran.php';
                if (!empty($filterSeksi)) {
                    $iframeSrc .= '?seksi=' . urlencode($filterSeksi);
                }
                ?>
                <iframe src="<?= htmlspecialchars($iframeSrc) ?>" style="width:100%;height:100%;border:none;"></iframe>
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
                            <h3 class="mb-0">
                                <?= $filterSeksi
                                    ? 'Laporan Pengeluaran PTS - Seksi ' . htmlspecialchars($namaSeksi)
                                    : 'Laporan Pengeluaran PTS'; ?>
                            </h3>
                        </div>

                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">
                                    <?= $filterSeksi
                                        ? 'Laporan ' . htmlspecialchars($namaSeksi)
                                        : 'Laporan Pengeluaran PTS'; ?>
                                </li>
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

                            <table class="table table-bordered table-striped" id="pengeluaranTable">
                                <thead class="table-light text-center align-middle">
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Seksi</th>
                                        <th>Uraian</th>
                                        <th>Jumlah (Rp)</th>
                                        <th>Keterangan</th>
                                        <th>Nota</th>
                                        <th>Pembayaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($pengeluaran) > 0): ?>
                                        <?php $i = 1; ?>
                                        <?php foreach ($pengeluaran as $row): ?>
                                            <tr>
                                                <td class="text-center"><?= $i++ ?></td>
                                                <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                                <td><?= htmlspecialchars($row['seksi_nama']) ?></td>
                                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                                <td class="text-end">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                                <td><?= $row['keterangan'] ?></td>
                                                <td class="text-center">
                                                    <?php if (!empty($row['nota'])): ?>
                                                        <a href="uploads/<?= htmlspecialchars($row['nota']) ?>" target="_blank">Lihat Nota</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if (!empty($row['bayar'])): ?>
                                                        <a href="uploads/<?= htmlspecialchars($row['bayar']) ?>" target="_blank">Lihat Bukti Pembayaran</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">Belum ada data pengeluaran.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="4" class="text-end">Total Pengeluaran:</td>
                                        <td class="text-end">Rp <?= number_format($total, 0, ',', '.') ?></td>
                                        <td colspan="3"></td>
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
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="assets/js/adminlte.js"></script>
    <script src="assets/js/sides.js"></script>
</body>

</html>