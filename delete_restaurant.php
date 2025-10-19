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
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($restaurant) {
        // Hapus foto restoran dari server jika ada
        $restaurant_photo = $restaurant['photo'];
        if ($restaurant_photo && file_exists('uploads/restaurants/' . $restaurant_photo)) {
            unlink('uploads/restaurants/' . $restaurant_photo); // Hapus foto restoran
        }

        // Hapus file menu dari server jika ada
        $menu_file = $restaurant['menu'];
        if ($menu_file && file_exists('uploads/menus/' . $menu_file)) {
            unlink('uploads/menus/' . $menu_file); // Hapus foto menu
        }
        // Ambil daftar menu yang terkait dengan restoran
        $menu_stmt = $pdo->prepare("SELECT * FROM menus WHERE restaurant_id = ?");
        $menu_stmt->execute([$id]);
        $menus = $menu_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hapus foto menu dan data menu untuk setiap menu restoran
        foreach ($menus as $menu) {
            $menu_photos = json_decode($menu['photo'], true); // Foto disimpan dalam format JSON
            if (!empty($menu_photos)) {
                foreach ($menu_photos as $photo) {
                    $menu_photo_path = 'uploads/menus/' . $photo;
                    if (file_exists($menu_photo_path)) {
                        unlink($menu_photo_path); // Hapus foto menu
                    }
                }
            }
            // Hapus data menu dari database
            $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
            $stmt->execute([$menu['id']]);
        }

        // Hapus restoran dari database
        $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
        $stmt->execute([$id]);
    }
}

// Redirect ke halaman daftar restoran
header('Location: restaurants.php');
exit;
