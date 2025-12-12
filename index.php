<?php
$nom_cookie    = 'consentement_cookies';
$duree_cookie  = 24 * 3600; 
$chemin_cookie = '/';

function definir_cookie($nom, $valeur, $duree, $chemin = '/')
{
    setcookie($nom, $valeur, time() + $duree, $chemin);
}


$consentement = null;
if (isset($_COOKIE[$nom_cookie])) {
    $decode = json_decode($_COOKIE[$nom_cookie], true);
    if (is_array($decode)) {
        $consentement = $decode;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_consentement'])) {

    if ($_POST['action_consentement'] === 'tout_accepter') {
        $donnees = [
            'donne' => true,
            'date' => time(),
            'categories' => [
                'necessaires' => true,
                'preferences' => true,
                'analytiques' => true,
                'marketing'   => true,
            ],
        ];
        definir_cookie($nom_cookie, json_encode($donnees), $duree_cookie, $chemin_cookie);
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    if ($_POST['action_consentement'] === 'tout_refuser') {
        $donnees = [
            'donne' => true,
            'date' => time(),
            'categories' => [
                'necessaires' => true,   
                'preferences' => false,
                'analytiques' => false,
                'marketing'   => false,
            ],
        ];
        definir_cookie($nom_cookie, json_encode($donnees), $duree_cookie, $chemin_cookie);
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}


?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ReserveScene — Accueil</title>
  <meta name="description" content="ReserveScene est une plateforme de réservation de billets pour concerts, spectacles et événements culturels. Trouve et réserve facilement tes places.">


  <link rel="stylesheet" href="public/css/index.css">
  <link rel="stylesheet" href="public/css/banner.css">
  <link rel="stylesheet" href="public/css/trending.css">
  <link rel="stylesheet" href="public/css/showcases.css">
  <link rel="stylesheet" href="public/css/footer.css">
</head>


<?php 
include __DIR__ . '/src/includes/header.php';
echo (!$consentement || empty($consentement['donne'])) ? 'class="banner-open"' : ''; ?>>

<?php if (!$consentement || empty($consentement['donne'])): ?>
<div class="cookie-banner" role="dialog" aria-live="polite" aria-label="Bannière de cookies">
    <div class="cookie-banner-inner">
        <p class="cookie-banner-text">
            Nous utilisons des cookies pour améliorer ton expérience sur ReserveScene.
            Tu peux accepter ou refuser les cookies non essentiels.
        </p>

        <div class="cookie-banner-actions">
            <form method="post" class="cookie-banner-form">
                <button type="submit"
                        name="action_consentement"
                        value="tout_refuser"
                        class="cookie-btn cookie-btn-secondary">
                    Tout refuser
                </button>

                <button type="submit"
                        name="action_consentement"
                        value="tout_accepter"
                        class="cookie-btn cookie-btn-primary">
                    Tout accepter
                </button>
            </form>

            <div class="cookie-banner-more">
                <a href="cookies.php">Gérer mes cookies</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<section class="hero">
 <div id="heroViewport" class="hero__viewport"></div>
</section>
<h1 class="visually-hidden">ReserveScene – Billetterie et spectacles</h1>

<section class="trending" id="trending">
  <div class="trending__head">
    <h2>Comédie • Musique • Théâtre — les dates à ne pas manquer</h2>
    <p class="trending__tag">#tendances</p>
  </div>

 
  <div class="trending__list" id="trendingList">
    <?php include_once __DIR__ . '/src/components/trending.php'; ?>
  </div>
</section>
<script src="public/js/banner.js"></script>
<?php include_once __DIR__ . '/src/components/showcases.php'; ?>
<?php include_once __DIR__ . '/src/includes/footer.php'; ?>




