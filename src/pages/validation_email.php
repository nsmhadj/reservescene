<?php
session_start();
$message = "";

/* === Connexion DB === */
require_once __DIR__ . '/../../config/database.php';

/* === Supprimer comptes expirés en attente === */
$now = time();
$delete = $pdo->prepare("DELETE FROM client WHERE etat_compte='en_attente' AND reset_expires IS NOT NULL AND reset_expires < :now");
$delete->execute(['now' => $now]);

/* === Récupérer l'email depuis GET ou session === */
$email = $_GET['email'] ?? ($_SESSION['pending_validation']['email'] ?? null);
if (!$email) {
    die("Adresse e-mail manquante.");
}

/* === Traitement du formulaire === */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input_code = trim($_POST['code'] ?? '');

    if ($user_input_code === '') {
        $message = "Veuillez saisir votre code de validation.";
    } else {
        $stmt = $pdo->prepare("SELECT clef, reset_expires, etat_compte FROM client WHERE adresse_mail=:email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $message = "Compte introuvable.";
        } elseif ($user['etat_compte'] === 'actif') {
            $message = "Votre compte est déjà actif.";
        } elseif (time() > $user['reset_expires']) {
            $del = $pdo->prepare("DELETE FROM client WHERE adresse_mail=:email");
            $del->execute(['email' => $email]);
            $message = "Le code a expiré. Votre inscription a été annulée. Veuillez recommencer.";
        } elseif ($user_input_code !== $user['clef']) {
            $message = "Code incorrect. Veuillez réessayer.";
        } else {
            $update = $pdo->prepare("UPDATE client SET etat_compte='actif', reset_expires=NULL WHERE adresse_mail=:email");
            $update->execute(['email' => $email]);

            unset($_SESSION['pending_validation']);

            echo "<div style='text-align:center;margin-top:50px;'>
                    <h2>Votre compte a été validé </h2>
                    <p>Cliquez ci-dessous pour vous connecter :</p>
                    <a href='/src/pages/connexion.php' style='display:inline-block;margin-top:10px;padding:12px 25px;background:#222;color:#fff;text-decoration:none;border-radius:6px;'>Se connecter</a>
                  </div>";
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main style="display:flex;justify-content:center;align-items:center;min-height:70vh;padding:20px;">
  <div style="width:100%;max-width:480px;background:#fff;padding:20px;border-radius:6px;border:1px solid #eee;box-shadow:0 6px 20px rgba(0,0,0,0.04);">
    <h2>Validation de votre compte</h2>

    <?php if($message): ?>
      <div style="color:#c0392b;margin-bottom:12px;"><?= htmlspecialchars($message, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>

    <p>Un code de validation a été envoyé à votre adresse e-mail : <strong><?= htmlspecialchars($email) ?></strong></p>

    <form method="post" autocomplete="off">
      <label for="code">Code de validation</label>
      <input id="code" name="code" type="text" required placeholder="Entrez le code reçu par mail" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:12px;">
      <button type="submit" style="width:100%;padding:11px;border-radius:6px;background:#222;color:#fff;border:none;cursor:pointer;">Valider le compte</button>
    </form>

    <p style="margin-top:12px;font-size:13px;color:#666;"> Vous avez 15 minutes pour valider votre compte . Attention veuillez donc vérifier vos messages Spam.</p>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
