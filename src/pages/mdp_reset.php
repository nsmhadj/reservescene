<?php
session_start();
$message = "";
$success = "";


require_once __DIR__ . '/../../config/database.php';

$prefill_email = isset($_GET['email']) ? $_GET['email'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'verify') {
        $email = trim($_POST['email'] ?? '');
        $code = trim($_POST['code'] ?? '');

        if ($email === '' || $code === '') $message = "Email et code requis.";
        else {
            $stmt = $pdo->prepare("SELECT clef, reset_code, reset_expires FROM client WHERE adresse_mail = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['reset_code'] === null || time() > $user['reset_expires'] || $code !== $user['reset_code']) {
                $message = "Code invalide ou expiré.";
            } else {
                $_SESSION['password_reset_allowed'] = [
                    'email' => $email,
                    'clef' => $user['clef'],
                    'expires_at' => $user['reset_expires']
                ];
                header("Location: mdp_reset.php?stage=reset&email=" . urlencode($email));
                exit;
            }
        }
    } elseif ($action === 'reset') {
        $email = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';

        if (!isset($_SESSION['password_reset_allowed'])) {
            $message = "Vérifiez votre code avant de réinitialiser le mot de passe.";
        } else {
            $allowed = $_SESSION['password_reset_allowed'];
            if ($email !== $allowed['email']) {
                $message = "Adresse e-mail différente.";
            } elseif (time() > $allowed['expires_at']) {
                $message = "La session a expiré.";
                unset($_SESSION['password_reset_allowed']);
            } elseif ($new_password === '' || $new_password_confirm === '') {
                $message = "Tous les champs sont requis.";
            } elseif ($new_password !== $new_password_confirm) {
                $message = "Les mots de passe ne correspondent pas.";
            } elseif (strlen($new_password) < 8) {
                $message = "Le mot de passe doit contenir au moins 8 caractères.";
            } else {
             
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE client SET mdp_client = :mdp, reset_code = NULL, reset_expires = NULL WHERE clef = :clef");
                $stmt->execute([
                    'mdp' => $new_hash,
                    'clef' => $allowed['clef']
                ]);

                
                unset($_SESSION['password_reset_allowed']);

               
                $success = "Mot de passe modifié avec succès. Vous allez être redirigé vers la page de connexion dans quelques secondes.";
                
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main style="display:flex;justify-content:center;align-items:center;min-height:70vh;padding:20px;">
  <div style="width:100%;max-width:520px;background:#fff;padding:22px;border-radius:8px;border:1px solid #eee;box-shadow:0 10px 30px rgba(0,0,0,0.04);">
    <h2 style="margin-top:0;">Réinitialisation du mot de passe</h2>

    <?php if($message): ?>
      <div style="color:#c0392b;margin-bottom:14px;padding:10px 12px;border:1px solid #f5c6cb;background:#fff5f6;border-radius:6px;">
        <?= htmlspecialchars($message, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <?php if($success): ?>
      <div id="success-box" style="color:#155724;margin-bottom:14px;padding:12px;border:1px solid #c3e6cb;background:#eafaf0;border-radius:6px;">
        <strong><?= htmlspecialchars($success, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?></strong>
        <div style="margin-top:8px;font-size:14px;color:#0b5345;">
          <a id="login-now" href="connexion.php" style="text-decoration:none;font-weight:600;">Se connecter maintenant</a>
          <span style="margin-left:8px;color:#333;font-weight:400;">(sinon vous serez redirigé automatiquement)</span>
        </div>
      </div>

      <script>
        (function(){
          
          var delay = 4000; 
          setTimeout(function(){
            window.location.replace('connexion.php?reset=success');
          }, delay);
        })();
      </script>

    <?php
    
    else:
      $show_reset_form = isset($_SESSION['password_reset_allowed']);
      if ($show_reset_form):
        $email_value = $_SESSION['password_reset_allowed']['email'];
    ?>
      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="reset">
        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px;" value="<?= htmlspecialchars($email_value, ENT_QUOTES) ?>">

        <label for="new_password">Nouveau mot de passe</label>
        <input id="new_password" name="new_password" type="password" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px;">

        <label for="new_password_confirm">Confirmer le mot de passe</label>
        <input id="new_password_confirm" name="new_password_confirm" type="password" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px;">

        <button type="submit" style="width:100%;padding:11px;border-radius:6px;background:#222;color:#fff;border:none;cursor:pointer;">Mettre à jour le mot de passe</button>
      </form>
    <?php else: ?>
      <form method="post" autocomplete="off">
        <input type="hidden" name="action" value="verify">
        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px;" value="<?= htmlspecialchars($prefill_email, ENT_QUOTES) ?>">

        <label for="code">Code reçu par e-mail</label>
        <input id="code" name="code" type="text" required placeholder="6 caractères" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px;">

        <button type="submit" style="width:100%;padding:11px;border-radius:6px;background:#222;color:#fff;border:none;cursor:pointer;">Vérifier le code</button>
      </form>
      <p style="margin-top:12px;font-size:13px;color:#666;">Entrez le code que vous avez reçu par e-mail. Il est valable 15 minutes.</p>
      <p style="margin-top:6px;"><a href="mdp_oubliee.php">Demander un nouveau code</a></p>
    <?php endif; ?>

    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php' ; ?>
