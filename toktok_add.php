<?php
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Ambil data anggota untuk dropdown
$anggotaStmt = $pdo->query("SELECT id, nama FROM anggota ORDER BY nama ASC");
$anggotas = $anggotaStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anggota_id     = $_POST['anggota_id'] ?? '';
    $tanggal_bayar  = $_POST['tanggal_bayar'] ?? '';
    $toktok         = $_POST['toktok'] ?? 0;
    $sukarela       = $_POST['sukarela'] ?? 0;
    $keterangan     = trim($_POST['keterangan'] ?? '');
    $bukti          = '';

    // Upload bukti jika ada
    if (!empty($_FILES['bukti']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        // Ambil ekstensi file
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));

        // Ambil nama anggota untuk dijadikan bagian nama file
        $stmtNama = $pdo->prepare("SELECT nama FROM anggota WHERE id = ?");
        $stmtNama->execute([$anggota_id]);
        $namaAnggota = $stmtNama->fetchColumn();
        $namaAnggotaSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($namaAnggota)); // buat aman untuk nama file

        // Format nama file: toktokripe-namaanggota-tanggal.ext
        $tanggalFile = date('Ymd', strtotime($tanggal_bayar));
        $filename = "toktokripe-" . $namaAnggotaSlug . "-" . $tanggalFile . "." . $ext;
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES["bukti"]["tmp_name"], $targetFile)) {
            $bukti = $filename;
        } else {
            $error = "Gagal upload bukti pembayaran.";
        }
    }


    if (!$error && $anggota_id && $tanggal_bayar) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO iuran (anggota_id, tanggal_bayar, toktok, sukarela, keterangan, bukti)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$anggota_id, $tanggal_bayar, $toktok, $sukarela, $keterangan, $bukti]);
            header('Location: toktok-proses.php');
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        if (!$anggota_id || !$tanggal_bayar) $error = "Harap isi semua field wajib.";
    }
}
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

    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
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
                            <h3 class="mb-0">Add New Tok-tok ripe</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="toktok-proses.php">Tok-tok ripe</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add Tok-tok ripe</li>
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
                                    <div class="card-title">Tok-tok ripe Information</div>
                                </div>
                                <!--end::Header-->

                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>
                                <!--begin::Form-->
                                <form method="POST" enctype="multipart/form-data">
                                    <!--begin::Body-->
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Anggota</label>
                                            <select name="anggota_id" class="form-select" required>
                                                <option value="">-- Pilih Anggota --</option>
                                                <?php foreach ($anggotas as $a): ?>
                                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Bayar</label>
                                            <input type="date" name="tanggal_bayar" class="form-control" required>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Toktok (Rp)</label>
                                                <input type="number" name="toktok" class="form-control" min="0" placeholder="Jumlah toktok">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Sukarela (Rp)</label>
                                                <input type="number" name="sukarela" class="form-control" min="0" placeholder="Jumlah sukarela">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Keterangan</label>
                                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Upload Bukti (opsional)</label>
                                            <input type="file" name="bukti" class="form-control" accept="image/*,application/pdf">
                                        </div>
                                    </div>
                                    <!--end::Body-->
                                    <!--begin::Footer-->
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                        <a href="toktok-proses.php" class="float-end btn btn-secondary">Back</a>
                                    </div>
                                    <!--end::Footer-->
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