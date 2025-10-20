<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("DELETE FROM sumbangan WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header('Location: sumbangan.php?deleted=1');
    exit;
} else {
    header('Location: sumbangan.php?error=1');
    exit;
}
