<?php
header('Content-Type: application/json');


// CORS headers - WAJIB jika frontend beda domain
header("Access-Control-Allow-Origin: *"); // atau ganti * dengan domain spesifik
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../db.php';

try {
    // $sql = "SELECT * FROM restaurants WHERE status='activ'";
    $sql = "
    SELECT r.*, 
           AVG(CASE WHEN rv.approved = 1 THEN rv.rating ELSE NULL END) AS averageRating,
           COUNT(CASE WHEN rv.approved = 1 THEN rv.id ELSE NULL END) AS totalReviews
    FROM restaurants r
    LEFT JOIN reviews rv ON rv.restaurant_id = r.id
    WHERE r.status = 'activ'
";

    $params = [];

    // ID
    if (!empty($_GET['id'])) {
        $sql .= " AND id = :id";
        $params[':id'] = $_GET['id'];
    }

    // Area
    if (!empty($_GET['area'])) {
        $sql .= " AND area LIKE :area";
        $params[':area'] = '%' . $_GET['area'] . '%';
    }

    // Name
    if (!empty($_GET['name'])) {
        $sql .= " AND name LIKE :name";
        $params[':name'] = '%' . $_GET['name'] . '%';
    }

    // Rating minimum
    if (!empty($_GET['rating_min']) && is_numeric($_GET['rating_min'])) {
        $sql .= " AND rating >= :rating_min";
        $params[':rating_min'] = $_GET['rating_min'];
    }

    // Status
    // if (!empty($_GET['status'])) {
    //     $sql .= " AND status = :status";
    //     $params[':status'] = $_GET['status'];
    // }

    // ORDER BY
    $allowedOrderFields = ['name', 'rating', 'created_at']; // biar aman
    $orderby = in_array($_GET['orderby'] ?? '', $allowedOrderFields) ? $_GET['orderby'] : 'id';
    $sort = (strtolower($_GET['sort'] ?? '') === 'desc') ? 'DESC' : 'ASC';

    $sql .= " GROUP BY r.id ORDER BY $orderby $sort";
    // LIMIT
    if (!empty($_GET['limit']) && is_numeric($_GET['limit'])) {
        $sql .= " LIMIT :limit";
    }

    $stmt = $pdo->prepare($sql);

    // Binding
    foreach ($params as $key => &$value) {
        $stmt->bindParam($key, $value);
    }

    if (!empty($_GET['limit']) && is_numeric($_GET['limit'])) {
        $limit = (int) $_GET['limit'];
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $baseUrl = "https://roterestaurants.com/admin/uploads/restaurants/";
    foreach ($data as &$restaurant) {
        $restaurant['photo_url'] = $restaurant['photo'] ? $baseUrl . $restaurant['photo'] : null;
    }

    // Format respons JSON
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
