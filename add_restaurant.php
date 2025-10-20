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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan trim semua input
    $name           = trim($_POST['name'] ?? '');
    $location       = trim($_POST['location'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $phone_resto    = preg_replace('/[^0-9]/', '', $_POST['phone_resto']);
    $phone_manager    = preg_replace('/[^0-9]/', '', $_POST['phone_manager']);
    $phone_owner    = preg_replace('/[^0-9]/', '', $_POST['phone_owner']);
    // $menu   = trim($_POST['menu'] ?? '');
    $link_menu   = trim($_POST['link_menu'] ?? '');
    $bank_account   = trim($_POST['bank_account'] ?? '');
    $number_bank    = trim($_POST['number_bank'] ?? '');
    // $food_type      = trim($_POST['food_type'] ?? '');
    $food_type = $_POST['food_type'] ?? [];
    $food_type = array_map('trim', $food_type); // Hapus spasi tiap nilai
    $food_type = implode(',', $food_type); // Gabungkan jadi string: "general,vegetarian"

    $cost_delivery  = trim($_POST['cost_delivery'] ?? '');
    $latitude       = trim($_POST['latitude'] ?? '');
    $longitude      = trim($_POST['longitude'] ?? '');
    $slug = slugify($name);
    $status         = 'activ'; // Default status
    $photo_filename = null; // Default photo = null
    if (!empty($_FILES['photo']['name'])) {
        $photo = $_FILES['photo'];
        $photo_filename = time() . '_' . basename($photo['name']); // Rename file untuk unik
        $target_directory = 'uploads/restaurants/'; // Folder untuk simpan foto

        if (!is_dir($target_directory)) {
            mkdir($target_directory, 0755, true); // Buat folder kalau belum ada
        }

        $target_path = $target_directory . $photo_filename;

        if (!move_uploaded_file($photo['tmp_name'], $target_path)) {
            $error = 'Failed to upload photo.';
        }
    }
    $menu_file_filename = null;

    if (!empty($_FILES['menu_file']['name'])) {
        $menu_file = $_FILES['menu_file'];
        $menu_file_filename = time() . '_' . basename($menu_file['name']);
        $menu_target_directory = 'uploads/menus/';

        if (!is_dir($menu_target_directory)) {
            mkdir($menu_target_directory, 0755, true);
        }

        $menu_target_path = $menu_target_directory . $menu_file_filename;

        if (!move_uploaded_file($menu_file['tmp_name'], $menu_target_path)) {
            $error = 'Failed to upload menu file.';
        }
    }
    // Validasi input minimal yang diperlukan
    if ($name) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO restaurants 
                (name, location, description, address, phone_resto, phone_manager, phone_owner, bank_account, number_bank, food_type, cost_delivery, latitude, longitude, photo, link_menu, menu, status, slug)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $location,
                $description,
                $address,
                $phone_resto,
                $phone_manager,
                $phone_owner,
                $bank_account,
                $number_bank,
                $food_type,
                $cost_delivery,
                $latitude,
                $longitude,
                $photo_filename,
                $link_menu,
                $menu_file_filename,
                $status,
                $slug
            ]);

            header('Location: restaurants.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill in all required fields (Restaurant Name and Food Type).';
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
    <!-- font  -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Roboto&family=Lato&family=Montserrat&family=Poppins&display=swap" rel="stylesheet">
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
                            <h3 class="mb-0">Add New Restaurant</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item"><a href="restaurants.php">Restaurants</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add Restaurants</li>
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
                                    <div class="card-title">Restaurant Information</div>
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
                                            <label for="name" class="form-label">Restaurant Name</label>
                                            <input
                                                type="text" class="form-control" name="name" id="name" aria-describedby="name" required />
                                        </div>
                                        <div class="mb-3">
                                            <label for="location" class="form-label">Location</label>
                                            <select class="form-select" name="location" id="location" required>
                                                <option selected disabled value="">Choose ...</option>
                                                <option value="nembrala">Nembrala</option>
                                                <option value="boa">Boa</option>
                                                <option value="sedoin">Sedoin</option>
                                                <option value="tenggoin">Tenggoin</option>
                                            </select>
                                            <div class="invalid-feedback">Please select a valid Food Location.</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control" name="address" id="address" aria-label="address" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Food Type</label>
                                            <div class="row">
                                                <div class="col-6 col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="food_type[]" value="general" id="foodGeneral">
                                                        <label class="form-check-label" for="foodGeneral">General</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="food_type[]" value="vegetarian" id="foodVegetarian">
                                                        <label class="form-check-label" for="foodVegetarian">Vegetarian</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="food_type[]" value="seafood" id="foodSeafood">
                                                        <label class="form-check-label" for="foodSeafood">Seafood</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="food_type[]" value="indonesian" id="foodIndonesian">
                                                        <label class="form-check-label" for="foodIndonesian">Indonesian</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="food_type[]" value="italian" id="foodItalian">
                                                        <label class="form-check-label" for="foodItalian">Italian</label>
                                                    </div>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="food_type[]" value="western" id="foodWestern">
                                                        <label class="form-check-label" for="foodWestern">Western</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="link_menu" class="form-label">Link Menu</label>
                                            <input
                                                type="text" class="form-control" name="link_menu" id="link_menu" aria-describedby="link_menu" />
                                            <small class="text-warning">This menu link will serve as the main link. Leave it blank to enable the Upload Menu File field instead.</small><br>
                                            <small class="text-info">Google Drive - PDF: /preview <br>
                                                Google Drive - IMG: -/preview<br>
                                                EX PDF: https://drive.google.com/file/d/1NJrAtYCcpbDIqFxhcgisacgdD1sXyKSZ/preview
                                            </small>
                                        </div>
                                        <div class="mb-3">
                                            <label for="menu_file" class="form-label">Upload Menu File (PDF)</label>
                                            <input class="form-control" type="file" name="menu_file" id="menu_file" accept=".pdf,.doc,.docx,image/*">
                                        </div>

                                        <!-- <div class="mb-3">
                                            <label for="menu" class="form-label">Link Menu</label>
                                            <input type="text" name="menu" id="menu" class="form-control" aria-describedby="menu">
                                            <small class="text-muted">PDF: /preview <br>
                                                IMG: -/preview<br>
                                                EX: https://drive.google.com/file/d/1NJrAtYCcpbDIqFxhcgisacgdD1sXyKSZ/preview
                                            </small>
                                        </div> -->

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>

                                            <textarea class="form-control" name="description" id="description" aria-label="description" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone_resto" class="form-label">Phone Restaurant</label>
                                            <input type="text" name="phone_resto" id="phone_resto" class="form-control" placeholder="e.g. 6281234567890" aria-describedby="phone_resto">
                                            <small class="text-muted">Use format: 6281234567890 (no + or spaces)</small>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone_manager" class="form-label">Phone Manager</label>
                                            <input type="text" name="phone_manager" id="phone_manager" class="form-control" placeholder="e.g. 6281234567890" aria-describedby="phone_manager">
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone_owner" class="form-label">Phone Owner</label>
                                            <input type="text" name="phone_owner" id="phone_owner" class="form-control" placeholder="e.g. 6281234567890" aria-describedby="phone_owner">
                                        </div>
                                        <div class="mb-3">
                                            <label for="bank_account" class="form-label">Bank</label>
                                            <input type="text" name="bank_account" id="bank_account" class="form-control" aria-describedby="bank_account">
                                        </div>
                                        <div class="mb-3">
                                            <label for="number_bank" class="form-label">Number Bank Account</label>
                                            <input type="number" name="number_bank" id="number_bank" class="form-control" aria-describedby="number_bank">
                                        </div>
                                        <div class="mb-3">
                                            <label for="cost_delivery" class="form-label">Cost Delivery</label>
                                            <input type="number" name="cost_delivery" id="cost_delivery" class="form-control" aria-describedby="cost_delivery">
                                        </div>
                                        <div class="row g-3 mb-3">
                                            <div class="col-md-6">
                                                <label for="latitude" class="form-label">Latitude</label>
                                                <input type="text" name="latitude" id="latitude" class="form-control" aria-describedby="latitude" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="longitude" class="form-label">Longitude</label>
                                                <input type="text" name="longitude" id="longitude" class="form-control" aria-describedby="longitude" required>
                                            </div>
                                        </div>
                                        <div class="input-group mb-3">
                                            <input type="file" class="form-control" name="photo" id="photo" accept="image/*" required />
                                            <label class="input-group-text" for="photo">Photo</label>
                                        </div>

                                    </div>
                                    <!--end::Body-->
                                    <!--begin::Footer-->
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                        <a href="restaurants.php" class="float-end btn btn-secondary">Back</a>
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
            height: 300,
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
</body>

</html>