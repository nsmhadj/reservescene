<?php
// ----------------------
// politique.php
// ----------------------
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de Confidentialité - ReserveScene</title>

    <!-- Feuille de style principale -->
    <link rel="stylesheet" href="style.css?v=6">
    <!-- Feuille de style spécifique -->
    <link rel="stylesheet" href="/public/css/politique.css?v=4">
</head>
<body>

<?php include(__DIR__ . '/../includes/header.php'); ?> <!-- header lisible sur fond blanc -->

<main class="page-container">
    <section class="politique-wrapper">
        <h1>Politique de Confidentialité</h1>
        <p class="intro">
            Chez <strong>ReserveScene</strong>, la protection de vos données personnelles est une priorité.  
            Cette politique explique comment nous collectons, utilisons et protégeons les informations de nos utilisateurs.
        </p>

        <div class="politique-content">

            <h2>1. Données collectées</h2>
            <p>Lorsque vous créez un compte sur ReserveScene, nous recueillons uniquement les informations nécessaires à votre identification et à l’accès à nos services :</p>
            <ul>
                <li>Nom et prénom</li>
                <li>Adresse e-mail (login)</li>
                <li>Numéro de téléphone</li>
                <li>Sexe</li>
                <li>Mot de passe (chiffré et sécurisé)</li>
            </ul>
            <p>Aucune donnée bancaire ou de paiement n’est collectée ni stockée par ReserveScene.</p>

            <h2>2. Provenance des événements</h2>
            <p>Les événements proposés sur notre site (concerts, spectacles, comédies, etc.) sont obtenus à partir d’API externes officielles.  
            Nous ne collectons pas de données personnelles auprès de ces sources.</p>

            <h2>3. Utilisation des données</h2>
            <p>Les données que vous nous fournissez servent uniquement à :</p>
            <ul>
                <li>Créer et gérer votre compte utilisateur ;</li>
                <li>Permettre l’accès à votre espace personnel ;</li>
                <li>Vous envoyer des informations relatives à votre profil ou à nos services (si vous l’acceptez) ;</li>
                <li>Améliorer l’affichage, le filtrage et la présentation des événements.</li>
            </ul>

            <h2>4. Protection et sécurité</h2>
            <p>Les informations personnelles sont stockées dans une base de données sécurisée.  
            Les mots de passe sont <strong>hachés et jamais enregistrés en clair</strong>.  
            L’accès à ces données est strictement limité aux administrateurs autorisés de ReserveScene.</p>

            <h2>5. Cookies</h2>
            <p>Des cookies peuvent être utilisés afin de mémoriser vos préférences d’affichage ou vos filtres de recherche.  
            Vous pouvez modifier ces paramètres à tout moment via la page <a href="cookies.php">Gérer mes cookies</a>.</p>

            <h2>6. Partage des données</h2>
            <p>Nous ne partageons jamais vos informations personnelles avec des tiers, sauf en cas d’obligation légale.  
            Aucun transfert ou vente de données n’est effectué.</p>

            <h2>7. Vos droits</h2>
            <p>Conformément au RGPD, vous disposez des droits suivants :</p>
            <ul>
                <li>Accès à vos données personnelles ;</li>
                <li>Rectification ou suppression de vos informations ;</li>
                <li>Opposition à leur traitement ;</li>
                <li>Portabilité de vos données.</li>
            </ul>
            <p>Pour exercer ces droits, vous pouvez contacter :  
            <a href="mailto:contact@reservescene.fr">contact@reservescene.fr</a></p>

            <h2>8. Modifications de la politique</h2>
            <p>ReserveScene se réserve le droit d’adapter la présente politique pour se conformer à l’évolution de la législation ou de nos services.  
            La version à jour est toujours accessible sur cette page.</p>

            <p class="last-update">Dernière mise à jour : <?php echo date("d/m/Y"); ?></p>
        </div>
    </section>
</main>

<?php include(__DIR__ . '/../includes/footer.php'); ?>

</body>
</html>
