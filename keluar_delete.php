<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("DELETE FROM pengeluaran WHERE id = :id");
    $stmt->execute([':id' => $id]);

    header('Location: keluar-proses.php?deleted=1');
    exit;
} else {
    header('Location: keluar-proses.php?error=1');
    exit;
}
