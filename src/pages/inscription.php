<?php
session_start();
$message = "";
require_once __DIR__ . '/../../config/bootstrap.php';

$HCAPTCHA_SITEKEY = getenv('HCAPTCHA_SITEKEY') ?: "";
$HCAPTCHA_SECRET  = getenv('HCAPTCHA_SECRET') ?: "";

if (!$HCAPTCHA_SITEKEY || !$HCAPTCHA_SECRET) {
    $HCAPTCHA_SITEKEY = null;
    $HCAPTCHA_SECRET = null;
}


require_once __DIR__ . '/../../config/database.php';

function generateClef($length = 6) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $clef = '';
    for ($i = 0; $i < $length; $i++) {
        $clef .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $clef;
}

$now = time();
$delete = $pdo->prepare("
    DELETE FROM client
    WHERE etat_compte='en_attente'
      AND reset_expires IS NOT NULL
      AND reset_expires < :now
");
$delete->execute(['now' => $now]);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom      = trim($_POST['nom'] ?? '');
    $prenom   = trim($_POST['prenom'] ?? '');
    $login    = trim($_POST['login'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $tel      = trim($_POST['tel'] ?? '');
    $sexe     = $_POST['sexe'] ?? '';
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($nom === '' || $prenom === '' || $login === '' || $email === '' || $password === '' || $password2 === '') {
        $message = "Tous les champs obligatoires doivent être remplis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse e-mail invalide.";
    } elseif (strlen($password) < 8) {
        $message = "Le mot de passe doit comporter au moins 8 caractères.";
    } elseif ($password !== $password2) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {

      
        if ($HCAPTCHA_SECRET && $HCAPTCHA_SITEKEY) {
            $hcaptchaResponse = $_POST['h-captcha-response'] ?? '';
            if (empty($hcaptchaResponse)) {
                $message = "Veuillez confirmer que vous n'êtes pas un robot.";
            } else {
                $verifyUrl = "https://hcaptcha.com/siteverify";
                $data = [
                    'secret' => $HCAPTCHA_SECRET,
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
                    $message = "La vérification du captcha a échoué.";
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

                $tel  = $tel ?: null;
                $sexe = $sexe ?: null;

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

        
                $_SESSION['pending_validation'] = [
                    'email'   => $email,
                    'clef'    => $clef,
                    'expires' => $expires
                ];

                $subject = "Validation de votre compte";
                $body = "Bonjour $prenom $nom,\n\n"
                      . "Merci de créer votre compte.\n"
                      . "Pour valider votre compte, utilisez ce code : $clef\n\n"
                      . "Ce code est valable 15 minutes.\n\n"
                      . "Si vous n'avez pas demandé cette action, ignorez ce message.\n\n"
                      . "Cordialement,\nL'équipe de ReserveScene.";

                @mail($email, $subject, $body, "From: no-reply@reservescene.tld\r\n");

                header("Location: validation_email.php?email=" . urlencode($email));
                exit;
            }
        }
    }
}


?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ReserveScene — Inscription</title>
  <meta name="description" content="ReserveScene est une plateforme de réservation de billets pour concerts, spectacles et événements culturels. Trouve et réserve facilement tes places.">
<link rel="stylesheet" href="/public/css/inscription.css">
</head>

    <?php include __DIR__ . '/../includes/header.php'; ?>
<main class="inscription-wrapper">
    <div class="inscription-card">
        <h2>Créer un compte</h2>

        <?php if ($message): ?>
            <div class="form-error">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="form-grid">
                <div>
                    
                    <input name="nom" aria-label="nom" placeholder="Nom" type="text" required>
                </div>
                <div>
                  
                    <input name="prenom" aria-label="prenom" placeholder="Prenom" type="text" required>
                </div>
            </div>

            
            <input name="login" id="login"  aria-label="id" placeholder="ID" type="text" required>
            <span id="login-info" class="live-info"></span>

            
            <input name="email" id="email" aria-label="email" placeholder="ton.email@example.com" type="email" required>
            <span id="email-info" class="live-info"></span>

            
            <input name="tel" aria-label="tel" placeholder="Telephone" type="text">

            
            <select name="sexe" aria-label="sexe">
                <option value="">Choisissez votre sexe</option>
                <option value="H">Homme</option>
                <option value="F">Femme</option>
            </select>

           
            <input name="password" id="password" aria-label="mdpss" placeholder="Votre mot de passe" type="password" required>

            <div id="password-rules" class="password-rules">
                <p id="rule-length" class="rule">Minimum 8 caractères</p>
                <p id="rule-uppercase" class="rule">Au moins 1 majuscule</p>
                <p id="rule-number" class="rule">Au moins 1 chiffre</p>
                <p id="rule-special" class="rule">Au moins 1 caractère spécial</p>
            </div>

   
            <input name="password2"  aria-label="mdpssconf" placeholder="Confirmez votre mot de passe" type="password" required>

            <?php if ($HCAPTCHA_SITEKEY): ?>
            <div class="captcha-container" >
                <div class="h-captcha"  aria-hidden="true"  data-sitekey="<?= htmlspecialchars($HCAPTCHA_SITEKEY, ENT_QUOTES) ?>"></div>
            </div>
            <?php endif; ?>

            <button class="btn-submit" type="submit">Créer mon compte</button>
        </form>
    </div>
</main>

<script src="https://js.hcaptcha.com/1/api.js" async defer></script>

<script>
function checkField(type, value, spanId) {
    if (value.trim().length < 2) {
        const span = document.getElementById(spanId);
        span.textContent = "";
        span.classList.remove("good", "bad");
        return;
    }

    fetch(`../api/check_inscription.php?type=${encodeURIComponent(type)}&value=${encodeURIComponent(value)}`)
    .then(res => res.json())
    .then(data => {
        const span = document.getElementById(spanId);
        if (data.exists === true) {
            span.classList.add("bad");
            span.classList.remove("good");
            span.textContent = type + " invalide ✘";
        } else {
            span.classList.add("good");
            span.classList.remove("bad");
            span.textContent = type + " valide ✔";
        }
    })
    .catch(err => console.error(err));
}


document.getElementById("login").addEventListener("blur", () => {
    const loginInput = document.getElementById("login");
    checkField("login", loginInput.value, "login-info");
});


document.getElementById("email").addEventListener("blur", () => {
    const emailInput = document.getElementById("email");
    const span = document.getElementById("email-info");
    const value = emailInput.value.trim();

   
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
        span.classList.add("bad");
        span.classList.remove("good");
        span.textContent = "email invalide ✘";
        return;
    }

    checkField("email", value, "email-info");
});


const passwordInput = document.getElementById("password");
passwordInput.addEventListener("input", () => {
    const value = passwordInput.value;
    document.getElementById("rule-length").style.color = value.length >= 8 ? "green" : "black";
    document.getElementById("rule-uppercase").style.color = /[A-Z]/.test(value) ? "green" : "black";
    document.getElementById("rule-number").style.color = /\d/.test(value) ? "green" : "black";
    document.getElementById("rule-special").style.color = /[!@#$%^&*(),.?":{}|<>]/.test(value) ? "green" : "black";
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
