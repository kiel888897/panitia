<?php
require_once 'db.php';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Pemasukan PTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- JS Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
        }

        table th,
        table td {
            font-size: 0.9rem;
        }

        h4.section-title {
            background: #f1f3f5;
            padding: .5rem .75rem;
            border-left: 5px solid #0d6efd;
            margin-top: 2rem;
        }
    </style>
</head>

<body class="p-4">
    <div class="container-fluid">
        <h3 class="text-center mb-3">Laporan Pemasukan PTS</h3>

        <div class="text-center mb-4">
            <button id="exportExcel" class="btn btn-success me-2">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </button>
            <button id="exportPDF" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </button>
        </div>

        <?php

        echo "<h4 class='section-title mt-4'>Rekap Laporan Pemasukan</h4>";
        // === 1. TOKTOK & SUKARELA ===
        $stmt = $pdo->query("
    SELECT 
        COALESCE(SUM(i.toktok), 0) AS total_toktok,
        COALESCE(SUM(i.sukarela), 0) AS total_sukarela,
        COUNT(DISTINCT a.id) AS jumlah_anggota
    FROM anggotas a
    LEFT JOIN iuran i ON a.id = i.anggota_id
");
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        $jumlahAnggota = (int)$data['jumlah_anggota'];
        $totalToktok = (int)$data['total_toktok'];
        $totalSukarela = (int)$data['total_sukarela'];

        $toktokRipe = 250000;
        $jumlahToktok = $toktokRipe * $jumlahAnggota;
        $piutangToktok = $jumlahToktok - $totalToktok;


        // === 2. BAJU PTS ===
        $stmt2 = $pdo->query("
    SELECT 
        COALESCE(SUM(oi.total_qty), 0) AS total_qty,
        COALESCE(SUM(oi.total_qty), 0) * 100000 AS total_pesanan,
        COALESCE(SUM(bb.total_bayar), 0) AS total_bayar
    FROM
        (SELECT order_id, SUM(qty) AS total_qty
         FROM order_items
         GROUP BY order_id) oi
    LEFT JOIN
        (SELECT anggota_id, SUM(jumlah) AS total_bayar
         FROM bayar_baju
         GROUP BY anggota_id) bb
      ON oi.order_id = bb.anggota_id
");
        $baju = $stmt2->fetch(PDO::FETCH_ASSOC);

        $totalPesanan = (int)$baju['total_pesanan'];
        $totalBayarBaju = (int)$baju['total_bayar'];
        $piutangBaju = $totalPesanan - $totalBayarBaju;


        // === 3. KUPON PTS ===
        $stmt3 = $pdo->query("
    SELECT 
        COALESCE(SUM((k.jumlah - k.kembali) * 50000), 0) AS total_tagihan,
        COALESCE(SUM(bk.bayar), 0) AS total_bayar
    FROM kupon k
    LEFT JOIN bayar_kupon bk ON k.id = bk.id_kupon
");
        $kupon = $stmt3->fetch(PDO::FETCH_ASSOC);

        $totalTagihanKupon = (int)$kupon['total_tagihan'];
        $totalBayarKupon = (int)$kupon['total_bayar'];
        $piutangKupon = $totalTagihanKupon - $totalBayarKupon;


        // === 4. SILUA PER KELOMPOK ===
        $stmt4 = $pdo->query("
    SELECT 
        LOWER(TRIM(a.keterangan)) AS kategori,
        COALESCE(SUM(a.jumlah), 0) AS total_silua,
        COALESCE(SUM(b.jumlah), 0) AS total_bayar
    FROM silua a
    LEFT JOIN bayar_silua b ON a.id = b.silua_id
    GROUP BY LOWER(TRIM(a.keterangan))
");
        $siluaData = $stmt4->fetchAll(PDO::FETCH_ASSOC);

        $categories = [
            'hula' => ['label' => 'Silua Hula-hula', 'total_silua' => 0, 'total_bayar' => 0],
            'boru' => ['label' => 'Silua Boru', 'total_silua' => 0, 'total_bayar' => 0],
            'bere' => ['label' => 'Silua Bere & Ibebere', 'total_silua' => 0, 'total_bayar' => 0],
        ];

        foreach ($siluaData as $r) {
            $key = strtolower(trim($r['kategori']));
            if (isset($categories[$key])) {
                $categories[$key]['total_silua'] = (int)$r['total_silua'];
                $categories[$key]['total_bayar'] = (int)$r['total_bayar'];
            }
        }


        // === 5. SUMBANGAN (Dana & Tor-tor) ===
        $stmt5 = $pdo->query("
    SELECT 
        jenis,
        nama,
        COALESCE(jumlah, 0) AS jumlah
    FROM sumbangan
    WHERE jenis IN ('dana', 'tor-tor')
    ORDER BY jenis, tanggal DESC
");
        $sumbangan = $stmt5->fetchAll(PDO::FETCH_ASSOC);


        // === 6. OUTPUT RINGKASAN SEMUA ===
        echo "<table class='table table-bordered table-sm align-middle'>
<thead class='table-secondary text-center'>
<tr>
  <th>#</th>
  <th>Item / Uraian</th>
  <th>Jumlah (Rp)</th>
  <th>Pembayaran (Rp)</th>
  <th>Piutang (Rp)</th>
</tr>
</thead>
<tbody>
<tr>
  <td class='text-center'>1</td>
  <td>Toktok Ripe ({$jumlahAnggota} KK)</td>
  <td class='text-end'>" . number_format($jumlahToktok, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($totalToktok, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($piutangToktok, 0, ',', '.') . "</td>
</tr>
<tr>
  <td class='text-center'>2</td>
  <td>Sukarela</td>
  <td class='text-end'>" . number_format($totalSukarela, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($totalSukarela, 0, ',', '.') . "</td>
  <td class='text-end'>-</td>
</tr>
<tr>
  <td class='text-center'>3</td>
  <td>Baju PTS</td>
  <td class='text-end'>" . number_format($totalPesanan, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($totalBayarBaju, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($piutangBaju, 0, ',', '.') . "</td>
</tr>
<tr>
  <td class='text-center'>4</td>
  <td>Kupon Bajar KFC PTS</td>
  <td class='text-end'>" . number_format($totalTagihanKupon, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($totalBayarKupon, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($piutangKupon, 0, ',', '.') . "</td>
</tr>";

        $no = 5;
        $grandSilua = 0;
        $grandBayarSilua = 0;

        foreach ($categories as $v) {
            $piutang = $v['total_silua'] - $v['total_bayar'];
            echo "
<tr>
  <td class='text-center'>{$no}</td>
  <td>{$v['label']}</td>
  <td class='text-end'>" . number_format($v['total_silua'], 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($v['total_bayar'], 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($piutang, 0, ',', '.') . "</td>
</tr>";
            $no++;
            $grandSilua += $v['total_silua'];
            $grandBayarSilua += $v['total_bayar'];
        }

        // === Tambahkan baris sumbangan dinamis ===
        $totalJumlahSumbangan = 0;
        if (!empty($sumbangan)) {
            foreach ($sumbangan as $row) {
                $jenis = ucfirst($row['jenis']);
                $nama = htmlspecialchars($row['nama']);
                $jumlah = (int)$row['jumlah'];
                $totalJumlahSumbangan += $jumlah;

                echo "
<tr>
  <td class='text-center'>{$no}</td>
  <td>{$jenis} ({$nama})</td>
  <td class='text-end'>" . number_format($jumlah, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($jumlah, 0, ',', '.') . "</td>
  <td class='text-end'>0</td>
</tr>";
                $no++;
            }
        }

        // === TOTAL KESELURUHAN ===
        $totalJumlah = $jumlahToktok + $totalSukarela + $totalPesanan + $totalTagihanKupon + $grandSilua + $totalJumlahSumbangan;
        $totalBayar = $totalToktok + $totalSukarela + $totalBayarBaju + $totalBayarKupon + $grandBayarSilua + $totalJumlahSumbangan;
        $totalPiutang = $totalJumlah - $totalBayar;

        echo "
<tr class='table-light fw-bold'>
  <td colspan='2' class='text-center'>TOTAL PEMASUKAN</td>
  <td class='text-end'>" . number_format($totalJumlah, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($totalBayar, 0, ',', '.') . "</td>
  <td class='text-end'>" . number_format($totalPiutang, 0, ',', '.') . "</td>
</tr>
</tbody>
</table>";

        /* ============================================================
            Details
            ============================================================ */
        /* ================= TOK-TOK RIPE ================= */
        echo "<h4 class='section-title mt-4'>Tok-tok Ripe</h4>";

        /* ============================================================
            2. DATA TOK-TOK RIPE
            ============================================================ */

        $stmt = $pdo->query("
                SELECT 
                    a.id AS anggota_id,
                    a.nama,
                    a.jabatan,
                    COALESCE(SUM(i.toktok), 0) AS total_toktok,
                    COALESCE(SUM(i.sukarela), 0) AS total_sukarela
                FROM anggotas a
                LEFT JOIN iuran i ON a.id = i.anggota_id
                GROUP BY a.id, a.nama, a.jabatan
                ORDER BY FIELD(a.jabatan, 'hula', 'boru', 'bere'), a.nama
            ");
        $toktok = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($toktok)) {
            echo "<p class='text-muted'>Tidak ada data Tok-tok Ripe.</p>";
        } else {
            $orders = ['hula' => 'Hula-hula', 'boru' => 'Boru', 'bere' => 'Bere & Ibebere'];
            $grouped = ['hula' => [], 'boru' => [], 'bere' => []];
            foreach ($toktok as $r) $grouped[strtolower($r['jabatan'])][] = $r;

            echo "<table class='table table-bordered table-sm align-middle'>
    <thead class='table-secondary text-center'>
        <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Toktok Ripe (Rp)</th>
            <th>Sukarela (Rp)</th>
            <th>Total Pembayaran (Rp)</th>
            <th>Keterangan</th>
            <th>Status</th>
        </tr>
    </thead><tbody>";

            $no = 1;
            $grandTok = 0;
            $grandSuk = 0;
            $grandTot = 0;
            $toktokRipe = 250000; // nilai tetap

            foreach ($orders as $key => $label) {
                $rows = $grouped[$key];
                if (!$rows) continue;
                echo "<tr class='table-light fw-bold'><td colspan='7'>{$label}</td></tr>";

                $subTok = 0;
                $subSuk = 0;
                $subTot = 0;
                foreach ($rows as $r) {
                    $tok = (int)$toktokRipe; // nilai tetap
                    $suk = (int)$r['total_sukarela'];
                    $tot = (int)$r['total_toktok'] + $suk; // total pembayaran (hasil iuran)
                    $subTok += $tok;
                    $subSuk += $suk;
                    $subTot += $tot;

                    // status pembayaran
                    if ($tot >= $toktokRipe) {
                        $status = "<span class='text-success fw-bold'>Lunas</span>";
                    } elseif ($tot > 0) {
                        $status = "<span class='text-warning fw-bold'>Cicilan</span>";
                    } else {
                        $status = "<span class='text-danger fw-bold'>Belum Bayar</span>";
                    }

                    // link lihat detail hanya jika ada pembayaran
                    $link = ($tot > 0)
                        ? "<a href='toktok_detail.php?id={$r['anggota_id']}'>Lihat detail</a>"
                        : "-";

                    echo "<tr>
                <td class='text-center'>{$no}</td>
                <td>" . htmlspecialchars($r['nama']) . "</td>
                <td class='text-end'>" . number_format($tok, 0, ',', '.') . "</td>
                <td class='text-end'>" . number_format($suk, 0, ',', '.') . "</td>
                <td class='text-end'>" . number_format($tot, 0, ',', '.') . "</td>
                <td class='text-center'>{$link}</td>
                <td class='text-center'>{$status}</td>
              </tr>";
                    $no++;
                }

                echo "<tr class='fw-bold'>
            <td colspan='2' class='text-center'>SUBTOTAL {$label}</td>
            <td class='text-end'>" . number_format($subTok, 0, ',', '.') . "</td>
            <td class='text-end'>" . number_format($subSuk, 0, ',', '.') . "</td>
            <td class='text-end'>" . number_format($subTot, 0, ',', '.') . "</td>
            <td colspan='2'></td>
          </tr>";

                $grandTok += $subTok;
                $grandSuk += $subSuk;
                $grandTot += $subTot;
            }

            echo "<tr class='table-light fw-bold'>
        <td colspan='2' class='text-center'>TOTAL SELURUHNYA</td>
        <td class='text-end'>" . number_format($grandTok, 0, ',', '.') . "</td>
        <td class='text-end'>" . number_format($grandSuk, 0, ',', '.') . "</td>
        <td class='text-end'>" . number_format($grandTot, 0, ',', '.') . "</td>
        <td colspan='2'></td>
      </tr>
      </tbody></table>";
        }

        /* ================= BAJU PTS ================= */
        echo "<h4 class='section-title mt-4'>Baju PTS</h4>";

        /* ============================================================
            3. DATA BAJU
            ============================================================ */
        $stmt = $pdo->query("
                SELECT 
                    a.id AS anggota_id,
                    a.nama,
                    COALESCE(SUM(oi.qty),0) AS total_qty,
                    (COALESCE(SUM(oi.qty),0) * 100000) AS total_pesanan,
                    GROUP_CONCAT(CONCAT(oi.size, ' x', oi.qty) SEPARATOR ', ') AS pesanan,
                    COALESCE(bb_tot.total_bayar, 0) AS total_bayar
                FROM anggota a
                JOIN order_items oi ON oi.order_id = a.id
                LEFT JOIN (
                    SELECT anggota_id, SUM(jumlah) AS total_bayar
                    FROM bayar_baju
                    GROUP BY anggota_id
                ) bb_tot ON bb_tot.anggota_id = a.id
                GROUP BY a.id
                ORDER BY a.nama ASC
            ");
        $baju = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($baju)) {
            echo "<p class='text-muted'>Tidak ada data Baju PTS.</p>";
        } else {
            echo "<table class='table table-bordered table-sm align-middle'>
        <thead class='table-secondary text-center'>
            <tr>
                <th>#</th>
                <th>Nama Anggota</th>
                <th>Pesanan</th>
                <th>Total Qty</th>
                <th>Total Pesanan (Rp)</th>
                <th>Total Pembayaran (Rp)</th>
                <th>Keterangan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";

            $no = 1;
            $totalQty = 0;
            $totalPesanan = 0;
            $totalBayar = 0;

            foreach ($baju as $r) {
                $qty = (int)$r['total_qty'];
                $pesanan = htmlspecialchars($r['pesanan']);
                $pesananRp = (int)$r['total_pesanan'];
                $bayar = (int)$r['total_bayar'];

                $totalQty += $qty;
                $totalPesanan += $pesananRp;
                $totalBayar += $bayar;

                // Status pembayaran
                if ($bayar >= $pesananRp && $pesananRp > 0) {
                    $status = "<span class='text-success fw-bold'>Lunas</span>";
                } elseif ($bayar > 0 && $bayar < $pesananRp) {
                    $status = "<span class='text-warning fw-bold'>Cicilan</span>";
                } else {
                    $status = "<span class='text-danger fw-bold'>Belum Bayar</span>";
                }

                // Link hanya muncul jika ada pembayaran
                $link = ($bayar > 0)
                    ? "<a href='baju_detail.php?id={$r['anggota_id']}'>Lihat detail</a>"
                    : "-";

                echo "<tr>
            <td class='text-center'>{$no}</td>
            <td>" . htmlspecialchars($r['nama']) . "</td>
            <td>" . ($pesanan ?: '-') . "</td>
            <td class='text-center'>" . number_format($qty, 0, ',', '.') . "</td>
            <td class='text-end'>" . number_format($pesananRp, 0, ',', '.') . "</td>
            <td class='text-end'>" . number_format($bayar, 0, ',', '.') . "</td>
            <td class='text-center'>{$link}</td>
            <td class='text-center'>{$status}</td>
        </tr>";

                $no++;
            }

            echo "<tr class='table-light fw-bold'>
        <td colspan='3' class='text-center'>TOTAL</td>
        <td class='text-center'>" . number_format($totalQty, 0, ',', '.') . "</td>
        <td class='text-end'>" . number_format($totalPesanan, 0, ',', '.') . "</td>
        <td class='text-end'>" . number_format($totalBayar, 0, ',', '.') . "</td>
        <td colspan='2'></td>
    </tr>
    </tbody>
    </table>";
        }


        /* ================= KUPON ================= */
        echo "<h4 class='section-title mt-4'>Kupon PTS</h4>";


        /* ============================================================
            4. DATA KUPON
            ============================================================ */

        $stmt = $pdo->query("
                SELECT 
                    k.id AS id_kupon,
                    k.nama,
                    k.nomor_kupon,
                    k.jumlah AS jumlah_kupon,
                    k.kembali AS kembali_kupon,
                    (k.jumlah - k.kembali) * 50000 AS total_tagihan,
                    COALESCE(SUM(bk.bayar), 0) AS total_bayar
                FROM kupon k
                LEFT JOIN bayar_kupon bk ON k.id = bk.id_kupon
                GROUP BY k.id, k.nama, k.nomor_kupon, k.jumlah, k.kembali
                ORDER BY k.nama ASC
            ");
        $kupon = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($kupon)) {
            echo "<p class='text-muted'>Tidak ada data Kupon.</p>";
        } else {
            echo "<table class='table table-bordered table-sm align-middle'>
    <thead class='table-secondary text-center'>
        <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Nomor Kupon</th>
            <th>Jumlah</th>
            <th>Kembali</th>
            <th>Total Tagihan (Rp)</th>
            <th>Total Bayar (Rp)</th>
            <th>Keterangan</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>";

            $no = 1;
            $tJumlah = $tKembali = $tTagihan = $tBayar = 0;

            foreach ($kupon as $r) {
                $jumlah = (int)$r['jumlah_kupon'];
                $kembali = (int)$r['kembali_kupon'];
                $tagihan = (int)$r['total_tagihan'];
                $bayar = (int)$r['total_bayar'];

                $tJumlah += $jumlah;
                $tKembali += $kembali;
                $tTagihan += $tagihan;
                $tBayar += $bayar;

                // status
                if ($bayar >= $tagihan && $tagihan > 0) {
                    $status = "<span class='text-success fw-bold'>Lunas</span>";
                } elseif ($bayar > 0) {
                    $status = "<span class='text-warning fw-bold'>Cicilan</span>";
                } else {
                    $status = "<span class='text-danger fw-bold'>Belum Bayar</span>";
                }

                // link detail
                $link = ($bayar > 0)
                    ? "<a href='kupon_detail.php?id={$r['id_kupon']}'>Lihat detail</a>"
                    : "-";

                echo "<tr>
            <td class='text-center'>{$no}</td>
            <td>" . htmlspecialchars($r['nama']) . "</td>
            <td class='text-center'>" . htmlspecialchars($r['nomor_kupon']) . "</td>
            <td class='text-center'>" . number_format($jumlah, 0, ',', '.') . "</td>
            <td class='text-center'>" . number_format($kembali, 0, ',', '.') . "</td>
            <td class='text-end'>" . number_format($tagihan, 0, ',', '.') . "</td>
            <td class='text-end'>" . number_format($bayar, 0, ',', '.') . "</td>
            <td class='text-center'>{$link}</td>
            <td class='text-center'>{$status}</td>
        </tr>";
                $no++;
            }

            echo "<tr class='table-light fw-bold'>
        <td colspan='3' class='text-center'>TOTAL</td>
        <td class='text-center'>" . number_format($tJumlah, 0, ',', '.') . "</td>
        <td class='text-center'>" . number_format($tKembali, 0, ',', '.') . "</td>
        <td class='text-end'>" . number_format($tTagihan, 0, ',', '.') . "</td>
        <td class='text-end'>" . number_format($tBayar, 0, ',', '.') . "</td>
        <td colspan='2'></td>
    </tr>
    </tbody>
    </table>";
        }


        /* ================= SUMBANGAN DANA ================= */
        echo "<h4 class='section-title mt-4'>Sumbangan Dana</h4>";


        /* ============================================================
            5. DATA SUMBANGAN DANA
            ============================================================ */
        $stmt = $pdo->query("
                SELECT id, nama, jumlah, keterangan, photo, tanggal
                FROM sumbangan
                WHERE jenis = 'dana'
                ORDER BY tanggal DESC
            ");
        $dana = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($dana)) {
            echo "<p class='text-muted'>Tidak ada data Sumbangan Dana.</p>";
        } else {
            echo "<table class='table table-bordered table-sm align-middle'>
    <thead class='table-secondary text-center'>
        <tr>
            <th>#</th>
            <th>Nama</th>
            <th>Tanggal</th>
            <th>Jumlah (Rp)</th>
            <th>Keterangan</th>
            <th>Bukti</th>
        </tr>
    </thead>
    <tbody>";

            $no = 1;
            $totalJumlah = 0;

            foreach ($dana as $r) {
                $jumlah = (int)$r['jumlah'];
                $totalJumlah += $jumlah;

                // status
                if ($jumlah > 0) {
                    $status = "<span class='text-success fw-bold'>Terverifikasi</span>";
                } else {
                    $status = "<span class='text-danger fw-bold'>Belum Ada</span>";
                }

                // link bukti
                $link = "-";
                if (!empty($r['photo']) && file_exists(__DIR__ . "/uploads/" . $r['photo'])) {
                    $link = "<a href='uploads/" . htmlspecialchars($r['photo']) . "' target='_blank'>Lihat</a>";
                }

                // bersihkan keterangan dari HTML
                $ket = trim(html_entity_decode(strip_tags($r['keterangan'] ?? '-')));

                echo "<tr>
            <td class='text-center'>{$no}</td>
            <td>" . htmlspecialchars($r['nama']) . "</td>
            <td class='text-center'>" . date('d/m/Y', strtotime($r['tanggal'])) . "</td>
            <td class='text-end'>" . number_format($jumlah, 0, ',', '.') . "</td>
            <td>" . htmlspecialchars($ket) . "</td>
            <td class='text-center'>{$link}</td>
        </tr>";
                $no++;
            }

            echo "<tr class='table-light fw-bold'>
        <td colspan='3' class='text-center'>TOTAL</td>
        <td class='text-end'>" . number_format($totalJumlah, 0, ',', '.') . "</td>
        <td colspan='2'></td>
    </tr>
    </tbody>
    </table>";
        }


        /* ================= TOR-TOR ================= */
        echo "<h4 class='section-title mt-4'>Tor-tor</h4>";

        /* ============================================================
            6. DATA TOR-TOR
            ============================================================ */

        $stmt = $pdo->query("
                SELECT id, nama, jumlah, keterangan, photo, tanggal
                FROM sumbangan
                WHERE jenis = 'tor-tor'
                ORDER BY tanggal DESC
            ");
        $tortor = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($tortor)) {
            echo "<p class='text-muted'>Tidak ada data Tor-tor.</p>";
        } else {
            echo "<table class='table table-bordered table-sm align-middle'>
    <thead class='table-secondary text-center'>
        <tr>
            <th>#</th>
            <th>Tor-tor</th>
            <th>Jumlah (Rp)</th>
            <th>Keterangan</th>
            <th>Tanggal</th>
        </tr>
    </thead>
    <tbody>";

            $no = 1;
            $totalJumlah = 0;

            foreach ($tortor as $r) {
                $jumlah = (int)$r['jumlah'];
                $totalJumlah += $jumlah;

                // link bukti (cek jika file ada di folder uploads)
                $link = "-";
                if (!empty($r['photo']) && file_exists(__DIR__ . "/uploads/" . $r['photo'])) {
                    $link = "<a href='uploads/" . htmlspecialchars($r['photo']) . "' target='_blank'>Lihat</a>";
                }

                // bersihkan teks keterangan
                $ket = trim(html_entity_decode(strip_tags($r['keterangan'] ?? '-')));

                echo "<tr>
            <td class='text-center'>{$no}</td>
            <td>" . htmlspecialchars($r['nama']) . "</td>
            <td class='text-end'>" . number_format($jumlah, 0, ',', '.') . "</td>
            <td>" . htmlspecialchars($ket) . "</td>
            <td class='text-center'>" . date('d/m/Y', strtotime($r['tanggal'])) . "</td>
        </tr>";
                $no++;
            }

            echo "<tr class='table-light fw-bold'>
        <td colspan='2' class='text-center'>TOTAL</td>
        <td class='text-end'>" . number_format($totalJumlah, 0, ',', '.') . "</td>
        <td colspan='3'></td>
    </tr>
    </tbody>
    </table>";
        }
        /* ================= SILUA (satu tabel, urut dan subtotal) ================= */
        echo "<h4 class='section-title'>Dana Silua</h4>";

        /* ============================================================
        1. DATA SILUA
        ============================================================ */
        $stmt = $pdo->query("
            SELECT 
                a.id,
                a.nama,
                a.jumlah AS total_silua,
                a.keterangan,
                COALESCE(SUM(b.jumlah), 0) AS total_bayar
            FROM silua a
            LEFT JOIN bayar_silua b ON a.id = b.silua_id
            GROUP BY a.id, a.nama, a.jumlah, a.keterangan
            ORDER BY a.keterangan, a.nama ASC
        ");
        $silua = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($silua)) {
            echo "<p class='text-muted'>Tidak ada data silua.</p>";
        } else {
            // Urutan kelompok tetap
            $orders = ['hula' => 'Hula-hula', 'boru' => 'Boru', 'bere' => 'Bere & Ibebere'];
            $grouped = ['hula' => [], 'boru' => [], 'bere' => []];

            foreach ($silua as $r) {
                $key = strtolower(trim($r['keterangan']));
                if (isset($grouped[$key])) {
                    $grouped[$key][] = $r;
                }
            }

            echo "<table class='table table-bordered table-sm align-middle'>";
            echo "<thead class='table-secondary text-center'>
            <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Silua (Rp)</th>
                <th>Total Bayar (Rp)</th>
                <th>Keterangan</th>
                <th>Status</th>
            </tr>
          </thead><tbody>";

            $i = 1;
            $grandSilua = 0;
            $grandBayar = 0;

            foreach ($orders as $key => $label) {
                $rows = $grouped[$key];
                if (empty($rows)) continue;

                echo "<tr class='table-light fw-bold'><td colspan='6'>{$label}</td></tr>";

                $subSilua = 0;
                $subBayar = 0;

                foreach ($rows as $r) {
                    $sil = (int)$r['total_silua'];
                    $byr = (int)$r['total_bayar'];
                    $subSilua += $sil;
                    $subBayar += $byr;

                    if ($byr >= $sil && $sil > 0) {
                        $status = "<span class='text-success fw-bold'>Lunas</span>";
                    } elseif ($byr > 0) {
                        $status = "<span class='text-warning fw-bold'>Cicilan</span>";
                    } else {
                        $status = "<span class='text-danger fw-bold'>Belum Bayar</span>";
                    }

                    $link = "<a href='silua_detail.php?id=" . urlencode($r['id']) . "'>Lihat detail</a>";

                    echo "<tr>
                    <td class='text-center'>{$i}</td>
                    <td>" . htmlspecialchars($r['nama']) . "</td>
                    <td class='text-end'>" . number_format($sil, 0, ',', '.') . "</td>
                    <td class='text-end'>" . number_format($byr, 0, ',', '.') . "</td>
                    <td class='text-center'>{$link}</td>
                    <td class='text-center'>{$status}</td>
                  </tr>";
                    $i++;
                }

                // subtotal per kelompok
                echo "<tr class='fw-bold'>
                <td colspan='2' class='text-center'>SUBTOTAL {$label}</td>
                <td class='text-end'>" . number_format($subSilua, 0, ',', '.') . "</td>
                <td class='text-end'>" . number_format($subBayar, 0, ',', '.') . "</td>
                <td colspan='2'></td>
              </tr>";

                $grandSilua += $subSilua;
                $grandBayar += $subBayar;
            }

            // total seluruhnya
            echo "<tr class='table-light fw-bold'>
            <td colspan='2' class='text-center'>TOTAL Dana Silua</td>
            <td class='text-end'>" . number_format($grandSilua, 0, ',', '.') . "</td>
            <td class='text-end'>" . number_format($grandBayar, 0, ',', '.') . "</td>
            <td colspan='2'></td>
          </tr>";

            echo "</tbody></table>";
        }

        ?>
    </div>
    <script>
        /* ---------- Helpers ---------- */
        function getTodayString() {
            const d = new Date();
            return `${d.getFullYear()}${String(d.getMonth()+1).padStart(2,'0')}${String(d.getDate()).padStart(2,'0')}`;
        }

        // Coba parse string angka "1.234.567" atau "Rp 1.234.567" => number 1234567
        function parseIndoNumber(str) {
            if (typeof str !== 'string') return null;
            // hapus non digit, minus; kecuali koma/dot (kita akan remove dot)
            // tapi lebih aman: hapus semua selain digits and minus
            let s = str.replace(/\s+/g, ''); // remove spaces
            s = s.replace(/Rp|rp|,00/g, ''); // hapus Rp if any
            // jika ada dot sebagai thousands separator, hapus semua dot
            s = s.replace(/\./g, '');
            // ubah koma desimal jadi titik (jika ada)
            s = s.replace(/,/g, '.');
            // sekarang jika s hanya mengandung digits, minus or dot -> parseFloat
            if (/^[\d\.\-]+$/.test(s)) {
                const n = Number(s);
                if (!isNaN(n)) return n;
            }
            return null;
        }

        /* ---------- EXPORT PDF ---------- */
        function exportPDF() {
            try {
                const sections = document.querySelectorAll("h4.section-title");
                if (!sections.length) {
                    alert("Tidak menemukan header laporan (h4.section-title).");
                    return;
                }

                const content = [];

                sections.forEach((h4, sIdx) => {
                    // page break before except first
                    if (sIdx > 0) content.push({
                        text: '',
                        pageBreak: 'before'
                    });

                    // judul section
                    content.push({
                        text: h4.innerText.trim(),
                        style: 'header'
                    });

                    // baca isi sampai next h4.section-title
                    let next = h4.nextElementSibling;
                    while (next && !next.matches("h4.section-title")) {
                        if (next.tagName === 'TABLE') {
                            // build body rows, dan cari maxCols
                            const rows = Array.from(next.querySelectorAll('tr'));
                            if (rows.length === 0) {
                                next = next.nextElementSibling;
                                continue;
                            }

                            let maxCols = 0;
                            const parsedRows = rows.map(tr => {
                                const cells = Array.from(tr.querySelectorAll('th, td'));
                                if (cells.length > maxCols) maxCols = cells.length;
                                return cells;
                            });

                            const body = parsedRows.map(cells => {
                                const row = [];
                                for (let ci = 0; ci < maxCols; ci++) {
                                    const cell = cells[ci];
                                    if (!cell) {
                                        row.push(''); // important: do not push undefined
                                        continue;
                                    }
                                    const a = cell.querySelector('a');
                                    if (a && a.href) {
                                        row.push({
                                            text: (a.innerText || a.href).trim(),
                                            link: a.href,
                                            color: 'blue',
                                            decoration: 'underline'
                                        });
                                    } else {
                                        // ambil text, trim, replace multiple spaces / newline
                                        let txt = cell.innerText.replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim();
                                        row.push(txt);
                                    }
                                }
                                return row;
                            });

                            // determine headerRows: if first tr has any TH elements => headerRows = 1
                            const firstRowHasTh = !!rows[0].querySelectorAll('th').length;
                            const headerRows = firstRowHasTh ? 1 : 0;

                            content.push({
                                table: {
                                    headerRows: headerRows,
                                    widths: Array(maxCols).fill('*'),
                                    body: body
                                },
                                layout: 'lightHorizontalLines',
                                margin: [0, 6, 0, 10]
                            });
                        } else {
                            // teks / paragraph etc
                            const txt = next.innerText ? next.innerText.trim() : '';
                            if (txt) content.push({
                                text: txt,
                                margin: [0, 4, 0, 4]
                            });
                        }
                        next = next.nextElementSibling;
                    }
                });

                const docDefinition = {
                    info: {
                        title: 'Laporan Pemasukan'
                    },
                    content,
                    styles: {
                        header: {
                            fontSize: 14,
                            bold: true,
                            margin: [0, 8, 0, 8]
                        },
                    },
                    pageSize: 'A4',
                    pageMargins: [30, 40, 30, 40]
                };

                const filename = `laporan_pemasukan_${getTodayString()}.pdf`;
                pdfMake.createPdf(docDefinition).download(filename);
            } catch (err) {
                console.error("exportPDF error:", err);
                alert("Terjadi kesalahan saat membuat PDF. Cek console untuk detail.");
            }
        }

        /* ---------- EXPORT EXCEL ---------- */
        function exportExcel() {
            try {
                const workbook = XLSX.utils.book_new();
                const sections = document.querySelectorAll("h4.section-title");
                if (!sections.length) {
                    alert("Tidak menemukan header laporan (h4.section-title).");
                    return;
                }

                sections.forEach((h4, sIdx) => {
                    // ambil html sampai h4 berikutnya
                    let next = h4.nextElementSibling;
                    // collect tables under this section (bisa 0..n)
                    while (next && !next.matches("h4.section-title")) {
                        if (next.tagName === 'TABLE') {
                            const table = next;
                            // parse rows and cells into array-of-arrays
                            const trs = Array.from(table.querySelectorAll('tr'));
                            if (!trs.length) {
                                next = next.nextElementSibling;
                                continue;
                            }

                            const aoa = [];
                            const linkMap = {}; // 'r_c' => href

                            trs.forEach((tr, rIdx) => {
                                const cells = Array.from(tr.querySelectorAll('th, td'));
                                const rowArr = [];
                                cells.forEach((cell, cIdx) => {
                                    // if anchor exists, capture href and text
                                    const a = cell.querySelector('a');
                                    let text = cell.innerText ? cell.innerText.replace(/\r?\n|\r/g, ' ').replace(/\s+/g, ' ').trim() : '';
                                    if (a && a.href) {
                                        // simpan link, gunakan text sebagai value
                                        linkMap[`${rIdx}_${cIdx}`] = a.href;
                                        text = text || a.href;
                                    }

                                    // coba parse angka indo -> number
                                    const maybeNum = parseIndoNumber(text);
                                    if (maybeNum !== null) {
                                        rowArr.push(maybeNum);
                                    } else {
                                        rowArr.push(text);
                                    }
                                });
                                aoa.push(rowArr);
                            });

                            // pad short rows so matrix is rectangular
                            let maxCols = 0;
                            aoa.forEach(r => {
                                if (r.length > maxCols) maxCols = r.length;
                            });
                            aoa.forEach(r => {
                                while (r.length < maxCols) r.push('');
                            });

                            // create sheet and then add hyperlinks & types
                            const ws = XLSX.utils.aoa_to_sheet(aoa);

                            // assign hyperlink objects and ensure numeric cells are numbers
                            for (let r = 0; r < aoa.length; r++) {
                                for (let c = 0; c < maxCols; c++) {
                                    const addr = XLSX.utils.encode_cell({
                                        r,
                                        c
                                    });
                                    const cell = ws[addr];
                                    if (!cell) continue;
                                    // if entry is numeric in aoa, ensure cell.t = 'n'
                                    const v = aoa[r][c];
                                    if (typeof v === 'number') {
                                        ws[addr].t = 'n';
                                        ws[addr].v = v;
                                    }
                                    const key = `${r}_${c}`;
                                    if (linkMap[key]) {
                                        ws[addr].l = {
                                            Target: linkMap[key],
                                            Tooltip: 'Buka link'
                                        };
                                    }
                                }
                            }

                            // sheet name: combine section name + index, trim illegal chars and length
                            const baseName = h4.innerText.trim().substring(0, 25).replace(/[\[\]\*\/\\\?\:]/g, '');
                            const sheetName = (baseName + (sIdx > 0 ? `_${sIdx}` : '')).substring(0, 31);

                            XLSX.utils.book_append_sheet(workbook, ws, sheetName);
                        }
                        next = next.nextElementSibling;
                    }
                });

                const filename = `laporan_pemasukan_${getTodayString()}.xlsx`;
                XLSX.writeFile(workbook, filename);
            } catch (err) {
                console.error("exportExcel error:", err);
                alert("Terjadi kesalahan saat membuat Excel. Cek console untuk detail.");
            }
        }

        /* ---------- Event listeners ---------- */
        document.getElementById("exportPDF").addEventListener("click", exportPDF);
        document.getElementById("exportExcel").addEventListener("click", exportExcel);
    </script>


</body>

</html>