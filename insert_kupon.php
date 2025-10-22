<?php
require_once 'db.php';

// Ambil daftar nama anggota untuk datalist
$stmt = $pdo->prepare("SELECT nama FROM anggotas ORDER BY nama ASC");
$stmt->execute();
$hulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inisialisasi pesan
$success = '';
$error = '';

// Simpan data kupon jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $nomor_kupon = trim($_POST['nomor_kupon']);
    $jumlah = (int)$_POST['jumlah'];

    // 🔍 Cek apakah nama sudah ada di tabel kupon
    $check = $pdo->prepare("SELECT COUNT(*) FROM kupon WHERE nama = ?");
    $check->execute([$nama]);
    $exists = $check->fetchColumn();

    if ($exists > 0) {
        $error = "Nama <strong>$nama</strong> sudah memiliki data kupon! - Silakan hubungi panitia.";
    } else {
        // 💾 Simpan data baru
        $stmt = $pdo->prepare("
            INSERT INTO kupon (nama, nomor_kupon, jumlah,  created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$nama, $nomor_kupon, $jumlah]);


        $success = "Data kupon berhasil disimpan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kupon Bajar PTS</title>

    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-50 via-white to-blue-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl p-8 border border-blue-100">
        <h1 class="flex items-center justify-center gap-3 text-3xl font-extrabold text-blue-700 mb-8">
            <img src="assets/img/logo.png" alt="Logo" class="w-12 h-12 rounded-full shadow-md border border-blue-200" />
            <span class="tracking-wide">Tambah Kupon Baru</span>
        </h1>


        <!-- Notifikasi sukses & error -->
        <?php if (!empty($success)): ?>
            <div class="mb-6 flex items-center gap-2 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg shadow-sm">
                <i class="bi bi-check-circle-fill text-green-600"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="mb-6 flex items-center gap-2 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg shadow-sm">
                <i class="bi bi-exclamation-triangle-fill text-red-600"></i>
                <span><?= ($error) ?></span>
            </div>
        <?php endif; ?>


        <form method="POST" class="space-y-5">

            <!-- Nama -->
            <div>
                <label for="hulaNama" class="block text-sm font-medium text-gray-700 mb-1">Pilih / Ketik Nama</label>
                <input list="hulaList" name="nama" id="hulaNama"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                    placeholder="Pilih atau ketik nama baru" required>
                <datalist id="hulaList">
                    <?php foreach ($hulas as $h): ?>
                        <option value="<?= htmlspecialchars($h['nama']); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <!-- Nomor Kupon -->
            <div>
                <label for="nomor_kupon" class="block text-sm font-medium text-gray-700 mb-1">Nomor Kupon</label>
                <textarea id="nomor_kupon" name="nomor_kupon" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none"
                    placeholder="Masukkan daftar nomor kupon, pisahkan dengan koma" required></textarea>
            </div>

            <!-- Jumlah Kupon -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-1">Jumlah Kupon</label>
                    <input type="number" name="jumlah" id="jumlah" min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        placeholder="0" required>
                </div>

                <!-- Kembali -->
                <!-- <div>
                    <label for="kembali" class="block text-sm font-medium text-gray-700 mb-1">Kembali Kupon</label>
                    <input type="number" name="kembali" id="kembali" min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        placeholder="0" required>
                </div> -->
            </div>

            <!-- Tombol Submit -->
            <div class="pt-4">
                <button type="submit"
                    class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-md">
                    <i class="bi bi-plus-circle"></i> Simpan Kupon
                </button>
            </div>

        </form>
    </div>

</body>

</html>