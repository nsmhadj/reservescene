<?php
// check_user.php
header('Content-Type: application/json');

// Load database configuration
require_once __DIR__ . '/../../config/database.php';

if (!isset($_GET['username'])) {
    echo json_encode(['exists' => false]);
    exit;
}

$username = trim($_GET['username']);

// $pdo is already initialized from config/database.php
try {

    $stmt = $pdo->prepare("
        SELECT login 
        FROM client
        WHERE login = :username OR adresse_mail = :username
        LIMIT 1
    ");
    $stmt->execute(['username' => $username]);

    echo json_encode(['exists' => $stmt->fetch() ? true : false]);
} 
catch (PDOException $e) {
    echo json_encode(['exists' => false]);
}
