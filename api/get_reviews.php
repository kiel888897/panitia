<?php
header('Content-Type: application/json');

// CORS headers - WAJIB jika frontend beda domain
header("Access-Control-Allow-Origin: *"); // atau ganti * dengan domain spesifik
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../db.php';

// Ambil parameter opsional dari URL
$restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Buat query dasar
$sql = "SELECT * FROM reviews WHERE approved = 1";
$params = [];

// Tambah filter jika ada
if ($restaurant_id) {
    $sql .= " AND restaurant_id = :restaurant_id";
    $params[':restaurant_id'] = $restaurant_id;
}

$sql .= " ORDER BY id DESC LIMIT :limit";

// Siapkan statement
$stmt = $pdo->prepare($sql);

// Bind params
if ($restaurant_id) {
    $stmt->bindValue(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

// Eksekusi dan ambil hasil
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tampilkan dalam format JSON
echo json_encode([
    'success' => true,
    'data' => $reviews
]);
