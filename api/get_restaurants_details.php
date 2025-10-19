<?php
header('Content-Type: application/json');

// CORS headers - WAJIB jika frontend beda domain
header("Access-Control-Allow-Origin: *"); // atau ganti * dengan domain spesifik
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../db.php';

// Ambil parameter dari URL (via rewrite)
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if ($slug) {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE slug  = :slug  LIMIT 1");
    $stmt->bindParam(':slug', $slug);
    $stmt->execute();
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    $baseUrl = "https://roterestaurants.com/admin/uploads/restaurants/";
    if ($restaurant) {
        $restaurant['photo_url'] = !empty($restaurant['photo']) ? $baseUrl . $restaurant['photo'] : null;
    }

    if ($restaurant) {
        echo json_encode([
            'success' => true,
            'data' => $restaurant
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Restaurant not found'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Name parameter is required'
    ]);
}
