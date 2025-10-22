<?php
require_once 'db.php';

// Ambil semua data kupon
$stmt = $pdo->query("
    SELECT id, nama, nomor_kupon, jumlah, kembali, created_at
    FROM kupon
    ORDER BY created_at DESC
");
$kupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kupon | Panitia Bona Taon PTS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen font-sans">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-ticket-detailed text-blue-500"></i> Data Kupon
            </h1>
            <a href="kupon_add.php"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-md flex items-center gap-2 transition">
                <i class="bi bi-plus-circle"></i> Tambah Kupon
            </a>
        </div>

        <!-- Notifikasi sukses -->
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg mb-6 shadow">
                ✅ Data kupon berhasil disimpan!
            </div>
        <?php endif; ?>

        <!-- Tabel -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
            <table class="min-w-full text-sm text-gray-700">
                <thead class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Nomor Kupon</th>
                        <th class="px-4 py-3 text-center">Jumlah</th>
                        <th class="px-4 py-3 text-center">Kembali</th>
                        <th class="px-4 py-3 text-center">Total Tagihan</th>
                        <th class="px-4 py-3 text-center">Tanggal</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($kupons)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-6 text-gray-500 italic">Belum ada data kupon</td>
                        </tr>
                    <?php else: ?>
                        <?php
                        $i = 1;
                        foreach ($kupons as $row):
                            $tagihan = ($row['jumlah'] - $row['kembali']) * 50000;
                        ?>
                            <tr class="hover:bg-gray-50 border-b transition">
                                <td class="px-4 py-3 font-medium text-gray-600"><?= $i++ ?></td>
                                <td class="px-4 py-3 font-semibold text-gray-800"><?= htmlspecialchars($row['nama']) ?></td>
                                <td class="px-4 py-3"><?= nl2br(htmlspecialchars($row['nomor_kupon'])) ?></td>
                                <td class="px-4 py-3 text-center"><?= (int)$row['jumlah'] ?></td>
                                <td class="px-4 py-3 text-center"><?= (int)$row['kembali'] ?></td>
                                <td class="px-4 py-3 text-center text-blue-600 font-semibold">Rp <?= number_format($tagihan, 0, ',', '.') ?></td>
                                <td class="px-4 py-3 text-center"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <a href="kupon_edit.php?id=<?= $row['id'] ?>"
                                        class="inline-flex items-center px-3 py-1.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>