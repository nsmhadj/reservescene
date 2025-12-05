<?php
require_once 'bootstrap.php' ;
$host   = getenv('DB_HOST') ;
$dbname = getenv('DB_NAME') ;
$dbuser = getenv('DB_USER') ;
$dbpass = getenv('DB_PASS');
$dbport = getenv('DB_PORT') ;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$dbport;dbname=$dbname;charset=utf8",
        $dbuser,
        $dbpass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage()));
}
