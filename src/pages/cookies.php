<?php

$nom_cookie    = 'consentement_cookies';
$duree_cookie  = 365 * 24 * 3600;
$chemin_cookie = '/';

function definir_cookie($nom, $valeur, $duree, $chemin = '/')
{
    setcookie($nom, $valeur, [
        'expires'  => time() + $duree,
        'path'     => $chemin,
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}


$consentement = null;
if (isset($_COOKIE[$nom_cookie])) {
    $decode = json_decode($_COOKIE[$nom_cookie], true);
    if (is_array($decode)) {
        $consentement = $decode;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_consentement'])) {

    if ($_POST['action_consentement'] === 'sauver_prefs') {
        $donnees = [
            'donne' => true,
            'date' => time(),
            'categories' => [
                'necessaires' => true,
                'preferences' => isset($_POST['cat_preferences']),
                'analytiques' => isset($_POST['cat_analytiques']),
                'marketing'   => isset($_POST['cat_marketing']),
            ],
        ];
        definir_cookie($nom_cookie, json_encode($donnees), $duree_cookie, $chemin_cookie);
        header("Location: cookies.php");
        exit;
    }

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
        header("Location: cookies.php");
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
        header("Location: cookies.php");
        exit;
    }

    if ($_POST['action_consentement'] === 'reinitialiser') {
        definir_cookie($nom_cookie, '', -3600, $chemin_cookie);
        header("Location: cookies.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des cookies - ReserveScene</title>


    <link rel="stylesheet" href="style.css?v=6">
   
    <link rel="stylesheet" href="/public/css/cookies.css?v=5">
</head>

<body>

<?php include(__DIR__ . '/../includes/header.php'); ?>

<main>
    <section class="cookies-wrapper">
        <h1>Gestion des cookies</h1>

        <form method="post" class="cookies-form">
            <p class="cookies-intro">
                Vous pouvez choisir les catégories de cookies que vous acceptez.
                Les cookies strictement nécessaires sont toujours activés car
                ils sont indispensables au fonctionnement du site.
            </p>

            <div class="cookie-category">
                <h2>Cookies nécessaires</h2>
                <p>
                    Ces cookies sont indispensables pour utiliser le site et ses fonctionnalités
                    (sécurité, sauvegarde de vos choix, etc.).
                </p>
                <label class="cookie-switch disabled">
                    <input type="checkbox" checked disabled>
                    <span>Toujours activés</span>
                </label>
            </div>

            <div class="cookie-category">
                <h2>Préférences</h2>
                <p>
                    Ces cookies permettent de mémoriser vos choix (langue, thème, préférences d’affichage).
                </p>
                <label class="cookie-switch">
                    <input type="checkbox" name="cat_preferences"
                        <?php if (!empty($consentement['categories']['preferences'])) echo 'checked'; ?>>
                    <span>Autoriser les cookies de préférences</span>
                </label>
            </div>

            <div class="cookie-category">
                <h2>Analytiques</h2>
                <p>
                    Ces cookies nous aident à comprendre comment le site est utilisé afin d’améliorer
                    son contenu et ses performances (statistiques anonymes, etc.).
                </p>
                <label class="cookie-switch">
                    <input type="checkbox" name="cat_analytiques"
                        <?php if (!empty($consentement['categories']['analytiques'])) echo 'checked'; ?>>
                    <span>Autoriser les cookies de mesure d’audience</span>
                </label>
            </div>

            <div class="cookie-category">
                <h2>Marketing</h2>
                <p>
                    Ces cookies peuvent être utilisés pour personnaliser les publicités
                    et le contenu en fonction de vos centres d’intérêt.
                </p>
                <label class="cookie-switch">
                    <input type="checkbox" name="cat_marketing"
                        <?php if (!empty($consentement['categories']['marketing'])) echo 'checked'; ?>>
                    <span>Autoriser les cookies marketing</span>
                </label>
            </div>

            <div class="cookies-actions">
                <button type="submit"
                        name="action_consentement"
                        value="sauver_prefs"
                        class="btn btn-primary">
                    Enregistrer mes préférences
                </button>

                <button type="submit"
                        name="action_consentement"
                        value="tout_accepter"
                        class="btn btn-outline">
                    Tout accepter
                </button>

                <button type="submit"
                        name="action_consentement"
                        value="tout_refuser"
                        class="btn btn-outline">
                    Tout refuser
                </button>

                <button type="submit"
                        name="action_consentement"
                        value="reinitialiser"
                        class="btn btn-link">
                    Réinitialiser mon choix
                </button>
            </div>
        </form>

        
        <div class="cookies-legal">
            <h2>1. Qu’est-ce qu’un cookie&nbsp;?</h2>
            <p>
                Un cookie est un petit fichier texte déposé sur votre terminal (ordinateur,
                smartphone, tablette) lorsque vous visitez un site web. Il permet notamment
                de mémoriser certaines informations sur votre navigation.
            </p>

            <h2>2. Quels types de cookies utilisons-nous&nbsp;?</h2>
            <ul>
                <li>Cookies nécessaires au fonctionnement du site ;</li>
                <li>Cookies de préférences ;</li>
                <li>Cookies de mesure d’audience ;</li>
                <li>Cookies marketing ou publicitaires.</li>
            </ul>

            <h2>3. Durée de conservation</h2>
            <p>
                Votre choix concernant les cookies (acceptation ou refus) est conservé pendant
                une durée maximale de 12 mois, puis nous vous redemanderons votre consentement.
            </p>

            <h2>4. Contact</h2>
            <p>
                Pour toute question concernant les cookies et vos données personnelles,
                vous pouvez nous contacter à
                <a href="mailto:contact@reservescene.fr">contact@reservescene.fr</a>.
            </p>

            <p class="last-update">
                Dernière mise à jour : <?php echo date("d/m/Y"); ?>
            </p>
        </div>
    </section>
</main>

<?php include(__DIR__ . '/../includes/footer.php'); ?>


