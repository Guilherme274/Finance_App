<?php
$host = '127.0.0.1';
$user = 'root';
$password = 'root';
try {
    $pdo = new PDO("mysql:host=$host;port=3306", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS FinanceApp");
    echo "Database created successfully\n";
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage() . "\n");
}
