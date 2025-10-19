<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("DELETE FROM iuran WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header('Location: toktok-proses.php?deleted=1');
    exit;
} else {
    header('Location: toktok-proses.php?error=1');
    exit;
}
