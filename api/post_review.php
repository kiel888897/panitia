<?php
header("Content-Type: application/json");

// CORS headers - WAJIB jika frontend beda domain
header("Access-Control-Allow-Origin: *"); // atau ganti * dengan domain spesifik
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../db.php';

// Mendapatkan input JSON
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['restaurant_id'], $data['customer_name'], $data['rating'], $data['review'])) {
    $restaurant_id = $data['restaurant_id'];
    $customer_name = trim($data['customer_name']);
    $rating = (float) $data['rating'];
    $review = trim($data['review']);

    if ($customer_name && $rating && $review) {
        $stmt = $pdo->prepare("INSERT INTO reviews (restaurant_id, customer_name, rating, review, approved) VALUES (:restaurant_id, :customer_name, :rating, :review, 0)");
        $stmt->execute([
            ':restaurant_id' => $restaurant_id,
            ':customer_name' => $customer_name,
            ':rating' => $rating,
            ':review' => $review
        ]);

        // Kirim respon sukses
        echo json_encode([
            'success' => true,
            'message' => 'Review submitted! Waiting for approval.'
        ]);
    } else {
        // Kirim respon error jika ada data yang kurang
        echo json_encode([
            'success' => false,
            'message' => 'Incomplete data'
        ]);
    }
} else {
    // Kirim respon error jika input tidak valid
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input data'
    ]);
}
