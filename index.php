<?php
$menu = 'index';
session_start();
require_once 'db.php';

// Cek jika admin sudah login
if (!isset($_SESSION['admin_id'])) {
  header('Location: login.php');
  exit;
}

// // === Query masing-masing sumber pemasukan ===

// // 1. Toktok + Sukarela
// $stmt1 = $pdo->query("SELECT IFNULL(SUM(toktok + sukarela), 0) AS total FROM iuran");
// $iuran = (float)$stmt1->fetchColumn();

// // 2. Sumbangan Dana
// $stmt2 = $pdo->query("SELECT IFNULL(SUM(jumlah), 0) AS total FROM sumbangan WHERE jenis = 'dana'");
// $dana = (float)$stmt2->fetchColumn();

// // 3. Sumbangan Tor-tor
// $stmt3 = $pdo->query("SELECT IFNULL(SUM(jumlah), 0) AS total FROM sumbangan WHERE jenis = 'tor-tor'");
// $tortor = (float)$stmt3->fetchColumn();

// // 4. Bayar Kupon
// $stmt4 = $pdo->query("SELECT IFNULL(SUM(bayar), 0) AS total FROM bayar_kupon");
// $kupon = (float)$stmt4->fetchColumn();

// // 5. Bayar Baju
// $stmt5 = $pdo->query("SELECT IFNULL(SUM(jumlah), 0) AS total FROM bayar_baju");
// $baju = (float)$stmt5->fetchColumn();

// // 6. Bayar Silua
// $stmt6 = $pdo->query("SELECT IFNULL(SUM(jumlah), 0) AS total FROM bayar_silua");
// $silua = (float)$stmt6->fetchColumn();

// // === Hitung total keseluruhan ===
// $total_pemasukan = $iuran + $dana + $tortor + $kupon + $baju + $silua;

// // === Tampilkan dalam tabel HTML ===
// echo "
// <table class='table table-bordered table-sm align-middle'>
// <thead class='table-secondary text-center'>
// <tr>
//   <th>#</th>
//   <th>Sumber Pemasukan</th>
//   <th>Jumlah (Rp)</th>
// </tr>
// </thead>
// <tbody>
// <tr><td class='text-center'>1</td><td>Iuran (Toktok + Sukarela)</td><td class='text-end'>" . number_format($iuran, 0, ',', '.') . "</td></tr>
// <tr><td class='text-center'>2</td><td>Sumbangan Dana</td><td class='text-end'>" . number_format($dana, 0, ',', '.') . "</td></tr>
// <tr><td class='text-center'>3</td><td>Sumbangan Tor-tor</td><td class='text-end'>" . number_format($tortor, 0, ',', '.') . "</td></tr>
// <tr><td class='text-center'>4</td><td>Bayar Kupon</td><td class='text-end'>" . number_format($kupon, 0, ',', '.') . "</td></tr>
// <tr><td class='text-center'>5</td><td>Bayar Baju</td><td class='text-end'>" . number_format($baju, 0, ',', '.') . "</td></tr>
// <tr><td class='text-center'>6</td><td>Bayar Silua</td><td class='text-end'>" . number_format($silua, 0, ',', '.') . "</td></tr>
// <tr class='table-light fw-bold'>
//   <td colspan='2' class='text-center'>TOTAL PEMASUKAN</td>
//   <td class='text-end'>" . number_format($total_pemasukan, 0, ',', '.') . "</td>
// </tr>
// </tbody>
// </table>";

// echo "Halo, " . $_SESSION['nama_lengkap'] . " (Role ID: " . $_SESSION['role_id'] . ")";
// Query gabungan untuk total pemasukan
$query = "
SELECT 
    IFNULL((SELECT SUM(toktok + sukarela) FROM iuran), 0) +
    IFNULL((SELECT SUM(jumlah) FROM sumbangan WHERE jenis = 'dana'), 0) +
    IFNULL((SELECT SUM(jumlah) FROM sumbangan WHERE jenis = 'tor-tor'), 0) +
    IFNULL((SELECT SUM(bayar) FROM bayar_kupon), 0) +
    IFNULL((SELECT SUM(jumlah) FROM bayar_baju), 0) +
    IFNULL((SELECT SUM(jumlah) FROM bayar_silua), 0) AS total_pemasukan
