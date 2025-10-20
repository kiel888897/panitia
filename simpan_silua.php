<?php
session_start();
require_once 'db.php';

// Pastikan admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Pastikan form dikirim dengan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan rapikan input
    $nama       = trim($_POST['nama'] ?? '');
    $jumlah     = trim($_POST['jumlah'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    // Validasi sederhana
    if ($nama && $jumlah && is_numeric($jumlah) && $keterangan) {
        try {
            // Query insert dengan positional placeholder (?)
            $stmt = $pdo->prepare("
                INSERT INTO silua (nama, jumlah, keterangan)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$nama, $jumlah, $keterangan]);

            // Redirect sukses ke tab terkait
            header("Location: silua.php?saved=1&tab={$keterangan}");
            exit;
        } catch (PDOException $e) {
            // Jika gagal, catat error ke log
            error_log('Error simpan_silua: ' . $e->getMessage());
            header('Location: silua.php?error=db_error');
            exit;
        }
    } else {
        // Input tidak valid
        header('Location: silua.php?error=invalid_data');
        exit;
    }
} else {
    // Jika bukan metode POST
    header('Location: silua.php?error=invalid_request');
    exit;
}
