<?php
session_start();
require_once 'db.php';

// Cek jika admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Ambil data restoran dari database
    $stmt = $pdo->prepare("SELECT * FROM category WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($category) {
        // Hapus foto restoran dari server jika ada
        $category_photo = $category['photo'];
        if ($category_photo && file_exists('uploads/categorys/' . $category_photo)) {
            unlink('uploads/categorys/' . $category_photo); // Hapus foto restoran
        }
        // Hapus restoran dari database
        $stmt = $pdo->prepare("DELETE FROM category WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Redirect ke halaman daftar restoran
header('Location: category_menu.php');
exit;
