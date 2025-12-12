<?php
session_start();
$message = "";

/* === Connexion DB === */
require_once __DIR__ . '/../../config/database.php';


function generateCode($length = 6) {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if ($email === '') {
        $message = "Veuillez saisir votre adresse e-mail.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse e-mail invalide.";
    } else {
        $message = "Si cette adresse existe, un code vous sera envoyé par e-mail.";

   
        $stmt = $pdo->prepare("SELECT clef, login FROM client WHERE adresse_mail = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $code = generateCode(); 
            $expires_at = time() + 15*60; 

        
            $update = $pdo->prepare("UPDATE client SET reset_code = :code, reset_expires = :expires WHERE clef = :clef");
            $update->execute([
                'code' => $code,
                'expires' => $expires_at,
                'clef' => $user['clef']
            ]);

         
            $subject = "Code de réinitialisation de votre mot de passe";
            $body = "Bonjour,\n\nVous avez demandé la réinitialisation de votre mot de passe.\n\n"
                  . "Votre code : " . $code . "\n\n"
                  . "Ce code est valable 15 minutes.\n\nSi vous n'avez pas demandé cette action, ignorez ce message.\n\nCordialement.";

            @mail($email, $subject, $body, "From: no-reply@reservescene.tld\r\n");
        }

        header("Location: mdp_reset.php?email=" . urlencode($email));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main style="display:flex;justify-content:center;align-items:center;min-height:70vh;padding:20px;">
  <div style="width:100%;max-width:480px;background:#fff;padding:20px;border-radius:6px;border:1px solid #eee;box-shadow:0 6px 20px rgba(0,0,0,0.04);">
    <h2>Mot de passe oublié</h2>

    <?php if($message): ?>
      <div style="color:#2c3e50;margin-bottom:12px;"><?= htmlspecialchars($message, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <label for="email" style="display:block;margin-bottom:6px;">Adresse e-mail</label>
      <input id="email" name="email" type="email" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px;" value="<?= isset($_GET['email']) ? htmlspecialchars($_GET['email'], ENT_QUOTES) : '' ?>">
      <button type="submit" style="width:100%;padding:11px;border-radius:6px;background:#222;color:#fff;border:none;cursor:pointer;">Recevoir le code</button>
    </form>

    <p style="margin-top:12px;font-size:13px;color:#666;">Si l'adresse existe, un code sera envoyé et sera valable 15 minutes.</p>
    <p style="margin-top:6px;"><a href="connexion.php">Retour à la connexion</a></p>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
