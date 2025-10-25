<?php
$menu = 'lapsilua';
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil data silua + total pembayaran
$stmt = $pdo->query("
    SELECT 
        a.id,
        a.nama,
        a.jumlah AS total_silua,
        a.keterangan,
        COALESCE(SUM(b.jumlah), 0) AS total_bayar
    FROM silua a
    LEFT JOIN bayar_silua b ON a.id = b.silua_id
    GROUP BY a.id, a.nama, a.jumlah, a.keterangan
    ORDER BY a.keterangan, a.nama ASC
");
$siluas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan berdasarkan keterangan
$kelompok = [
    'hula' => [],
    'boru' => [],
    'bere' => [],
];
foreach ($siluas as $s) {
    $k = strtolower(trim($s['keterangan']));
    if (isset($kelompok[$k])) {
        $kelompok[$k][] = $s;
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Silua PTS | Panitia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-buttons-bs5@2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        .status-lunas {
            color: green;
            font-weight: bold;
        }

        .status-cicilan {
            color: orange;
            font-weight: bold;
        }

        .status-belum {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<!-- Modal Export -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Data Silua PTS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 30vh;">
                <iframe src="export_silua.php" style="width: 100%; height: 100%; border: none;"></iframe>
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
                            <h3 class="mb-0">Laporan Silua PTS</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Laporan Silua PTS</li>
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

                            <!-- Tabs untuk kategori -->
                            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="hula-tab" data-bs-toggle="tab" data-bs-target="#hula" type="button" role="tab">Hula-hula</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="boru-tab" data-bs-toggle="tab" data-bs-target="#boru" type="button" role="tab">Boru</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="bere-tab" data-bs-toggle="tab" data-bs-target="#bere" type="button" role="tab">Bere & Ibebere</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="myTabContent">
                                <?php foreach (['hula' => 'Hula-hula', 'boru' => 'Boru', 'bere' => 'Bere & Ibebere'] as $key => $label): ?>
                                    <div class="tab-pane fade <?= $key === 'hula' ? 'show active' : '' ?>" id="<?= $key ?>" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Nama</th>
                                                        <th>Silua (Rp)</th>
                                                        <th>Total Bayar (Rp)</th>
                                                        <th>Keterangan</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $no = 1;
                                                    $totalKategori = 0;
                                                    foreach ($kelompok[$key] as $row):
                                                        $silua = (int)$row['total_silua'];
                                                        $bayar = (int)$row['total_bayar'];
                                                        $totalKategori += $bayar;

                                                        if ($bayar >= $silua) {
                                                            $status = "Lunas";
                                                            $rowClass = "status-lunas";
                                                        } elseif ($bayar > 0) {
                                                            $status = "Cicilan";
                                                            $rowClass = "status-cicilan";
                                                        } else {
                                                            $status = "Belum Bayar";
                                                            $rowClass = "status-belum";
                                                        }
                                                    ?>
                                                        <tr>
                                                            <td><?= $no++ ?></td>
                                                            <td><b class="<?= $rowClass ?>"><?= htmlspecialchars($row['nama']) ?></b></td>
                                                            <td><?= number_format($silua, 0, ',', '.') ?></td>
                                                            <td><?= number_format($bayar, 0, ',', '.') ?></td>
                                                            <td>
                                                                <?php if ($bayar > 0): ?>
                                                                    <a href="silua_detail.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-link p-0">Lihat Detail</a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">-</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><strong><?= $status ?></strong></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-info">
                                                        <td colspan="3" class="text-end"><strong>Total Keseluruhan:</strong></td>
                                                        <td colspan="3"><strong><?= number_format($totalKategori, 0, ',', '.') ?></strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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