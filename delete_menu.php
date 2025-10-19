<?php
session_start();
require_once 'db.php';

// Cek jika admin sudah login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id']) && isset($_GET['menu_id'])) {
    $id = $_GET['id'];
    $menu_id = $_GET['menu_id'];

    // Ambil data menu dari database
    $stmt = $pdo->prepare("SELECT * FROM menus WHERE id = ?");
    $stmt->execute([$menu_id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($menu) {
        // Ambil foto yang terkait dengan menu
        $photos = json_decode($menu['photo'], true); // Foto disimpan dalam format JSON

        // Hapus file foto dari server jika ada
        if (!empty($photos)) {
            foreach ($photos as $photo) {
                $photo_path = 'uploads/menus/' . $photo;
                if (file_exists($photo_path)) {
                    unlink($photo_path); // Hapus file foto dari server
                }
            }
        }

        // Hapus data menu dari database
        $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
        $stmt->execute([$menu_id]);
    }
}

// Redirect ke halaman menu restaurant
header("Location: menu_restaurant.php?id={$id}");
exit;
