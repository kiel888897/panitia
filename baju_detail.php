<?php
require_once 'db.php';

// Pastikan ada ID anggota
if (!isset($_GET['id'])) {
    header('Location: baju.php');
    exit;
}

$anggota_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT SUM(qty) AS total_qty FROM order_items WHERE order_id = ?");
$stmt->execute([$anggota_id]);
$totalQty = (int) $stmt->fetchColumn();

$targetBaju = $totalQty * 100000; // harga satuan baju = 100rb

// Ambil data anggota
$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id = ?");
$stmt->execute([$anggota_id]);
$anggota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anggota) {
    echo "<script>alert('Data anggota tidak ditemukan'); window.location='baju.php';</script>";
    exit;
}

// Ambil data pembayaran anggota ini
$stmt = $pdo->prepare("
    SELECT id, jumlah, tanggal, bukti
    FROM bayar_baju
    WHERE anggota_id = ?
    ORDER BY tanggal DESC
");
$stmt->execute([$anggota_id]);
$pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total pembayaran
$totalBayar = array_sum(array_column($pembayaran, 'jumlah'));

// Tentukan status
if ($totalBayar >= $targetBaju && $targetBaju > 0) {
    $status = 'Lunas';
    $statusClass = 'success';
} elseif ($totalBayar > 0) {
    $status = 'Cicilan';
    $statusClass = 'warning';
} else {
    $status = 'Belum Bayar';
    $statusClass = 'secondary';
}
// Hitung sisa pembayaran
$sisaPembayaran = max($targetBaju - $totalBayar, 0);

?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Detail Pembayaran Baju | Panitia Bona Taon PTS</title>

    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css">
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="">

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-12">
                            <h3 class="mb-0">Detail Pembayaran Baju: <?= htmlspecialchars($anggota['nama']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Informasi Anggota</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Nama:</strong> <?= htmlspecialchars($anggota['nama']) ?></p>
                            <p><strong>Status:</strong>
                                <span class="badge bg-<?= $statusClass ?>"><?= $status ?></span>
                            </p>
                            <p><strong>Total Pesanan:</strong> Rp <?= number_format($targetBaju, 0, ',', '.') ?></p>
                            <p><strong>Total Pembayaran:</strong> Rp <?= number_format($totalBayar, 0, ',', '.') ?></p>
                            <p><strong>Sisa Pembayaran:</strong> Rp <?= number_format($sisaPembayaran, 0, ',', '.') ?></p>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Riwayat Pembayaran</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped" id="detailTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Jumlah (Rp)</th>
                                        <th>Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pembayaran)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">Belum ada pembayaran</td>
                                        </tr>
                                        <?php else: $i = 1;
                                        foreach ($pembayaran as $row): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars(date('d-m-Y', strtotime($row['tanggal']))) ?></td>
                                                <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php if (!empty($row['bukti'])): ?>
                                                        <a href="uploads/<?= htmlspecialchars($row['bukti']) ?>" target="_blank" class="text-primary">
                                                            <i class="bi bi-image"></i> Lihat
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                    <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <?php include 'footer.php'; ?>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/adminlte.js"></script>
    <script>
        $(document).ready(function() {
            $('#detailTable').DataTable({
                pageLength: 10,
                language: {
                    search: "Cari:",
                    paginate: {
                        previous: "Prev",
                        next: "Next"
                    }
                }
            });
        });
    </script>

</body>

</html>