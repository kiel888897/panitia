<?php
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
    FROM anggota a
    LEFT JOIN iuran i ON a.id = i.anggota_id
    GROUP BY a.id, a.nama, a.jabatan
    ORDER BY a.nama ASC
");
$anggotaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Iuran Toktok Ripe | Panitia Bona Taon PTS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="assets/img/favicon.png" type="image/x-icon" />

    <!-- Bootstrap & AdminLTE -->
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css">
    <style>
        .status-lunas {
            color: green;
        }

        /* hijau muda */
        .status-cicilan {
            color: yellow;
        }

        /* kuning muda */
        .status-belum {
            color: red;
        }

        /* abu muda */
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div>
        <?php include 'header.php'; ?>
        <?php include 'sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Data Iuran Toktok Ripe</h3>
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
                            <a href="toktok_add.php" class="btn btn-success mb-3"><i class="bi bi-plus-circle"></i> Tambah Pembayaran</a>
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
                                                    <a href="toktok_detail.php?id=<?= $row['anggota_id'] ?>" class="btn btn-link p-0">Lihat Detail</a>
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

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/adminlte.js"></script>

    <script>
        $(document).ready(function() {
            $('#toktokTable').DataTable({
                pageLength: 25,
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