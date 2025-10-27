<?php
require_once 'db.php';

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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Pemasukan PTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

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
        // --- FUNGSI CETAK TABEL (BIAR RAPI) ---
        function renderTable($title, $headers, $rows, $bodyFn)
        {
            echo "<h4 class='section-title'>$title</h4>";
            echo "<table class='table table-bordered table-sm align-middle'>";
            echo "<thead class='table-secondary text-center'><tr>";
            foreach ($headers as $h) echo "<th>$h</th>";
            echo "</tr></thead><tbody>";
            echo $bodyFn($rows);
            echo "</tbody></table>";
        }
        /* ================= SILUA (satu tabel, urut dan subtotal) ================= */
        echo "<h4 class='section-title'>Dana Silua</h4>";

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
            <td colspan='2' class='text-center'>TOTAL SELURUHNYA</td>
            <td class='text-end'>" . number_format($grandSilua, 0, ',', '.') . "</td>
            <td class='text-end'>" . number_format($grandBayar, 0, ',', '.') . "</td>
            <td colspan='2'></td>
          </tr>";

            echo "</tbody></table>";
        }


        /* ================= TOK-TOK RIPE ================= */
        echo "<h4 class='section-title mt-4'>Tok-tok Ripe</h4>";

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

        ?>
    </div>

    <script>
        // ====== EXPORT EXCEL (semua sheet) ======
        document.getElementById("exportExcel").addEventListener("click", function() {
            const wb = XLSX.utils.book_new();
            const tables = document.querySelectorAll("table");
            tables.forEach((tbl, idx) => {
                const ws = XLSX.utils.table_to_sheet(tbl);
                const title = document.querySelectorAll("h4.section-title")[idx].innerText.trim();
                XLSX.utils.book_append_sheet(wb, ws, title.substring(0, 31));
            });
            const today = new Date().toISOString().slice(0, 10);
            XLSX.writeFile(wb, `Laporan_Pemasukan_PTS_${today}.xlsx`);
        });

        // ====== EXPORT PDF (gabung semua tabel) ======
        document.getElementById("exportPDF").addEventListener("click", function() {
            const docContent = [];
            const titles = document.querySelectorAll("h4.section-title");
            titles.forEach((t, i) => {
                docContent.push({
                    text: t.innerText,
                    style: 'sectionHeader',
                    margin: [0, 10, 0, 5]
                });
                const tbl = document.querySelectorAll("table")[i];
                const body = [];
                tbl.querySelectorAll("tr").forEach((row) => {
                    const rowData = [];
                    row.querySelectorAll("th,td").forEach((cell) => {
                        rowData.push(cell.innerText);
                    });
                    body.push(rowData);
                });
                docContent.push({
                    table: {
                        headerRows: 1,
                        body
                    },
                    layout: 'lightHorizontalLines',
                    margin: [0, 0, 0, 10]
                });
            });

            const docDefinition = {
                content: docContent,
                styles: {
                    sectionHeader: {
                        fontSize: 13,
                        bold: true
                    },
                },
                defaultStyle: {
                    fontSize: 9
                },
                pageOrientation: 'landscape',
                pageSize: 'A4'
            };

            const today = new Date().toISOString().slice(0, 10);
            pdfMake.createPdf(docDefinition).download(`Laporan_Pemasukan_PTS_${today}.pdf`);
        });
    </script>
</body>

</html>