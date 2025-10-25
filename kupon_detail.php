<?php
require_once 'db.php';

// Pastikan ada ID kupon
if (!isset($_GET['id'])) {
    header('Location: kupon.php');
    exit;
}

$kupon_id = (int) $_GET['id'];

// Ambil data kupon
$stmt = $pdo->prepare("SELECT * FROM kupon WHERE id = ?");
$stmt->execute([$kupon_id]);
$kupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kupon) {
    echo "<script>alert('Data kupon tidak ditemukan'); window.location='kupon.php';</script>";
    exit;
}

// Ambil data pembayaran kupon ini
$stmt = $pdo->prepare("
    SELECT id, bayar, tanggal, bukti
    FROM bayar_kupon
    WHERE id_kupon = ?
    ORDER BY tanggal DESC
");
$stmt->execute([$kupon_id]);
$pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total pembayaran
$totalBayar = array_sum(array_column($pembayaran, 'bayar'));

// Hitung total tagihan dan sisa pembayaran
$hargaPerKupon = 50000;
$jumlahKuponAktif = $kupon['jumlah'] - $kupon['kembali'];
$totalTagihan = $jumlahKuponAktif * $hargaPerKupon;
$sisaPembayaran = $totalTagihan - $totalBayar;

// Tentukan status pembayaran
$statusPembayaran = ($sisaPembayaran <= 0) ? 'Lunas' : 'Cicilan';
$statusClass = ($sisaPembayaran <= 0) ? 'text-success' : 'text-danger';

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
                        <div class="col-sm-6">
                            <h3 class="mb-0">Detail Pembayaran Kupon: <?= htmlspecialchars($kupon['nama']) ?></h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="kupon-proses.php">Pembayaran Kupon</a></li>
                                <li class="breadcrumb-item active">Detail Pembayaran</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Informasi Kupon</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nama:</strong> <?= htmlspecialchars($kupon['nama']) ?></p>
                                    <p><strong>Nomor Kupon:</strong> <?= htmlspecialchars($kupon['nomor_kupon']) ?></p>
                                    <p><strong>Jumlah Kupon:</strong> <?= number_format($kupon['jumlah'], 0, ',', '.') ?></p>
                                    <p><strong>Kupon Kembali:</strong> <?= number_format($kupon['kembali'], 0, ',', '.') ?></p>
                                    <p><strong>Kupon Aktif:</strong> <?= number_format($jumlahKuponAktif, 0, ',', '.') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Tagihan:</strong> Rp <?= number_format($totalTagihan, 0, ',', '.') ?></p>
                                    <p><strong>Total Pembayaran:</strong> Rp <?= number_format($totalBayar, 0, ',', '.') ?></p>
                                    <p><strong>Status:</strong> <span class="<?= $statusClass ?>"><?= $statusPembayaran ?></span></p>
                                    <?php if ($sisaPembayaran > 0): ?>
                                        <p><strong>Sisa Pembayaran:</strong> <span class="text-danger">Rp <?= number_format($sisaPembayaran, 0, ',', '.') ?></span></p>
                                    <?php endif; ?>
                                </div>
                            </div>
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
                                                <td>Rp <?= number_format($row['bayar'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php if (!empty($row['bukti'])): ?>
                                                        <a href="uploads/<?= htmlspecialchars($row['bukti']) ?>" target="_blank" class="text-primary">
                                                            <i class="bi bi-image"></i> Lihat Bukti
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Cash</span>
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