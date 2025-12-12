<?php
session_start();
$message = "";


require_once __DIR__ . '/../../config/database.php';

$defaultRedirect = '/index.php';
$redirect = $defaultRedirect;

if (!empty($_REQUEST['redirect'])) {
  
    $candidate = rawurldecode($_REQUEST['redirect']);
   
    if (preg_match('#^[a-zA-Z][a-zA-Z0-9+\-.]*://#', $candidate) === 0 && strpos($candidate, '//') === false) {
        
        $candidate = str_replace(["\r", "\n"], '', $candidate);
      
        $redirect = $candidate !== '' ? $candidate : $defaultRedirect;
    } else {
        error_log('connexion.php: rejected redirect candidate: ' . $candidate);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $message = "Veuillez fournir un nom d'utilisateur (ou email) et un mot de passe.";
    } else {
        $stmt = $pdo->prepare("
            SELECT login, adresse_mail, mdp_client, etat_compte
            FROM client
            WHERE login = :username OR adresse_mail = :username
            LIMIT 1
        ");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['etat_compte'] !== 'actif') {
                $message = "Votre compte n'est pas actif.";
            } elseif (password_verify($password, $user['mdp_client'])) {
               
                session_regenerate_id(true);
                $_SESSION['user']       = $user['login'];        
                $_SESSION['user_email'] = $user['adresse_mail'];
                header('Location: ' . $redirect);
                exit;
            } else {
                $message = "Identifiant ou mot de passe incorrect.";
            }
        } else {
            $message = "Identifiant ou mot de passe incorrect.";
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ReserveScene — Connexion</title>
  <meta name="description" content="ReserveScene est une plateforme de réservation de billets pour concerts, spectacles et événements culturels. Trouve et réserve facilement tes places.">



<?php include __DIR__ . '/../includes/header.php'; ?>

  
<main style="display:flex;justify-content:center;align-items:center;min-height:80vh;">
  <div class="auth-card" role="main" aria-labelledby="auth-title" style="width:100%;max-width:520px;background:#fff;border:1px solid #e9e9e9;border-radius:6px;padding:22px 28px;box-shadow:0 6px 20px rgba(33,33,33,0.06);">
    <h2 id="auth-title" style="font-size:16px;font-weight:600;color:#222;">Connexion</h2>

    <?php if($message): ?>
      <div style="text-align:center;margin-bottom:10px;color:#d9534f;font-size:13px;">
        <?= htmlspecialchars($message, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" novalidate>
    
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">

      <div style="margin-bottom:14px; position:relative;">
        <label for="username" style="display:block;font-size:12px;color:#666;margin-bottom:6px;">E-mail ou login</label>
        <input id="username" type="text" name="username" placeholder="Nom d'utilisateur ou email" required style="width:100%;padding:10px 12px;border:1px solid #e0e0e0;border-radius:6px;">
        <small id="userCheck" style="font-size:12px;display:block;margin-top:4px;"></small>
      </div>

      <div style="margin-bottom:14px;">
        <label for="password" style="display:block;font-size:12px;color:#666;margin-bottom:6px;">Mot de passe</label>
        <input id="password" type="password" name="password" placeholder="Mot de passe" required style="width:100%;padding:10px 12px;border:1px solid #e0e0e0;border-radius:6px;">
      </div>

      <button type="submit" style="width:100%;padding:11px 14px;border-radius:6px;background:#222;color:#fff;border:none;cursor:pointer;">Connexion</button>

      <div style="margin-top:12px;display:flex;justify-content:space-between;font-size:13px;">
        <a href="inscription.php" style="color:#1a73e8;text-decoration:none;">Créer un nouveau compte ?</a>
        <a href="mdp_oubliee.php" style="color:#1a73e8;text-decoration:none;">Mot de passe oublié ?</a>
      </div>
    </form>
  </div>
</main>

<script>

document.getElementById("username").addEventListener("blur", function () {
    const username = this.value.trim();
    const info = document.getElementById("userCheck");

    if (username === "") {
        info.textContent = "";
        return;
    }

    fetch("check_user.php?username=" + encodeURIComponent(username))
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                info.style.color = "green";
                info.textContent = "✔ Identifiant trouvé.";
            } else {
                info.style.color = "red";
                info.textContent = "✘ Cet identifiant n’existe pas.";
            }
        });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>