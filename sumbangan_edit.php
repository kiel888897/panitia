<?php
session_start();
require_once 'db.php';
function slugify($text)
{
    // Ganti karakter non huruf/angka dengan strip
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate ke ASCII
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Hapus karakter yang tidak diinginkan
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim strip
    $text = trim($text, '-');
    // Hapus duplikat strip
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);

    return $text ?: 'n-a';
}
// Cek apakah admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM sumbangan WHERE id = ?");
    $stmt->execute([$id]);
    $sumbangan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sumbangan) {
        header('Location: sumbangan.php');
        exit;
    }
} else {
    header('Location: sumbangan.php');
    exit;
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan trim semua input
    $nama           = trim($_POST['nama'] ?? '');
    $jenis       = trim($_POST['jenis'] ?? '');
    $jumlah       = trim($_POST['jumlah'] ?? '');
    $keterangan   = trim($_POST['description'] ?? '');
    $tanggal      = trim($_POST['tanggal'] ?? '');
    $bukti_lama     = $_POST['bukti_lama'] ?? ($sumbangan['photo'] ?? '');
    $bukti          = $bukti_lama;
    // jika ada upload bukti baru
    if (!empty($_FILES['bukti']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        // ambil ekstensi dan slug nama anggota
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
        $slugNama = slugify($nama);
        $tanggalFile = date('Ymd', strtotime($tanggal));
        $filename = "sumbangan-{$slugNama}-{$tanggalFile}." . $ext;
        $targetFile = $targetDir . $filename;

        // jika file dengan nama sama sudah ada, tambahkan suffix unik
        $counter = 1;
        $base = pathinfo($filename, PATHINFO_FILENAME);
        while (file_exists($targetFile)) {
            $filename = "{$base}-{$counter}." . $ext;
            $targetFile = $targetDir . $filename;
            $counter++;
        }

        // hapus file lama jika ada dan bukan kosong
        if (!empty($bukti_lama) && file_exists($targetDir . $bukti_lama)) {
            @unlink($targetDir . $bukti_lama);
        }

        if (move_uploaded_file($_FILES["bukti"]["tmp_name"], $targetFile)) {
            $bukti = $filename;
        } else {
            $error = "Gagal mengunggah bukti pembayaran.";
        }
    }
    // Validasi input minimal yang diperlukan
    if ($nama && $jenis && $tanggal) {
        try {


            $stmt = $pdo->prepare("UPDATE sumbangan SET nama = ?, photo = ?, jenis = ?, jumlah = ?, keterangan = ?, tanggal = ? WHERE id = ?");
            $stmt->execute([
                $nama,
                $bukti,
                $jenis,
                $jumlah,
                $keterangan,
                $tanggal,
                $id
            ]);

            header('Location: sumbangan.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill in all required fields (Nama, Jenis, Jumlah, Keterangan, and Tanggal).';
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
                            <h3 class="mb-0">Edit Sumbangan</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="sumbangan.php">Sumbangan</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit Sumbangan</li>
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
                                    <div class="card-title">Sumbangan Information</div>
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
                                            <label for="tanggal" class="form-label">Tanggal</label>
                                            <input
                                                type="date" class="form-control" name="tanggal" id="tanggal" aria-describedby="tanggal" value="<?= htmlspecialchars($sumbangan['tanggal']) ?>" required />
                                        </div>
                                        <div class="mb-3">
                                            <label for="nama" class="form-label">Nama</label>
                                            <input
                                                type="text" class="form-control" name="nama" id="nama" aria-describedby="nama" value="<?= htmlspecialchars($sumbangan['nama']) ?>" required />
                                        </div>
                                        <div class="mb-3">
                                            <label for="jenis" class="form-label">Jenis</label>
                                            <select class="form-select" name="jenis" id="jenis" required>
                                                <option selected disabled value="">Choose ...</option>
                                                <option value="dana" <?= ($sumbangan['jenis'] === 'dana') ? 'selected' : '' ?>>Dana</option>
                                                <option value="produk" <?= ($sumbangan['jenis'] === 'produk') ? 'selected' : '' ?>>Produk</option>
                                            </select>
                                            <div class="invalid-feedback">Please select a valid jenis Type.</div>
                                        </div>

                                        <div class="mb-3" id="jumlahContainer">
                                            <label for="jumlah" class="form-label">Jumlah</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                name="jumlah"
                                                id="jumlah"
                                                aria-describedby="jumlah"
                                                placeholder="Masukkan jumlah (Rp)" value="<?= htmlspecialchars($sumbangan['jumlah']) ?>" />
                                        </div>



                                        <div class="mb-3">
                                            <label for="description" class="form-label">Keterangan</label>
                                            <textarea class="form-control" name="description" id="description" aria-label="description"><?= htmlspecialchars($sumbangan['keterangan']) ?></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Upload (opsional)</label>
                                            <input type="file" name="bukti" class="form-control" accept="image/*,application/pdf">
                                            <?php if (!empty($sumbangan['photo'])): ?>
                                                <p class="mt-2">File saat ini:
                                                    <a href="uploads/<?= htmlspecialchars($sumbangan['photo']) ?>" target="_blank"><?= htmlspecialchars($sumbangan['photo']) ?></a>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <!--end::Body-->
                                    <!--begin::Footer-->
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                        <a href="sumbangan.php" class="float-end btn btn-secondary">Back</a>
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

    <!-- JS Summernote -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
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
    <script>
        $('#description').summernote({
            placeholder: 'Tulis konten di sini...',
            callbacks: {
                onInit: function() {
                    $('#description').summernote('fontName', 'Poppins');
                }
            },
            tabsize: 2,
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            fontNames: [
                'Poppins', 'Roboto', 'Lato', 'Montserrat', 'Open Sans',
                'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New',
                'Georgia', 'Impact', 'Tahoma', 'Times New Roman', 'Trebuchet MS', 'Verdana'
            ],
            fontNamesIgnoreCheck: [
                'Poppins', 'Roboto', 'Lato', 'Montserrat', 'Open Sans'
            ],
            callbacks: {
                onImageUpload: function(files) {
                    // Upload gambar ke server
                    uploadImage(files[0]);
                }
            }
        });

        function uploadImage(file) {
            let data = new FormData();
            data.append('image', file);

            $.ajax({
                url: 'upload.php', // Ganti ini dengan endpoint server kamu
                method: 'POST',
                data: data,
                contentType: false,
                processData: false,
                dataType: 'json', // <-- Tambahkan ini
                success: function(res) {
                    $('#description').summernote('insertImage', res.url);
                },
                error: function(err) {
                    console.error(err);
                    alert("Upload gagal.");
                }
            });
        }


        function handleSubmit() {
            const html = $('#description').summernote('code');
            console.log("Konten HTML:", html);
            alert("Konten berhasil ditangkap. Lihat console.");
            return false;
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const jenisSelect = document.getElementById("jenis");
            const jumlahContainer = document.getElementById("jumlahContainer");

            jenisSelect.addEventListener("change", function() {
                if (this.value === "dana") {
                    jumlahContainer.style.display = "block";
                } else {
                    jumlahContainer.style.display = "none";
                    document.getElementById("jumlah").value = ""; // kosongkan jika bukan dana
                }
            });
        });
    </script>
</body>

</html>