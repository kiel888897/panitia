<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Ambil daftar review dengan nama restoran
$stmt = $pdo->query("
    SELECT reservations.*, restaurants.name AS restaurant_name
    FROM reservations
    JOIN restaurants ON reservations.restaurant_id = restaurants.id
    ORDER BY reservations.id DESC
");
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Admin | Panitia Bona Taon PTS</title>
    <!--begin::Primary Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="title" content="Admin | Panitia Bona Taon PTS" />
    <meta name="author" content="El - Total" />
    <meta
        name="description"
        content="Admin Panitia Bona Taon PTS" />
    <meta
        name="keywords"
        content="Admin Panitia Bona Taon PTS" />

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
                            <h3 class="mb-0">Reservations</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">reservations</li>
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
                                <!-- <div class="card-header">
                                    <h3 class="card-title">Title</h3>
                                    <div class="card-tools">
                                        <button
                                            type="button"
                                            class="btn btn-tool"
                                            data-lte-toggle="card-collapse"
                                            title="Collapse">
                                            <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
                                            <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-tool"
                                            data-lte-toggle="card-remove"
                                            title="Remove">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div> -->
                                <div class="card-body">

                                    <table class="table table-bordered" id="reviewTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Restaurant</th>
                                                <th>Customer</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Guests</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i = 1;
                                            foreach ($reservations as $reservation): ?>
                                                <tr>
                                                    <td><?= $i++ ?></td>
                                                    <td><?= htmlspecialchars(ucwords($reservation['restaurant_name'])) ?></td>
                                                    <td><?= htmlspecialchars(ucwords($reservation['customer_name'])) ?></td>
                                                    <td><?= htmlspecialchars($reservation['customer_email']) ?></td>
                                                    <td><?= htmlspecialchars($reservation['customer_phone']) ?></td>
                                                    <td><?= htmlspecialchars($reservation['reservation_date']) ?></td>
                                                    <td><?= htmlspecialchars($reservation['reservation_time']) ?></td>
                                                    <td><?= htmlspecialchars($reservation['number_of_people']) ?></td>
                                                    <td><?= htmlspecialchars(ucwords($reservation['status'])) ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group" aria-label="Actions">
                                                            <a href="detail_reservations.php?id=<?= $reservation['id'] ?>" class="btn btn-info btn-sm">Details</a>
                                                            <?php if ($reservation['status'] == 'pending'): ?>
                                                                <a href="approve_reservations.php?id=<?= $reservation['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure to Confirmed this Reservations?')">Confirmed</a>
                                                                <a href="cancel_reservations.php?id=<?= $reservation['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure to Cancel this Reservations?')">Cancel</a>
                                                            <?php endif; ?>
                                                        </div>
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
    <?php if (isset($_GET['approved'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Review Approved!',
                text: 'The review has been successfully approved and is now visible to the public.',
                confirmButtonText: 'OKAY',
                confirmButtonColor: '#28a745'
            });
        </script>
    <?php elseif (isset($_GET['deleted'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Review Deleted!',
                text: 'The review has been successfully removed from the system.',
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