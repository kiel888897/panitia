<?php
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Pastikan ada ID anggota
if (!isset($_GET['id'])) {
    header('Location: toktok.php');
    exit;
}

$anggota_id = (int) $_GET['id'];

// Ambil data anggota
$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id = ?");
$stmt->execute([$anggota_id]);
$anggota = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anggota) {
    echo "<script>alert('Data anggota tidak ditemukan'); window.location='toktok.php';</script>";
    exit;
}

// Ambil data pembayaran anggota ini
$stmt = $pdo->prepare("
    SELECT * FROM iuran
    WHERE anggota_id = ?
    ORDER BY tanggal_bayar DESC
");
$stmt->execute([$anggota_id]);
$pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total
$totalToktok = 0;
$totalSukarela = 0;
foreach ($pembayaran as $p) {
    $totalToktok += $p['toktok'];
    $totalSukarela += $p['sukarela'];
}

// Tentukan status
$targetToktok = 250000;
if ($totalToktok >= $targetToktok) {
    $status = 'Lunas';
    $statusClass = 'success';
} elseif ($totalToktok > 0) {
    $status = 'Cicilan';
    $statusClass = 'warning';
} else {
    $status = 'Belum Bayar';
    $statusClass = 'secondary';
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Detail Iuran Toktok Ripe | Panitia Bona Taon PTS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css">
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
                            <h3 class="mb-0">Detail Iuran: <?= htmlspecialchars($anggota['nama']) ?></h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="toktok.php">Toktok Ripe</a></li>
                                <li class="breadcrumb-item active">Detail</li>
                            </ol>
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
                            <p><strong>Jabatan:</strong>
                                <?php
                                $jabatan = strtolower($anggota['jabatan']);
                                if ($jabatan === 'hula') echo 'Hula Hula';
                                elseif ($jabatan === 'bere') echo 'Bere & Ibebere';
                                else echo ucfirst($jabatan);
                                ?>
                            </p>
                            <p><strong>Status:</strong> <span class="badge bg-<?= $statusClass ?>"><?= $status ?></span></p>
                            <p><strong>Total Toktok:</strong> Rp <?= number_format($totalToktok, 0, ',', '.') ?> / Rp <?= number_format($targetToktok, 0, ',', '.') ?></p>
                            <p><strong>Total Sukarela:</strong> Rp <?= number_format($totalSukarela, 0, ',', '.') ?></p>
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
                                        <th>Toktok (Rp)</th>
                                        <th>Sukarela (Rp)</th>
                                        <th>Keterangan</th>
                                        <th>Bukti</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pembayaran)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Belum ada pembayaran</td>
                                        </tr>
                                        <?php else: $i = 1;
                                        foreach ($pembayaran as $row): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_bayar']))) ?></td>
                                                <td><?= number_format($row['toktok'], 0, ',', '.') ?></td>
                                                <td><?= number_format($row['sukarela'], 0, ',', '.') ?></td>
                                                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                                <td>
                                                    <?php if (!empty($row['bukti'])): ?>
                                                        <a href="uploads/<?= htmlspecialchars($row['bukti']) ?>" target="_blank" class="text-primary"><i class="bi bi-image"></i> Lihat</a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="toktok_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                                    <a href="toktok_delete.php?id=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus data ini?')" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                    <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>

                            <a href="toktok.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Kembali</a>
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