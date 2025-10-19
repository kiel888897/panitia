<?php
header('Content-Type: application/json');

// CORS headers - WAJIB jika frontend beda domain
header("Access-Control-Allow-Origin: *"); // atau ganti * dengan domain spesifik
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../db.php';

// Ambil parameter opsional dari URL
$day = isset($_GET['day']) ? $_GET['day'] : null;

// Buat query dasar
$sql = "SELECT * FROM events WHERE status = 1";
$params = [];

// Tambah filter jika ada
if ($day) {
    $sql .= " AND day = :day";
    $params[':day'] = $day;
}

$sql .= " ORDER BY id DESC";

// Siapkan statement
$stmt = $pdo->prepare($sql);

// Bind params

if ($day) {
    $stmt->bindValue(':day', $day, PDO::PARAM_STR);
}

// Eksekusi dan ambil hasil
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tampilkan dalam format JSON
echo json_encode([
    'success' => true,
    'data' => $events
]);
