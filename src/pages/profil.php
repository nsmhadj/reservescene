<?php
session_start();

// Traiter la déconnexion
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

// Rediriger si non connecté
if (empty($_SESSION['user'])) {
    header('Location: /connexion.php?redirect=' . urlencode('profil.php'));
    exit;
}

// Load database configuration
require_once __DIR__ . '/../../config/database.php';

include_once __DIR__ . '/../includes/helpers.php';

// Récupérer infos utilisateur
$stmt = $pdo->prepare("SELECT login, nom_client, adresse_mail, tel_client, sexe_client, solde, date_creation 
                        FROM client WHERE login = :login LIMIT 1");
$stmt->execute(['login' => $_SESSION['user']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: /connexion.php');
    exit;
}

/* ------------------------------------------------------------------
    TRAITEMENT MODIFICATION PROFIL
--------------------------------------------------------------------*/
$editMessage = "";
$editSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_profile'])) {

    $newLogin  = trim($_POST['login']);
    $newNom    = trim($_POST['nom_client']);
    $newMail   = trim($_POST['adresse_mail']);
    $newTel    = trim($_POST['tel_client']);
    $newSexe   = trim($_POST['sexe_client']);

    if ($newLogin === "" || $newMail === "") {
        $editMessage = "Le login et l'email sont obligatoires.";
    } else {
        // Vérifier si login déjà pris
        $stmt = $pdo->prepare("SELECT login FROM client WHERE login = :l AND login != :old LIMIT 1");
        $stmt->execute(['l' => $newLogin, 'old' => $user['login']]);
        $loginExists = $stmt->fetch();

        if ($loginExists) {
            $editMessage = "Ce login existe déjà.";
        } else {

            // Vérifier email déjà utilisé
            $stmt = $pdo->prepare("SELECT adresse_mail FROM client WHERE adresse_mail = :m AND login != :old LIMIT 1");
            $stmt->execute(['m' => $newMail, 'old' => $user['login']]);
            $mailExists = $stmt->fetch();

            if ($mailExists) {
                $editMessage = "Cet email est déjà utilisé.";
            } else {

                // Mise à jour
                $stmt = $pdo->prepare("UPDATE client SET
                        login = :login,
                        nom_client = :nom,
                        adresse_mail = :mail,
                        tel_client = :tel,
                        sexe_client = :sexe
                    WHERE login = :old_login");

                $stmt->execute([
                    'login' => $newLogin,
                    'nom'   => $newNom,
                    'mail'  => $newMail,
                    'tel'   => $newTel,
                    'sexe'  => $newSexe,
                    'old_login' => $user['login']
                ]);

                $_SESSION['user'] = $newLogin;
                $user['login'] = $newLogin;
                $user['nom_client'] = $newNom;
                $user['adresse_mail'] = $newMail;
                $user['tel_client'] = $newTel;
                $user['sexe_client'] = $newSexe;

                $editSuccess = true;
                $editMessage = "Profil mis à jour avec succès !";
            }
        }
    }
}

/* ------------------------------------------------------------------
    TRAITEMENT MODIFICATION MOT DE PASSE
--------------------------------------------------------------------*/
$passMessage = "";
$passSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_password'])) {

    $oldPass = $_POST['old_password'] ?? "";
    $newPass = $_POST['new_password'] ?? "";
    $confirmPass = $_POST['confirm_password'] ?? "";

    if ($oldPass === "" || $newPass === "" || $confirmPass === "") {
        $passMessage = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT mdp_client FROM client WHERE login = :login");
        $stmt->execute(['login' => $user['login']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($oldPass, $row['mdp_client'])) {
            $passMessage = "Ancien mot de passe incorrect.";
        } elseif (strlen($newPass) < 6) {
            $passMessage = "Le nouveau mot de passe doit faire au moins 6 caractères.";
        } elseif ($newPass !== $confirmPass) {
            $passMessage = "La confirmation ne correspond pas.";
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE client SET mdp_client = :pwd WHERE login = :login");
            $stmt->execute([
                'pwd' => $hash,
                'login' => $user['login']
            ]);

            $passSuccess = true;
            $passMessage = "Mot de passe modifié avec succès !";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Mon profil</title>
<link rel="stylesheet" href="/public/css/profil.css">
</head>

<body>
<main class="profile-page">

<!-- SECTION : Mon compte -->
<section class="profile-card">
    <h2>Mon compte</h2>

    <div class="info-row"><span class="info-label">Login :</span> <span class="info-value"><?= h($user['login']) ?></span></div>
    <div class="info-row"><span class="info-label">Nom :</span> <span class="info-value"><?= h($user['nom_client']) ?></span></div>
    <div class="info-row"><span class="info-label">Email :</span> <span class="info-value"><?= h($user['adresse_mail']) ?></span></div>
    <div class="info-row"><span class="info-label">Téléphone :</span> <span class="info-value"><?= h($user['tel_client']) ?></span></div>
    <div class="info-row"><span class="info-label">Sexe :</span> <span class="info-value"><?= h($user['sexe_client']) ?></span></div>
    <div class="info-row"><span class="info-label">Solde :</span> <span class="info-value solde"><?= format_money_eur($user['solde']) ?></span></div>
    <div class="info-row"><span class="info-label">Date de création :</span> <span class="info-value"><?= h($user['date_creation']) ?></span></div>

    <a href="/src/pages/reservation.php" class="reservations-link">Voir mes réservations</a>
</section>

<!-- SECTION : Modifier informations -->
<section class="profile-card">
    <h3>Modifier mes informations</h3>

    <?php if ($editMessage): ?>
        <div class="recharge-message <?= $editSuccess ? 'success' : 'error' ?>">
            <?= h($editMessage) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="edit-form">
        <input type="hidden" name="edit_profile" value="1">
        <label>Login :</label>
        <input type="text" name="login" value="<?= h($user['login']) ?>" required>
        <label>Nom & prénom :</label>
        <input type="text" name="nom_client" value="<?= h($user['nom_client']) ?>">
        <label>Email :</label>
        <input type="email" name="adresse_mail" value="<?= h($user['adresse_mail']) ?>" required>
        <label>Téléphone :</label>
        <input type="text" name="tel_client" value="<?= h($user['tel_client']) ?>">
        <label>Sexe :</label>
        <select name="sexe_client">
            <option value="Homme" <?= $user['sexe_client']=="Homme" ? "selected" : "" ?>>Homme</option>
            <option value="Femme" <?= $user['sexe_client']=="Femme" ? "selected" : "" ?>>Femme</option>
            <option value="Autre" <?= $user['sexe_client']=="Autre" ? "selected" : "" ?>>Autre</option>
        </select>
        <button type="submit">Mettre à jour</button>
    </form>
</section>

<!-- SECTION : Modifier mot de passe -->
<section class="profile-card">
    <h3>Modifier mon mot de passe</h3>

    <?php if ($passMessage): ?>
        <div class="recharge-message <?= $passSuccess ? 'success' : 'error' ?>">
            <?= h($passMessage) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="edit-form">
        <input type="hidden" name="edit_password" value="1">
        <label>Ancien mot de passe :</label>
        <input type="password" name="old_password" required>
        <label>Nouveau mot de passe :</label>
        <input type="password" name="new_password" required>
        <label>Confirmer le nouveau mot de passe :</label>
        <input type="password" name="confirm_password" required>
        <button type="submit">Changer le mot de passe</button>
    </form>
</section>

<!-- SECTION : Recharger solde -->
<section class="profile-card">
    <h3>Recharger mon solde</h3>
    <p>Votre solde actuel : <strong><?= format_money_eur($user['solde']) ?></strong></p>
    <a href="/src/pages/recharge.php" class="reservations-link">Recharger maintenant</a>
</section>

<!-- SECTION : Déconnexion -->
<section class="profile-card">
    <h3>Se déconnecter</h3>
    <p>Cliquez sur le bouton ci-dessous pour vous déconnecter.</p>
    <button class="reservations-link" onclick="confirmLogout()">Se déconnecter</button>
</section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
function confirmLogout() {
    if (confirm("Êtes-vous sûr de vouloir vous déconnecter ?")) {
        window.location.href = "profil.php?logout=1";
    }
}
</script>

</body>
</html>
