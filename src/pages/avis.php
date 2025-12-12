<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../includes/helpers.php';


$user = null;
if (!empty($_SESSION['user'])) {
    $stmt = $pdo->prepare("SELECT login FROM client WHERE login = :login LIMIT 1");
    $stmt->execute(['login' => $_SESSION['user']]);
    $user = $stmt->fetch();
}


$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['avis_text'])) {
    $avis_text = trim($_POST['avis_text']);
    if ($avis_text === "") {
        $message = "Veuillez écrire un avis avant d'envoyer.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO avis (contenu) VALUES (:contenu)");
        $stmt->execute(['contenu' => $avis_text]);
        $success = true;
        $message = "Merci ! Votre avis a bien été enregistré.";
    }
}


$stmt = $pdo->prepare("SELECT id_avis, contenu FROM avis ORDER BY id_avis DESC");
$stmt->execute();
$avis_list = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Avis des utilisateurs</title>
<link rel="stylesheet" href="/public/css/avis.css">
</head>
<body>
<main class="avis-page">
    <section class="avis-container">
        <h1 class="title">Les avis de nos utilisateurs</h1>

        <?php if ($message): ?>
        <div class="avis-message <?= $success ? 'success' : 'error' ?>">
            <?= h($message) ?>
        </div>
        <?php endif; ?>

        <div class="avis-list">
            <?php if ($avis_list): ?>
                <?php foreach ($avis_list as $avis): ?>
                    <div class="avis-item">
                        <p><?= nl2br(h($avis['contenu'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun avis pour le moment.</p>
            <?php endif; ?>
        </div>

        <div class="avis-form">
            <h2>Donnez votre avis</h2>
            <form method="post">
                <textarea aria-label="texte" name="avis_text" placeholder="Écrivez votre avis ici..." required></textarea>
                <button type="submit">Envoyer</button>
            </form>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
