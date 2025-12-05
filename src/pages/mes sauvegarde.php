<?php
session_start();
$message = "";

// Load database configuration
require_once __DIR__ . '/../../config/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $message = "Veuillez fournir un nom d'utilisateur ou email et un mot de passe.";
    } else {
        //chercher le client par login ou adresse_mail
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
                $_SESSION['user'] = $user['login'];
                header('Location: index.php');
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
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion</title>
<style>
body { font-family: Arial; background:#f0f0f0; padding:50px; }
form { background:#fff; padding:20px; border-radius:10px; max-width:300px; margin:auto; box-shadow:0 0 10px rgba(0,0,0,0.2);}
input { display:block; width:100%; margin-bottom:10px; padding:8px; border-radius:5px; border:1px solid #ccc; }
button { padding:8px 12px; border:none; border-radius:5px; background:#007BFF; color:#fff; cursor:pointer; width:100%; }
button:hover { background:#0056b3; }
p.error { color:red; text-align:center; }
h2 { text-align:center; }
</style>
</head>
<body>

<h2>Se connecter</h2>

<?php if($message): ?>
<p class="error"><?= htmlspecialchars($message, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?></p>
<?php endif; ?>

<form method="post" autocomplete="off">
    <input type="text" name="username" placeholder="Nom d'utilisateur ou email" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <button type="submit">Connexion</button>
</form>

</body>
</html>



/*
<?php
session_start();
$message = "";

// === Configuration de la base de données AlwaysData ===
$host = "mysql-reservescene.alwaysdata.net";
$dbname = "reservescene_bd";
$dbuser = "REMOVED_USER";
$dbpass = "REMOVED_PASS"; // 🔒 Remplace par ton mot de passe MySQL AlwaysData

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage()));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $message = "Veuillez fournir un nom d'utilisateur (ou email) et un mot de passe.";
    } else {
        // 🔍 On cherche le client par login OU adresse_mail
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
                $message = "Votre compte n'est pas actif. Veuillez contacter l'administrateur.";
            } elseif (password_verify($password, $user['mdp_client'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = $user['login'];
                header('Location: privaindexte1.php');
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

<?php include __DIR__ . '/../includes/header1.php'; ?>


<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
/* Reset léger */
* { box-sizing: border-box; }

body {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
  background: #f7f7f7;
  margin: 0;
  padding: 40px 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}

/* Card centrée */
.auth-card {
  width: 100%;
  max-width: 520px;
  background: #ffffff;
  border: 1px solid #e9e9e9;
  border-radius: 6px;
  padding: 22px 28px;
  box-shadow: 0 6px 20px rgba(33,33,33,0.06);
}

/* Form layout */
.auth-card h2 {
  margin: 0 0 14px 0;
  font-size: 16px;
  color: #222;
  font-weight: 600;
}

.form-row {
  margin-bottom: 14px;
}

.form-row label {
  display: block;
  font-size: 12px;
  color: #666;
  margin-bottom: 6px;
}

/* Inputs */
input[type="text"],
input[type="password"],
input[type="email"] {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 14px;
  background: #fff;
  transition: border-color .15s, box-shadow .15s;
}

input::placeholder { color: #bdbdbd; }

input:focus {
  outline: none;
  border-color: #cfcfcf;
  box-shadow: 0 0 0 3px rgba(0,0,0,0.02);
}

/* Bouton */
button[type="submit"] {
  display: block;
  width: 100%;
  padding: 11px 14px;
  border-radius: 6px;
  background: #222;
  color: #fff;
  border: none;
  font-size: 14px;
  cursor: pointer;
  margin-top: 6px;
}

button[type="submit"]:hover {
  opacity: 0.94;
}

/* Message d'erreur centré */
.message {
  text-align: center;
  margin-bottom: 10px;
  font-size: 13px;
  color: #d9534f; /* rouge */
}

/* Liens bas */
.actions {
  margin-top: 12px;
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.actions a {
  color: #1a73e8;
  text-decoration: none;
}

.actions a:hover {
  text-decoration: underline;
}

/* petit style responsive */
@media (max-width:420px) {
  .auth-card { padding: 16px; }
}
</style>
</head>
<body>

<div class="auth-card" role="main" aria-labelledby="auth-title">
  <h2 id="auth-title">Connexion</h2>

  <?php if($message): ?>
    <div class="message"><?= htmlspecialchars($message, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="off" novalidate>
    <div class="form-row">
      <label for="username">E-mail</label>
      <!-- placeholder 'Valeur' selon ton image -->
      <input id="username" type="text" name="username" placeholder="Nom d'utilisateur ou email" required>
    </div>

    <div class="form-row">
      <label for="password">Mot de passe</label>
      <input id="password" type="password" name="password" placeholder="Mot de passe" required>
    </div>

    <button type="submit">Connexion</button>

    <div class="actions" style="margin-top:12px;">
      <div><a href="register.php">Créer un nouveau compte?</a></div>
      <div><a href="forgot.php">Mot de passe oublié?</a></div>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
