<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: silua.php?error=invalid_id');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM silua WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header('Location: silua.php?deleted=1');
    exit;
} catch (PDOException $e) {
    error_log('Error delete_silua: ' . $e->getMessage());
    header('Location: silua.php?error=db_error');
    exit;
}
