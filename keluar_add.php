<?php
$menu = 'keluar-proses';
session_start();
require_once 'db.php';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$seksi = $_SESSION['role_id'] ?? null;
$error = '';
$success = '';
// helper: buat slug sederhana untuk nama file
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'item');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $nama = trim($_POST['nama'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $jumlah = (float)($_POST['jumlah'] ?? 0);

    $notaFileName = '';
    $bayarFileName = '';

    // validasi sederhana
    if (!$nama) {
        $error = 'Nama wajib diisi.';
    } elseif (!$keterangan) {
        $error = 'Keterangan wajib diisi.';
    } elseif ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0.';
    } else {
        // pastikan folder upload ada
        $targetDir = __DIR__ . '/uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        // proses upload nota (opsional)
        if (!empty($_FILES['nota']['name'])) {
            $ext = strtolower(pathinfo($_FILES['nota']['name'], PATHINFO_EXTENSION));
            $base = slugify($nama) . '-nota-' . date('YmdHis', strtotime($tanggal));
            $notaFileName = $base . '.' . $ext;
            $target = $targetDir . $notaFileName;

            // hindari duplikat
            $i = 1;
            while (file_exists($target)) {
                $notaFileName = $base . "-{$i}." . $ext;
                $target = $targetDir . $notaFileName;
                $i++;
            }

            if (!move_uploaded_file($_FILES['nota']['tmp_name'], $target)) {
                $error = 'Gagal mengunggah file nota.';
            }
        }

        // proses upload bayar (opsional)
        if (empty($error) && !empty($_FILES['bayar']['name'])) {
            $ext = strtolower(pathinfo($_FILES['bayar']['name'], PATHINFO_EXTENSION));
            $base = slugify($nama) . '-bayar-' . date('YmdHis', strtotime($tanggal));
            $bayarFileName = $base . '.' . $ext;
            $target = $targetDir . $bayarFileName;

            $i = 1;
            while (file_exists($target)) {
                $bayarFileName = $base . "-{$i}." . $ext;
                $target = $targetDir . $bayarFileName;
                $i++;
            }

            if (!move_uploaded_file($_FILES['bayar']['tmp_name'], $target)) {
                $error = 'Gagal mengunggah file bayar.';
            }
        }

        // insert ke database jika tidak ada error
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO pengeluaran (tanggal, seksi, nama, keterangan, jumlah, nota, bayar)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $tanggal,
                    $seksi,
                    $nama,
                    $keterangan,
                    $jumlah,
                    $notaFileName,
                    $bayarFileName
                ]);
                header('Location: keluar-proses.php?added=1');
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
                // hapus file yang sudah terupload jika DB gagal
                if ($notaFileName && file_exists($targetDir . $notaFileName)) @unlink($targetDir . $notaFileName);
                if ($bayarFileName && file_exists($targetDir . $bayarFileName)) @unlink($targetDir . $bayarFileName);
            }
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
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">

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
                            <h3 class="mb-0">Add Pengeluaran</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="keluar-proses.php">Pengeluaran</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add Pengeluaran</li>
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
                                    <div class="card-title">Pengeluaran Information</div>
                                </div>
                                <!--end::Header-->
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>

                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Tanggal</label>
                                                <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Judul Pengeluaran</label>
                                                <input type="text" name="nama" class="form-control" required>
                                                <div class="form-text text-muted">Contoh: Cetak Spanduk</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Jumlah (Rp)</label>
                                                <input type="number" name="jumlah" class="form-control" min="0" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Keterangan</label>
                                                <textarea id="keterangan" name="keterangan" class="form-control" required></textarea>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Upload Nota (opsional)</label>
                                                <input type="file" name="nota" class="form-control" accept="image/*,application/pdf">
                                            </div>
                                            <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>

                                                <div class="col-md-6">
                                                    <label class="form-label">Upload Bukti Bayar (opsional)</label>
                                                    <input type="file" name="bayar" class="form-control" accept="image/*,application/pdf">
                                                </div>

                                            <?php } ?>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                                <a href="keluar-proses.php" class="btn btn-secondary float-end">Kembali</a>
                                            </div>
                                        </div>
                                    </form>
                                    <!--end::Form-->
                                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#keterangan').summernote({
                placeholder: 'Tulis keterangan di sini...<br> contoh: 2 lembar spanduk ukuran 2x3.',
                tabsize: 2,
                height: 160,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        });
    </script>
    <script
        src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
        integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ="
        crossorigin="anonymous"></script>

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