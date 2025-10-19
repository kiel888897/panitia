<?php
header('Content-Type: application/json');

// CORS headers - WAJIB jika frontend beda domain
header("Access-Control-Allow-Origin: *"); // atau ganti * dengan domain spesifik
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../db.php';

// Ambil parameter dari URL
$restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;

// Validasi parameter
if (!$restaurant_id) {
    echo json_encode([
        'success' => false,
        'message' => 'restaurant_id is required'
    ]);
    exit();
}

// Query data menu
try {
    $stmt = $pdo->prepare("SELECT * FROM menus WHERE restaurant_id = :restaurant_id ORDER BY id ASC");
    $stmt->bindParam(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
    $stmt->execute();
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Base URL untuk image
    $baseUrl = "https://roterestaurants.com/admin/uploads/menus/";

    // Format ulang photo menjadi array berisi full URL
    foreach ($menus as &$menu) {
        $photos = json_decode($menu['photo'], true);
        $menu['photo_urls'] = [];

        if (is_array($photos)) {
            foreach ($photos as $photo) {
                $menu['photo_urls'][] = $baseUrl . $photo;
            }
        }
    }
    // Format respons JSON
    echo json_encode([
        'success' => true,
        'data' => $menus
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
