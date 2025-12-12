<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
include_once __DIR__ . '/../includes/helpers.php';

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom     = trim($_POST['nom'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $sujet   = trim($_POST['sujet'] ?? '');
    $contenu = trim($_POST['message'] ?? '');

    if ($nom === "" || $email === "" || $contenu === "") {
        $message = "Veuillez remplir tous les champs obligatoires.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO aidecontact (contenu) VALUES (:contenu)");
        $fullMessage = "Nom: $nom\nEmail: $email\nSujet: $sujet\nMessage: $contenu";
        $stmt->execute(['contenu' => $fullMessage]);
        $success = true;
        $message = "Merci ! Votre message a bien été envoyé.";
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Aide & Contact</title>
<link rel="stylesheet" href="/public/css/aidecontact.css">
</head>
<body>
<section class="contact">
    <h1>Aide & Contact</h1>
    <div class="contact-wrapper">

        <div class="help-box">
            <h2>Besoin d’un coup de main ?</h2>
            <ul>
                <li><a href="faq.php">Consultez la FAQ</a> — les réponses aux questions les plus courantes.</li>
                <li><a href="reserveevent.php">Comment reserver un événement</a> — guide étape par étape.</li>
                <li><a href="connexion.php">Problème de connexion</a> — réinitialisez votre mot de passe.</li>
            </ul>
            <div class="phone-box">Appelez-nous au <a href="tel:+33612345678">+33 6 12 34 56 78</a></div>
        </div>

        <div class="contact-form">
            <h2>Contactez-nous</h2>
            <?php if($message): ?>
                <div class="contact-message <?= $success?'success':'error' ?>"><?= h($message) ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="text" aria-label="nom" name="nom" placeholder="Votre nom" required>
                <input type="email" aria-label="email" name="email" placeholder="Votre e-mail" required>
                <input type="text" aria-label="sujet" name="sujet" placeholder="Sujet">
                <textarea name="message" aria-label="texto" rows="5" placeholder="Votre message..." required></textarea>
                <button type="submit">Envoyer le message</button>
            </form>
        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
