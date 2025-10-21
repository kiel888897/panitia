<?php
$menu = 'keluar-proses';
session_start();
require_once 'db.php';

// Cek jika admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
$seksi = (int)($_SESSION['role_id'] ?? 0);

// Ambil data dari tabel pengeluaran:
// Jika role 1 atau 2 -> tampilkan semua, selain itu filter berdasarkan seksi = role_id
if (in_array($seksi, [1, 2], true)) {
    $stmt = $pdo->query("SELECT p.*, ar.nama_role AS seksi_name FROM pengeluaran p LEFT JOIN admin_role ar ON p.seksi = ar.id ORDER BY p.id DESC");
    $pengeluarans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT p.*, ar.nama_role AS seksi_name FROM pengeluaran p LEFT JOIN admin_role ar ON p.seksi = ar.id WHERE p.seksi = ? ORDER BY p.id DESC");
    $stmt->execute([$seksi]);
    $pengeluarans = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Panitia Bona Taon PTS</title>
    <!--begin::Primary Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="title" content="Panitia Bona Taon PTS" />
    <meta name="author" content="El - Total" />
    <meta
        name="description"
        content="Panitia Bona Taon PTS" />
    <meta
        name="keywords"
        content="Panitia Bona Taon PTS" />

    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">
    <!--begin::Fonts-->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
        crossorigin="anonymous" />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css"
        integrity="sha256-tZHrRjVqNSRyWg2wbppGnT833E/Ys0DHWGwT04GiqQg="
        crossorigin="anonymous" />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI="
        crossorigin="anonymous" />
    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="assets/css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->
    <!-- apexcharts -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
        integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0="
        crossorigin="anonymous" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css" />

</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="">
        <!--begin::Header-->
        <?php include 'header.php' ?>
        <!--end::Header-->
        <!--begin::Sidebar-->
        <?php include 'sidebar.php' ?>
        <!--end::Sidebar-->
        <!--begin::App Main-->
        <main class="app-main">
            <!--begin::App Content Header-->
            <div class="app-content-header">
                <!--begin::Container-->
                <div class="container-fluid">
                    <!--begin::Row-->
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Pengeluaran</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Pengeluaran</li>
                            </ol>
                        </div>
                    </div>
                    <!--end::Row-->
                </div>
                <!--end::Container-->
            </div>
            <!--end::App Content Header-->
            <!--begin::App Content-->
            <div class="app-content">
                <!--begin::Container-->
                <div class="container-fluid">
                    <!--begin::Row-->
                    <div class="row">
                        <div class="col-12">
                            <!-- Default box -->
                            <div class="card">

                                <div class="card-body">
                                    <a href="keluar_add.php" class="btn btn-success">Tambah Pengeluaran</a>

                                    <table class="table table-bordered" id="eventTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Tanggal</th>
                                                <th>Seksi</th>
                                                <th>Nama</th>
                                                <th>Keterangan</th>
                                                <th>Jumlah (Rp)</th>
                                                <th>Nota</th>
                                                <th>Bayar</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i = 1;
                                            foreach ($pengeluarans as $p): ?>
                                                <tr>
                                                    <td><?= $i++ ?></td>
                                                    <td><?= htmlspecialchars(date('Y-m-d', strtotime($p['tanggal'] ?? ''))); ?></td>
                                                    <td><?= htmlspecialchars($p['seksi_name'] ?? $p['seksi']); ?></td>
                                                    <td><?= htmlspecialchars($p['nama']); ?></td>
                                                    <td><?= ($p['keterangan']); ?></td>
                                                    <td style="text-align:right"><?= number_format((float)($p['jumlah'] ?? 0), 0, ',', '.'); ?></td>
                                                    <td>
                                                        <?php if (!empty($p['nota']) && file_exists(__DIR__ . '/uploads/' . $p['nota'])): ?>
                                                            <a href="uploads/<?= htmlspecialchars($p['nota']); ?>" target="_blank">Lihat</a>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($p['nota'] ?? '-'); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($p['bayar']) && file_exists(__DIR__ . '/uploads/' . $p['bayar'])): ?>
                                                            <a href="uploads/<?= htmlspecialchars($p['bayar']); ?>" target="_blank">Lihat</a>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($p['bayar'] ?? '-'); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>

                                                        <?php if ($p['bayar'] === null || $p['bayar'] === '' || $_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>

                                                            <div class="btn-group" role="group" aria-label="Actions">
                                                                <a href="keluar_edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm" title="Edit">✏️</a>
                                                                <a href="keluar_delete.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure?')">🗑️</a>
                                                            </div>

                                                        <?php } else echo '<span class="badge bg-success">Approved</span>' ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- /.card-body -->
                                <!-- <div class="card-footer">Footer</div> -->
                                <!-- /.card-footer-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'footer.php' ?>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if (isset($_GET['added'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'The data has been successfully input.',
                confirmButtonText: 'OKAY',
                confirmButtonColor: '#28a745'
            });
        </script>
    <?php elseif (isset($_GET['deleted'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Data Deleted!',
                text: 'The Data has been successfully removed from the system.',
                confirmButtonText: 'OKAY',
                confirmButtonColor: '#28a745'
            });
        </script>
    <?php elseif (isset($_GET['error'])): ?>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Action Failed',
                text: 'There was a problem processing your request. Please try again.',
                confirmButtonText: 'OKAY',
                confirmButtonColor: '#dc3545'
            });
        </script>
    <?php endif; ?>
    <!-- DataTables JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
        integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ="
        crossorigin="anonymous"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
        crossorigin="anonymous"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
        crossorigin="anonymous"></script>
    <script src="assets/js/adminlte.js"></script>
    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables pada tabel
            $('table').DataTable({
                paging: true, // Aktifkan pagination
                searching: true, // Aktifkan fitur pencarian
                lengthChange: false, // Menonaktifkan pilihan jumlah item per halaman
                pageLength: 10, // Menentukan jumlah baris per halaman
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