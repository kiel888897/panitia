<?php
$host = 'localhost';
$dbname = 'danapts';
$user = 'root';
$pass = ''; // sesuaikan dengan password MySQL-mu

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // Atur error mode agar menampilkan exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
