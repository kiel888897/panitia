<?php
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data iuran dan gabungkan dengan tabel anggota
$stmt = $pdo->query("
    SELECT 
        iuran.id,
        iuran.tanggal_bayar,
        iuran.toktok,
        iuran.sukarela,
        iuran.keterangan,
        iuran.bukti,
        anggota.nama AS nama_anggota,
        anggota.jabatan
    FROM iuran
    INNER JOIN anggota ON iuran.anggota_id = anggota.id
    ORDER BY iuran.tanggal_bayar DESC
");
$iurans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Iuran Toktok Ripe | Panitia Bona Taon PTS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="assets/img/favicon.png" type="image/x-icon" />

    <!-- Bootstrap & AdminLTE -->
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
                            <h3 class="mb-0">Riwayat Pembayaran Toktok Ripe</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Toktok Ripe</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-body">
                            <a href="toktok_add.php" class="btn btn-success mb-3"><i class="bi bi-plus-circle"></i> Tambah Iuran</a>
                            <table class="table table-bordered" id="toktokTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Anggota</th>
                                        <th>Jabatan</th>
                                        <th>Tanggal Bayar</th>
                                        <th>Iuran Toktok (Rp)</th>
                                        <th>Sukarela (Rp)</th>
                                        <th>Keterangan</th>
                                        <th>Bukti</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1;
                                    foreach ($iurans as $row): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                                            <td>
                                                <?php
                                                $jabatan = strtolower($row['jabatan']);
                                                if ($jabatan === 'hula') {
                                                    echo 'Hula Hula';
                                                } elseif ($jabatan === 'bere') {
                                                    echo 'Bere & Ibebere';
                                                } else {
                                                    echo ucfirst($jabatan);
                                                }
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_bayar']))) ?></td>
                                            <td><?= number_format($row['toktok'], 0, ',', '.') ?></td>
                                            <td><?= number_format($row['sukarela'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                                            <td>
                                                <?php if (!empty($row['bukti'])): ?>
                                                    <a href="uploads/<?= htmlspecialchars($row['bukti']) ?>" target="_blank">
                                                        <i class="bi bi-file-earmark-image"></i> Lihat
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="toktok_edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                                                <a href="toktok_delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">🗑️</a>
                                            </td>
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

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/adminlte.js"></script>

    <script>
        $(document).ready(function() {
            $('#toktokTable').DataTable({
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