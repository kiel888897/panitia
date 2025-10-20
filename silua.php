<?php
$menu = 'silua';
session_start();
require_once 'db.php';
// Cek login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
// Fungsi ambil data berdasarkan jabatan 
function getAnggotasByJabatan($pdo, $jabatan)
{
    $stmt = $pdo->prepare("SELECT id, nama FROM anggotas WHERE jabatan = :jabatan ORDER BY nama ASC");
    $stmt->execute(['jabatan' => $jabatan]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Ambil data untuk tiap tab
$hulas = getAnggotasByJabatan($pdo, 'hula');
$borus = getAnggotasByJabatan($pdo, 'boru');
$beres = getAnggotasByJabatan($pdo, 'bere');
// ambil total pembayaran per silua sekaligus (untuk semua tab)
$totStmt = $pdo->query("SELECT silua_id, SUM(jumlah) AS total FROM bayar_silua GROUP BY silua_id");
$totals = [];
foreach ($totStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $totals[$r['silua_id']] = $r['total'];
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
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Silua</h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                                <li class="breadcrumb-item active">Silua</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="hula-tab" data-bs-toggle="tab" data-bs-target="#hula" type="button" role="tab" aria-controls="hula" aria-selected="true">Hula-hula</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="boru-tab" data-bs-toggle="tab" data-bs-target="#boru" type="button" role="tab" aria-controls="boru" aria-selected="false">Boru</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="bere-tab" data-bs-toggle="tab" data-bs-target="#bere" type="button" role="tab" aria-controls="bere" aria-selected="false">Bere</button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body tab-content" id="myTabContent">
                            <!-- TAB HULA -->
                            <div class="tab-pane fade show active" id="hula" role="tabpanel" aria-labelledby="hula-tab">
                                <form method="POST" action="simpan_silua.php" class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="hulaNama" class="form-label">Pilih / Ketik Nama</label>
                                        <input list="hulaList" name="nama" id="hulaNama" class="form-control" placeholder="Pilih atau ketik nama baru" required>
                                        <datalist id="hulaList">
                                            <?php foreach ($hulas as $h): ?>
                                                <option value="<?= htmlspecialchars($h['nama']); ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="jumlahHula" class="form-label">Jumlah (Rp)</label>
                                        <input type="number" class="form-control" name="jumlah" id="jumlahHula" placeholder="Masukkan jumlah" required>
                                    </div>
                                    <input type="hidden" name="keterangan" value="hula">
                                    <div class="col-md-2 align-self-end">
                                        <button type="submit" class="btn btn-primary w-100">Simpan</button>
                                    </div>
                                </form>

                                <table id="tableHula" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama</th>
                                            <th>Jumlah (Rp)</th>
                                            <th>Pembayaran (Rp)</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM silua WHERE keterangan = 'hula' ORDER BY id DESC");
                                        $dataHula = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($dataHula as $i => $d): ?>
                                            <tr>
                                                <td><?= $i + 1; ?></td>
                                                <td><?= htmlspecialchars($d['nama']); ?></td>
                                                <td><?= number_format($d['jumlah'], 0, ',', '.'); ?></td>
                                                <td><?= number_format($totals[$d['id']] ?? 0, 0, ',', '.'); ?></td>

                                                <td>
                                                    <a href="hapus_silua.php?id=<?= $d['id'] ?>" class="btn btn-danger btn-sm" aria-label="Delete Silua" title="Delete Silua" onclick="return confirm('Are you sure?')">🗑️</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- TAB BORU -->
                            <div class="tab-pane fade" id="boru" role="tabpanel" aria-labelledby="boru-tab">
                                <form method="POST" action="simpan_silua.php" class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="boruNama" class="form-label">Pilih / Ketik Nama</label>
                                        <input list="boruList" name="nama" id="boruNama" class="form-control" placeholder="Pilih atau ketik nama baru" required>
                                        <datalist id="boruList">
                                            <?php foreach ($borus as $b): ?>
                                                <option value="<?= htmlspecialchars($b['nama']); ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="jumlahBoru" class="form-label">Jumlah (Rp)</label>
                                        <input type="number" class="form-control" name="jumlah" id="jumlahBoru" required>
                                    </div>
                                    <input type="hidden" name="keterangan" value="boru">
                                    <div class="col-md-2 align-self-end">
                                        <button type="submit" class="btn btn-primary w-100">Simpan</button>
                                    </div>
                                </form>

                                <table id="tableBoru" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama</th>
                                            <th>Jumlah (Rp)</th>
                                            <th>Pembayaran (Rp)</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM silua WHERE keterangan = 'boru' ORDER BY id DESC");
                                        $dataBoru = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($dataBoru as $i => $d): ?>
                                            <tr>
                                                <td><?= $i + 1; ?></td>
                                                <td><?= htmlspecialchars($d['nama']); ?></td>
                                                <td><?= number_format($d['jumlah'], 0, ',', '.'); ?></td>
                                                <td><?= number_format($totals[$d['id']] ?? 0, 0, ',', '.'); ?></td>
                                                <td>
                                                    <a href="hapus_silua.php?id=<?= $d['id'] ?>" class="btn btn-danger btn-sm" aria-label="Delete Silua" title="Delete Silua" onclick="return confirm('Are you sure?')">🗑️</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- TAB BERE -->
                            <div class="tab-pane fade" id="bere" role="tabpanel" aria-labelledby="bere-tab">
                                <form method="POST" action="simpan_silua.php" class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="bereNama" class="form-label">Pilih / Ketik Nama</label>
                                        <input list="bereList" name="nama" id="bereNama" class="form-control" placeholder="Pilih atau ketik nama baru" required>
                                        <datalist id="bereList">
                                            <?php foreach ($beres as $br): ?>
                                                <option value="<?= htmlspecialchars($br['nama']); ?>"></option>
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="jumlahBere" class="form-label">Jumlah (Rp)</label>
                                        <input type="number" class="form-control" name="jumlah" id="jumlahBere" required>
                                    </div>
                                    <input type="hidden" name="keterangan" value="bere">
                                    <div class="col-md-2 align-self-end">
                                        <button type="submit" class="btn btn-primary w-100">Simpan</button>
                                    </div>
                                </form>

                                <table id="tableBere" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nama</th>
                                            <th>Jumlah (Rp)</th>
                                            <th>Pembayaran (Rp)</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM silua WHERE keterangan = 'bere' ORDER BY id DESC");
                                        $dataBere = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($dataBere as $i => $d): ?>
                                            <tr>
                                                <td><?= $i + 1; ?></td>
                                                <td><?= htmlspecialchars($d['nama']); ?></td>
                                                <td><?= number_format($d['jumlah'], 0, ',', '.'); ?></td>
                                                <td><?= number_format($totals[$d['id']] ?? 0, 0, ',', '.'); ?></td>
                                                <td>
                                                    <a href="hapus_silua.php?id=<?= $d['id'] ?>" class="btn btn-danger btn-sm" aria-label="Delete Silua" title="Delete Silua" onclick="return confirm('Are you sure?')">🗑️</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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

    <?php if (isset($_GET['saved'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Data Berhasil Disimpan!',
                text: 'Data silua berhasil ditambahkan ke database.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#28a745'
            });
        </script>

    <?php elseif (isset($_GET['deleted'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Data Berhasil Dihapus!',
                text: 'Data silua telah dihapus dari database.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#28a745'
            });
        </script>

    <?php elseif (isset($_GET['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal Memproses!',
                text: 'Terjadi kesalahan saat memproses permintaan. Silakan coba lagi.',
                confirmButtonText: 'OK',
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
        $(function() {
            $('#tableHula, #tableBoru, #tableBere').DataTable({
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
        $(document).ready(function() {
            // Inisialisasi tiap tabel
            $('#tableHula').DataTable();
            $('#tableBoru').DataTable();
            $('#tableBere').DataTable();
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab'); // contoh: "boru"

            if (activeTab) {
                // Nonaktifkan semua tab
                document.querySelectorAll('.nav-link').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(tab => tab.classList.remove('show', 'active'));

                // Aktifkan tab yang sesuai parameter
                const tabButton = document.querySelector(`#${activeTab}-tab`);
                const tabContent = document.querySelector(`#${activeTab}`);
                if (tabButton && tabContent) {
                    tabButton.classList.add('active');
                    tabContent.classList.add('show', 'active');
                }
            }
        });
    </script>

</body>

</html>