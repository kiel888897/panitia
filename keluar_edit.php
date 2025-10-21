<?php
session_start();
require_once 'db.php';

$menu = 'keluar-proses';

// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
$role = (int)($_SESSION['role_id'] ?? 0);

// helper slug untuk file
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'file');
}

// Pastikan id ada
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: keluar-proses.php');
    exit;
}
$id = (int) $_GET['id'];

// Ambil data single pengeluaran
$stmt = $pdo->prepare("SELECT * FROM pengeluaran WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$peng = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$peng) {
    header('Location: keluar-proses.php');
    exit;
}

// Cek hak akses: jika bukan role 1/2 harus sama seksi
if (!in_array($role, [1, 2], true) && ((int)$peng['seksi'] !== $role)) {
    header('Location: keluar-proses.php?error=unauthorized');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $nama = trim($_POST['nama'] ?? '');
    $keterangan = $_POST['keterangan'] ?? '';
    $jumlah = (float)($_POST['jumlah'] ?? 0);

    $notaOld = $peng['nota'] ?? '';
    $bayarOld = $peng['bayar'] ?? '';
    $notaFile = $notaOld;
    $bayarFile = $bayarOld;

    // Validasi
    if (!$nama) {
        $error = 'Nama wajib diisi.';
    } elseif ($jumlah <= 0) {
        $error = 'Jumlah harus lebih dari 0.';
    }

    // Pastikan folder uploads
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // Proses upload nota (jika ada)
    if (empty($error) && !empty($_FILES['nota']['name'])) {
        $ext = strtolower(pathinfo($_FILES['nota']['name'], PATHINFO_EXTENSION));
        $base = slugify($nama) . '-nota-' . date('YmdHis');
        $filename = $base . '.' . $ext;
        $target = $uploadDir . $filename;

        $i = 1;
        while (file_exists($target)) {
            $filename = $base . "-{$i}." . $ext;
            $target = $uploadDir . $filename;
            $i++;
        }

        if (move_uploaded_file($_FILES['nota']['tmp_name'], $target)) {
            // hapus file lama jika ada
            if (!empty($notaOld) && file_exists($uploadDir . $notaOld)) {
                @unlink($uploadDir . $notaOld);
            }
            $notaFile = $filename;
        } else {
            $error = 'Gagal mengunggah file nota.';
        }
    }

    // Proses upload bayar (jika ada)
    if (empty($error) && !empty($_FILES['bayar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['bayar']['name'], PATHINFO_EXTENSION));
        $base = slugify($nama) . '-bayar-' . date('YmdHis');
        $filename = $base . '.' . $ext;
        $target = $uploadDir . $filename;

        $i = 1;
        while (file_exists($target)) {
            $filename = $base . "-{$i}." . $ext;
            $target = $uploadDir . $filename;
            $i++;
        }

        if (move_uploaded_file($_FILES['bayar']['tmp_name'], $target)) {
            if (!empty($bayarOld) && file_exists($uploadDir . $bayarOld)) {
                @unlink($uploadDir . $bayarOld);
            }
            $bayarFile = $filename;
        } else {
            $error = 'Gagal mengunggah file bukti bayar.';
        }
    }

    // Jika tidak error, update DB
    if (empty($error)) {
        try {
            $stmtUpd = $pdo->prepare("
                UPDATE pengeluaran
                SET tanggal = ?, nama = ?, keterangan = ?, jumlah = ?, nota = ?, bayar = ?
                WHERE id = ?
            ");
            $stmtUpd->execute([
                $tanggal,
                $nama,
                $keterangan,
                $jumlah,
                $notaFile,
                $bayarFile,
                $id
            ]);
            header('Location: keluar-proses.php?updated=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">

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

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="">
        <?php include 'header.php' ?>
        <?php include 'sidebar.php' ?>
        <main class="app-main">
            <div class="app-content">
                <div class="container-fluid">
                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Edit Pengeluaran</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars(date('Y-m-d', strtotime($peng['tanggal'] ?? date('Y-m-d')))) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Judul Pengeluaran</label>
                                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($peng['nama'] ?? '') ?>" required>

                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Jumlah (Rp)</label>
                                        <input type="number" name="jumlah" class="form-control" min="0" value="<?= htmlspecialchars($peng['jumlah'] ?? 0) ?>" required>

                                    </div>

                                    <div class=" col-12">
                                        <label class="form-label">Keterangan</label>
                                        <textarea name="keterangan" id="keterangan" class="form-control"><?= htmlspecialchars($peng['keterangan'] ?? '') ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Upload Nota (opsional)</label>
                                        <input type="file" name="nota" class="form-control" accept="image/*,application/pdf">
                                        <?php if (!empty($peng['nota'])): ?>
                                            <p class="mt-2">File saat ini:
                                                <?php if (file_exists(__DIR__ . '/uploads/' . $peng['nota'])): ?>
                                                    <a href="uploads/<?= htmlspecialchars($peng['nota']) ?>" target="_blank"><?= htmlspecialchars($peng['nota']) ?></a>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($peng['nota']) ?>
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) { ?>

                                        <div class="col-md-6">
                                            <label class="form-label">Upload Bukti Bayar (opsional)</label>
                                            <input type="file" name="bayar" class="form-control" accept="image/*,application/pdf">
                                            <?php if (!empty($peng['bayar'])): ?>
                                                <p class="mt-2">File saat ini:
                                                    <?php if (file_exists(__DIR__ . '/uploads/' . $peng['bayar'])): ?>
                                                        <a href="uploads/<?= htmlspecialchars($peng['bayar']) ?>" target="_blank"><?= htmlspecialchars($peng['bayar']) ?></a>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($peng['bayar']) ?>
                                                    <?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                    <?php } ?>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                        <a href="keluar-proses.php" class="btn btn-secondary float-end">Kembali</a>
                                    </div>
                                </div>
                            </form>
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
                placeholder: 'Tulis keterangan di sini...',
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
</body>

</html>