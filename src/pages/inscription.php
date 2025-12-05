<?php
session_start();
$message = "";
require_once __DIR__ . '/../../config/bootstrap.php';
/* === Config hCaptcha - loaded from environment variables === */
$HCAPTCHA_SITEKEY = getenv('HCAPTCHA_SITEKEY') ?: "";  
$HCAPTCHA_SECRET  = getenv('HCAPTCHA_SECRET') ?: "";

if (!$HCAPTCHA_SITEKEY || !$HCAPTCHA_SECRET) {
    // hCaptcha not configured - skip verification for development
    $HCAPTCHA_SITEKEY = null;
    $HCAPTCHA_SECRET = null;
}

/* === Connexion DB === */
require_once __DIR__ . '/../../config/database.php';

/* === Générateur de clé unique === */
function generateClef($length = 6) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $clef = '';
    for ($i = 0; $i < $length; $i++) {
        $clef .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $clef;
}

/* === Supprimer comptes expirés en attente === */
$now = time();
$delete = $pdo->prepare("
    DELETE FROM client
    WHERE etat_compte='en_attente'
      AND reset_expires IS NOT NULL
      AND reset_expires < :now
");
$delete->execute(['now' => $now]);

/* === Traitement formulaire === */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom      = trim($_POST['nom'] ?? '');
    $prenom   = trim($_POST['prenom'] ?? '');
    $login    = trim($_POST['login'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $tel      = trim($_POST['tel'] ?? '');
    $sexe     = $_POST['sexe'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (
        $nom === '' || $prenom === '' || $login === '' || $email === '' ||
        $tel === '' || $sexe === '' || $password === '' || $password2 === ''
    ) {
        $message = "Tous les champs sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse e-mail invalide.";
    } elseif (strlen($password) < 8) {
        $message = "Le mot de passe doit comporter au moins 8 caractères.";
    } elseif ($password !== $password2) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {

        /* === Vérification hCaptcha === */
        $hcaptchaResponse = $_POST['h-captcha-response'] ?? '';

        // Skip hCaptcha verification if not configured (development mode)
        if ($HCAPTCHA_SECRET && $HCAPTCHA_SITEKEY) {
            if (empty($hcaptchaResponse)) {
                $message = "Veuillez confirmer que vous n'êtes pas un robot.";
            } else {
                $verifyUrl = "https://hcaptcha.com/siteverify";
                $data = [
                    'secret'   => $HCAPTCHA_SECRET,
                    'response' => $hcaptchaResponse,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null
                ];

                $ch = curl_init($verifyUrl);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $result = curl_exec($ch);
                curl_close($ch);

                $resultData = json_decode($result, true);

                if (!$resultData || empty($resultData['success'])) {
                    $message = "La vérification du captcha a échoué. Veuillez réessayer.";
                }
            }
        }

        if ($message === "") {

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM client
                WHERE login = :login OR adresse_mail = :email
            ");
            $stmt->execute([
                'login' => $login,
                'email' => $email
            ]);

            if ($stmt->fetchColumn() > 0) {
                $message = "Ce login ou cet e-mail est déjà utilisé.";
            } else {

                $hash    = password_hash($password, PASSWORD_DEFAULT);
                $clef    = generateClef();
                $expires = time() + 15 * 60;

                $insert = $pdo->prepare("
                    INSERT INTO client (
                        login,
                        nom_client,
                        adresse_mail,
                        mdp_client,
                        tel_client,
                        sexe_client,
                        clef,
                        etat_compte,
                        date_creation,
                        reset_expires
                    ) VALUES (
                        :login,
                        :nom,
                        :email,
                        :mdp,
                        :tel,
                        :sexe,
                        :clef,
                        'en_attente',
                        CURDATE(),
                        :expires
                    )
                ");

                $insert->execute([
                    'login'   => $login,
                    'nom'     => $prenom . ' ' . $nom,
                    'email'   => $email,
                    'mdp'     => $hash,
                    'tel'     => $tel,
                    'sexe'    => $sexe,
                    'clef'    => $clef,
                    'expires' => $expires
                ]);

                $subject = "Activation de votre compte RéserveScene";
                $body = "
                <html>
                  <body style='font-family:Arial,sans-serif;color:#222;'>
                    <p>Bonjour <strong>" . htmlspecialchars($prenom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</strong>,</p>
                    <p>Merci pour votre inscription sur <strong>RéserveScene</strong> </p>
                    <p>Votre code de validation unique est :</p>
                    <p style='font-size:20px; font-weight:bold;'>" . htmlspecialchars($clef, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
                    <p> Saisissez ce code sur le site pour activer votre compte.</p>
                    <p><strong> Ce code est valable 15 minutes, pensez à vérifier vos spams.</strong></p>
                    <p>Si vous ne validez pas dans ce délai, votre inscription sera supprimée automatiquement.</p>
                    <p>Cordialement,<br>L’équipe RéserveScene</p>
                  </body>
                </html>
                ";

                $headers  = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: no-reply@reservescene.alwaysdata.net\r\n";

                @mail($email, $subject, $body, $headers);

                $_SESSION['pending_validation'] = [
                    'email'   => $email,
                    'clef'    => $clef,
                    'expires' => $expires
                ];

                header("Location: validation_email.php?email=" . urlencode($email));
                exit;
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main style="display:flex;justify-content:center;align-items:center;min-height:80vh;">
  <div style="width:100%;max-width:550px;background:#fff;border:1px solid #e9e9e9;border-radius:6px;padding:25px;box-shadow:0 6px 20px rgba(0,0,0,0.05);">
    <h2 style="font-size:18px;font-weight:600;margin-bottom:10px;">Inscription</h2>

    <?php if ($message): ?>
      <div style="color:#d9534f;margin-bottom:12px;text-align:center;">
        <?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <label>Nom</label>
      <input name="nom" type="text" required style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;">

      <label>Prénom</label>
      <input name="prenom" type="text" required style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;">

      <label>Login</label>
      <input name="login" id="login" type="text" required style="width:100%;padding:10px;margin-bottom:3px;border:1px solid:#ccc;border-radius:6px;">
      <span id="login-info" style="font-size:13px;margin-bottom:10px;display:block;"></span>

      <label>Email</label>
      <input name="email" id="email" type="email" required style="width:100%;padding:10px;margin-bottom:3px;border:1px solid #ccc;border-radius:6px;">
      <span id="email-info" style="font-size:13px;margin-bottom:10px;display:block;"></span>

      <label>Téléphone</label>
      <input name="tel" type="text" required style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;">

      <label>Sexe</label>
      <select name="sexe" required style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;">
        <option value="">Choisissez</option>
        <option value="H">Homme</option>
        <option value="F">Femme</option>
      </select>

      <label>Mot de passe</label>
      <input name="password" type="password" required style="width:100%;padding:10px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;">

      <label>Confirmer le mot de passe</label>
      <input name="password2" type="password" required style="width:100%;padding:10px;margin-bottom:15px;border:1px solid #ccc;border-radius:6px;">

      <!-- hCaptcha widget - only show if configured -->
      <?php if ($HCAPTCHA_SITEKEY): ?>
      <div class="h-captcha" data-sitekey="<?= htmlspecialchars($HCAPTCHA_SITEKEY, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" style="margin-bottom:15px;"></div>
      <?php endif; ?>

      <button type="submit" style="width:100%;padding:11px;border-radius:6px;background:#222;color:#fff;border:none;cursor:pointer;">
        Créer mon compte
      </button>
    </form>
  </div>
</main>

<!-- Script hCaptcha -->
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>

<!-- ====================================================================== -->
<!-- JS LIVE LOGIN & EMAIL VALIDATION AJAX -->
<script>
function checkField(type, value, spanId) {
    if (value.trim().length < 2) {
        document.getElementById(spanId).textContent = "";
        return;
    }

    fetch("check_inscription.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: type + "=" + encodeURIComponent(value)
    })
    .then(res => res.json())
    .then(data => {
        if (data.exists === true) {
            document.getElementById(spanId).style.color = "red";
            document.getElementById(spanId).textContent = type + " invalide ✘";
        } else {
            document.getElementById(spanId).style.color = "green";
            document.getElementById(spanId).textContent = type + " valide ✔";
        }
    })
    .catch(err => console.error(err));
}

document.getElementById("login").addEventListener("blur", function() {
    checkField("login", this.value, "login-info");
});

document.getElementById("email").addEventListener("blur", function() {
    checkField("email", this.value, "email-info");
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
