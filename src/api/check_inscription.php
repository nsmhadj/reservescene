<?php
header('Content-Type: application/json');

// Load database configuration
require_once __DIR__ . '/../../config/database.php';

$type = $_GET['type'] ?? '';
$value = trim($_GET['value'] ?? '');

if ($value === '') {
    echo json_encode(['exists' => false]);
    exit;
}

if ($type === 'login') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM client WHERE login = :v");
} elseif ($type === 'email') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM client WHERE adresse_mail = :v");
} else {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt->execute(['v' => $value]);
$count = $stmt->fetchColumn();

echo json_encode(['exists' => $count > 0]);