";

$stmt = $pdo->query($query);
$total = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil nilai total pemasukan
$total_pemasukan = $total['total_pemasukan'];


// ===== TOTAL PENGELUARAN =====
$queryPengeluaran = "SELECT IFNULL(SUM(jumlah), 0) AS total_pengeluaran FROM pengeluaran";
$stmt2 = $pdo->query($queryPengeluaran);
$total2 = $stmt2->fetch(PDO::FETCH_ASSOC);
$total_pengeluaran = $total2['total_pengeluaran'];
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
  <!--end::Primary Meta Tags-->
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
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <?php include 'header.php' ?>
  <?php include 'sidebar.php' ?>
  <!--begin::App Main-->
  <main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
      <!--begin::Container-->
      <div class="container-fluid">
        <!--begin::Row-->
        <div class="row">
          <div class="col-sm-6">
            <h3 class="mb-0">Dashboard Panitia Bona Taon PTS</h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-end">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
            </ol>
          </div>
        </div>
        <!--end::Row-->
      </div>
      <!--end::Container-->
    </div>
    <div class="app-content">
      <!--begin::Container-->
      <div class="container-fluid">

        <div class="row g-4">
          <!-- CARD PEMASUKAN -->
          <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-left: 5px solid #28a745;">
              <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                  <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                    <i class="bi bi-graph-up-arrow fs-2"></i>
                  </div>
                </div>
                <div>

                  <div>
                    <h4 class=" text-secondary mb-1">Total Pemasukan</h4>
                  </div>
                  <div class="align-items-baseline">
                    <h3 class="fw-bold text-success mb-0" style="line-height: 1;"> Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></h3>
                  </div>
                  <small class="text-muted">Sampai saat ini</small>
                </div>
              </div>
            </div>
          </div>

          <!-- CARD PENGELUARAN -->
          <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-left: 5px solid #dc3545;">
              <div class="card-body d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                  <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                    <i class="bi bi-cash-stack fs-2"></i>
                  </div>
                </div>
                <div>
                  <div>
                    <h4 class=" text-secondary mb-1">Total Pengeluaran</h4>
                  </div>
                  <div class="align-items-baseline">
                    <h3 class="fw-bold text-danger mb-0" style="line-height: 1;">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></h3>
                  </div>
                  <small class="text-muted">Sampai saat ini</small>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
      <!--end::Container-->
    </div>
    <!--end::App Content-->
  </main>
  <!--end::App Main-->
  <?php include 'footer.php' ?>
  </div>
  <!--end::App Wrapper-->
  <!--begin::Script-->
  <!--begin::Third Party Plugin(OverlayScrollbars)-->
  <script
    src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
    integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ="
    crossorigin="anonymous"></script>
  <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
  <script
    src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
    crossorigin="anonymous"></script>
  <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
    integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
    crossorigin="anonymous"></script>
  <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
  <script src="assets/js/adminlte.js"></script>
  <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
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
  <!--end::OverlayScrollbars Configure-->
  <!-- OPTIONAL SCRIPTS -->
  <!-- apexcharts -->
  <script
    src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
    integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8="
    crossorigin="anonymous"></script>
  <script>
    // NOTICE!! DO NOT USE ANY OF THIS JAVASCRIPT
    // IT'S ALL JUST JUNK FOR DEMO
    // ++++++++++++++++++++++++++++++++++++++++++

    /* apexcharts
     * -------
     * Here we will create a few charts using apexcharts
     */

    //-----------------------
    // - MONTHLY SALES CHART -
    //-----------------------

    const sales_chart_options = {
      series: [{
          name: 'Digital Goods',
          data: [28, 48, 40, 19, 86, 27, 90],
        },
        {
          name: 'Electronics',
          data: [65, 59, 80, 81, 56, 55, 40],
        },
      ],
      chart: {
        height: 180,
        type: 'area',
        toolbar: {
          show: false,
        },
      },
      legend: {
        show: false,
      },
      colors: ['#0d6efd', '#20c997'],
      dataLabels: {
        enabled: false,
      },
      stroke: {
        curve: 'smooth',
      },
      xaxis: {
        type: 'datetime',
        categories: [
          '2023-01-01',
          '2023-02-01',
          '2023-03-01',
          '2023-04-01',
          '2023-05-01',
          '2023-06-01',
          '2023-07-01',
        ],
      },
      tooltip: {
        x: {
          format: 'MMMM yyyy',
        },
      },
    };

    const sales_chart = new ApexCharts(
      document.querySelector('#sales-chart'),
      sales_chart_options,
    );
    sales_chart.render();

    //---------------------------
    // - END MONTHLY SALES CHART -
    //---------------------------

    function createSparklineChart(selector, data) {
      const options = {
        series: [{
          data
        }],
        chart: {
          type: 'line',
          width: 150,
          height: 30,
          sparkline: {
            enabled: true,
          },
        },
        colors: ['var(--bs-primary)'],
        stroke: {
          width: 2,
        },
        tooltip: {
          fixed: {
            enabled: false,
          },
          x: {
            show: false,
          },
          y: {
            title: {
              formatter: function(seriesName) {
                return '';
              },
            },
          },
          marker: {
            show: false,
          },
        },
      };

      const chart = new ApexCharts(document.querySelector(selector), options);
      chart.render();
    }

    const table_sparkline_1_data = [25, 66, 41, 89, 63, 25, 44, 12, 36, 9, 54];
    const table_sparkline_2_data = [12, 56, 21, 39, 73, 45, 64, 52, 36, 59, 44];
    const table_sparkline_3_data = [15, 46, 21, 59, 33, 15, 34, 42, 56, 19, 64];
    const table_sparkline_4_data = [30, 56, 31, 69, 43, 35, 24, 32, 46, 29, 64];
    const table_sparkline_5_data = [20, 76, 51, 79, 53, 35, 54, 22, 36, 49, 64];
    const table_sparkline_6_data = [5, 36, 11, 69, 23, 15, 14, 42, 26, 19, 44];
    const table_sparkline_7_data = [12, 56, 21, 39, 73, 45, 64, 52, 36, 59, 74];

    createSparklineChart('#table-sparkline-1', table_sparkline_1_data);
    createSparklineChart('#table-sparkline-2', table_sparkline_2_data);
    createSparklineChart('#table-sparkline-3', table_sparkline_3_data);
    createSparklineChart('#table-sparkline-4', table_sparkline_4_data);
    createSparklineChart('#table-sparkline-5', table_sparkline_5_data);
    createSparklineChart('#table-sparkline-6', table_sparkline_6_data);
    createSparklineChart('#table-sparkline-7', table_sparkline_7_data);

    //-------------
    // - PIE CHART -
    //-------------

    const pie_chart_options = {
      series: [700, 500, 400, 600, 300, 100],
      chart: {
        type: 'donut',
      },
      labels: ['Chrome', 'Edge', 'FireFox', 'Safari', 'Opera', 'IE'],
      dataLabels: {
        enabled: false,
      },
      colors: ['#0d6efd', '#20c997', '#ffc107', '#d63384', '#6f42c1', '#adb5bd'],
    };

    const pie_chart = new ApexCharts(document.querySelector('#pie-chart'), pie_chart_options);
    pie_chart.render();

    //-----------------
    // - END PIE CHART -
    //-----------------
  </script>
  <!--end::Script-->
</body>
<!--end::Body-->

</html>