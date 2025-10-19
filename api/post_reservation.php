<?php
require_once '../db.php'; // Pastikan path ini sesuai struktur folder kamu

header("Content-Type: application/json");

// CORS headers - WAJIB jika frontend beda domain
header("Access-Control-Allow-Origin: *"); // atau ganti * dengan domain spesifik
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Ambil data JSON dari body request
$data = json_decode(file_get_contents('php://input'), true);

// Validasi data
if (
    isset(
        $data['restaurant_id'],
        $data['customer_name'],
        $data['customer_email'],
        $data['customer_phone'],
        $data['reservation_date'],
        $data['reservation_time'],
        $data['number_of_people']
    )
) {
    try {
        $stmt = $pdo->prepare("INSERT INTO reservations (
            restaurant_id, customer_name, customer_email, customer_phone,
            reservation_date, reservation_time, number_of_people, special_request
        ) VALUES (
            :restaurant_id, :customer_name, :customer_email, :customer_phone,
            :reservation_date, :reservation_time, :number_of_people, :special_request
        )");

        $stmt->execute([
            ':restaurant_id' => $data['restaurant_id'],
            ':customer_name' => trim($data['customer_name']),
            ':customer_email' => trim($data['customer_email']),
            ':customer_phone' => trim($data['customer_phone']),
            ':reservation_date' => $data['reservation_date'],
            ':reservation_time' => $data['reservation_time'],
            ':number_of_people' => (int)$data['number_of_people'],
            ':special_request' => isset($data['special_request']) ? trim($data['special_request']) : null
        ]);

        echo json_encode(['success' => true, 'message' => 'Reservation submitted!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Incomplete data']);
}
