<?php
session_start();
require_once 'db.php';

// Fungsi ubah teks ke slug (untuk nama file)
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'n-a');
}

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Pastikan ada ID pembayaran
if (!isset($_GET['id'])) {
    header('Location: silua-proses.php');
    exit;
}

$id = (int) $_GET['id'];

// Ambil data bayar_silua beserta nama silua
$stmt = $pdo->prepare("
    SELECT bs.*, s.nama AS nama_silua
    FROM bayar_silua bs
    JOIN silua s ON bs.silua_id = s.id
    WHERE bs.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$bayar = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bayar) {
    header('Location: silua-proses.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah = $_POST['jumlah'] ?? 0;
    $tanggal = $_POST['tanggal'] ?? '';
    $bukti_lama = $_POST['bukti_lama'] ?? ($bayar['bukti'] ?? '');
    $bukti = $bukti_lama;

    if (!$tanggal) {
        $error = "Tanggal wajib diisi.";
    } elseif (!is_numeric($jumlah) || $jumlah <= 0) {
        $error = "Jumlah harus berupa angka lebih dari 0.";
    } else {
        // Jika ada upload bukti baru
        if (!empty($_FILES['bukti']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
            // slug sederhana untuk nama file
            $namaSiluaSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($bayar['nama_silua'] ?? 'silua'));
            $tanggalFile = date('Ymd', strtotime($tanggal));
            $filename = "silua-{$namaSiluaSlug}-{$tanggalFile}." . $ext;
            $targetFile = $targetDir . $filename;

            // Cegah nama duplikat
            $counter = 1;
            $base = pathinfo($filename, PATHINFO_FILENAME);
            while (file_exists($targetFile)) {
                $filename = "{$base}-{$counter}." . $ext;
                $targetFile = $targetDir . $filename;
                $counter++;
            }

            // Hapus file lama jika ada
            if (!empty($bukti_lama) && file_exists($targetDir . $bukti_lama)) {
                @unlink($targetDir . $bukti_lama);
            }

            if (move_uploaded_file($_FILES["bukti"]["tmp_name"], $targetFile)) {
                $bukti = $filename;
            } else {
                $error = "Gagal mengunggah bukti pembayaran.";
            }
        }

        // Update database jika tidak ada error
        if (empty($error)) {
            try {
                $stmtUpd = $pdo->prepare("
                    UPDATE bayar_silua
                    SET jumlah = ?, tanggal = ?, bukti = ?
                    WHERE id = ?
                ");
                $stmtUpd->execute([$jumlah, $tanggal, $bukti, $id]);
                header('Location: silua-proses.php');
                exit;
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
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
    <title>Admin | Panitia Bona Taon PTS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="assets/css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/css/dataTables.bootstrap5.min.css" />
</head>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="">
        <?php include 'header.php' ?>
        <?php include 'sidebar.php' ?>
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Edit Pembayaran Silua</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="silua-proses.php">Pembayaran Silua</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Data</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary card-outline mb-4">
                                <div class="card-header">
                                    <div class="card-title">Pembayaran Information</div>
                                </div>

                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>

                                <form method="POST" enctype="multipart/form-data">
                                    <div class="card-body">
                                        <input type="hidden" name="bukti_lama" value="<?= htmlspecialchars($bayar['bukti'] ?? '') ?>">

                                        <div class="mb-3">
                                            <label class="form-label">Nama Silua</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($bayar['nama_silua']) ?>" disabled>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Jumlah (Rp)</label>
                                            <input type="number" name="jumlah" class="form-control" required min="1" value="<?= htmlspecialchars($bayar['jumlah']) ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Pembayaran</label>
                                            <input type="date" name="tanggal" class="form-control" required value="<?= htmlspecialchars(date('Y-m-d', strtotime($bayar['tanggal']))) ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Bukti Pembayaran</label>
                                            <input type="file" name="bukti" class="form-control" accept="image/*,application/pdf">
                                            <?php if (!empty($bayar['bukti'])): ?>
                                                <p class="mt-2">File saat ini:
                                                    <a href="uploads/<?= htmlspecialchars($bayar['bukti']) ?>" target="_blank"><?= htmlspecialchars($bayar['bukti']) ?></a>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                        <a href="silua-proses.php" class="float-end btn btn-secondary">Kembali</a>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
        </main>
        <?php include 'footer.php' ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5@1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
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
            $('table').DataTable({
                paging: true,
                searching: true,
                lengthChange: false,
                pageLength: 5,
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