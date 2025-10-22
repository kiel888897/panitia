<?php
$menu = 'kupon';
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';


if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM kupon WHERE id = ?");
    $stmt->execute([$id]);
    $kupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kupon) {
        header('Location: kupon.php');
        exit;
    }
} else {
    header('Location: kupon.php');
    exit;
}
// Inisialisasi pesan
$error = '';
$success = '';

if (!isset($_GET['id'])) {
    header('Location: kupon.php');
    exit;
}

$id = (int) $_GET['id'];

// Ambil data kupon yang akan diedit
$stmt = $pdo->prepare("SELECT * FROM kupon WHERE id = ?");
$stmt->execute([$id]);
$kupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$kupon) {
    header('Location: kupon.php');
    exit;
}

// Ambil daftar nama untuk datalist (jika tabel hula ada)
try {
    $hulas = $pdo->query("SELECT nama FROM hula ORDER BY nama")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $hulas = [];
}

// Simpan perubahan jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $nomor_kupon = trim($_POST['nomor_kupon'] ?? '');
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $kembali = (int)($_POST['kembali'] ?? 0);

    if ($nama === '') {
        $error = "Nama wajib diisi.";
    } else {
        // Cek apakah nama sudah ada di tabel kupon kecuali record yang diedit
        $check = $pdo->prepare("SELECT COUNT(*) FROM kupon WHERE nama = ? AND id != ?");
        $check->execute([$nama, $id]);
        $exists = $check->fetchColumn();

        if ($exists > 0) {
            $error = "Nama <strong>" . htmlspecialchars($nama) . "</strong> sudah memiliki data kupon! - Silakan hubungi panitia.";
        } else {
            // Update data kupon
            $stmt = $pdo->prepare("
                UPDATE kupon
                SET nama = ?, nomor_kupon = ?, jumlah = ?, kembali = ?
                WHERE id = ?
            ");
            $stmt->execute([$nama, $nomor_kupon, $jumlah, $kembali, $id]);

            header('Location: kupon.php?updated=1');
            exit;
        }
    }
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
                            <h3 class="mb-0">Edit Kupon</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="kupon.php">Kupon</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Kupon</li>
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
                        <div class="col-md-12">
                            <!--begin::Quick Example-->
                            <div class="card card-primary card-outline mb-4">
                                <!--begin::Header-->
                                <div class="card-header">
                                    <div class="card-title">Kupon Information</div>
                                </div>
                                <!--end::Header-->

                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>
                                <!--begin::Form-->
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="nama" class="form-label">Nama</label>
                                            <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? $kupon['nama']) ?>" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="nomor_kupon" class="form-label">Nomor Kupon</label>
                                            <textarea id="nomor_kupon" name="nomor_kupon" rows="3"
                                                class="form-control" placeholder="Masukkan daftar nomor kupon, pisahkan dengan koma" required><?= htmlspecialchars($_POST['nomor_kupon'] ?? $kupon['nomor_kupon']) ?></textarea>
                                            <div class="form-text">Pisahkan setiap nomor dengan koma (contoh: 001,002,003).</div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="jumlah" class="form-label">Jumlah Kupon</label>
                                                <input type="number" name="jumlah" id="jumlah" min="0"
                                                    class="form-control"
                                                    placeholder="0" required value="<?= htmlspecialchars($_POST['jumlah'] ?? $kupon['jumlah']) ?>">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="kembali" class="form-label">Kembali Kupon</label>
                                                <input type="number" name="kembali" id="kembali" min="0"
                                                    class="form-control" placeholder="0" value="<?= htmlspecialchars($_POST['kembali'] ?? $kupon['kembali']) ?>">
                                            </div>
                                        </div>

                                        <div class="card-footer">
                                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                            <a href="kupon.php" class="btn btn-secondary float-end">Kembali</a>
                                        </div>
                                    </div>
                                </form>
                                <!--end::Form-->
                            </div>

                        </div>
                    </div>
                </div>
        </main>
        <?php include 'footer.php' ?>
    </div>


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
                pageLength: 5, // Menentukan jumlah baris per halaman
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