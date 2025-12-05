<?php
// ----------------------
// mentions_legales.php
// ----------------------
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentions légales - ReserveScene</title>

    <!-- Feuille de style principale -->
    <link rel="stylesheet" href="style.css?v=6">
    <!-- Feuille de style spécifique -->
    <link rel="stylesheet" href="/public/css/mentions_legales.css?v=4">
</head>
<body>

<?php include(__DIR__ . '/../includes/header.php'); ?>

<main class="page-container">
    <section class="mentions-wrapper">
        <h1>Mentions Légales</h1>
        <p class="intro">
            Conformément à la loi n°2004-575 du 21 juin 2004 pour la confiance dans l’économie numérique (LCEN),  
            vous trouverez ci-dessous les informations légales relatives au site <strong>ReserveScene</strong>.
        </p>

        <div class="mentions-content">

            <h2>1. Éditeurs du site</h2>
            <p>
                Le site <strong>ReserveScene</strong> est édité par un groupe d’étudiants passionnés de culture et de technologie :  
            </p>
            <ul>
                <li><strong>AIT ALLALA Melynda</strong></li>
                <li><strong>MESSAHEL Lahna</strong></li>
                <li><strong>BATTOU Yazid</strong></li>
                <li><strong>HADJEBAR Nassim</strong></li>
            </ul>
            <p>
                <strong>Projet :</strong> ReserveScene — plateforme de découverte et d’organisation d’événements culturels (concerts, spectacles, comédies, etc.).<br>
                <strong>Email de contact :</strong> <a href="mailto:contact@reservescene.fr">contact@reservescene.fr</a><br>
                <strong>Rubrique d’aide :</strong> <a href="aidecontact.php">Aide & Contact</a>
            </p>
            <h2>2. Hébergement</h2>
            <p>
             Le site est hébergé par :  
            <strong>Alwaysdata</strong><br>
             Adresse : CY Cergy Paris Université - Site de Saint-Martin ,95000 Cergy, France<br>
             Site web : <a href="https://reservescene.alwaysdata.net/" target="_blank">https://reservescene.alwaysdata.net/</a><br><br>
             Le site est géré et maintenu par l’équipe de développement de ReserveScene :  
             <strong>AIT ALLALA Melynda</strong>, <strong>MESSAHEL Lahna</strong>, <strong>BATTOU Yazid</strong> et <strong>HADJEBAR Nassim</strong>.
            </p>


            <h2>3. Propriété intellectuelle</h2>
            <p>
                L’ensemble du contenu disponible sur le site (textes, images, logos, graphismes, code, etc.)
                est la propriété exclusive de l’équipe ReserveScene ou de ses partenaires API.  
                Toute reproduction, diffusion ou utilisation sans autorisation préalable est interdite.
            </p>

            <h2>4. Données personnelles</h2>
            <p>
                ReserveScene collecte uniquement les données nécessaires à la création et à la gestion de comptes utilisateurs :  
                nom, prénom, adresse e-mail, téléphone, sexe et mot de passe (chiffré).  
                Aucune donnée bancaire n’est collectée ni conservée.  
                Pour plus d’informations, consultez la <a href="politique.php">Politique de Confidentialité</a>.
            </p>

            <h2>5. Cookies</h2>
            <p>
                Des cookies peuvent être utilisés pour améliorer l’expérience de navigation et conserver vos préférences (par ex. filtres d’événements).  
                Vous pouvez les gérer à tout moment via la page <a href="cookies.php">Gérer mes cookies</a>.
            </p>

            <h2>6. Limitation de responsabilité</h2>
            <p>
                L’équipe ReserveScene met tout en œuvre pour fournir des informations fiables et à jour.  
                Toutefois, elle ne saurait être tenue responsable d’erreurs, d’omissions ou de problèmes techniques.  
                Les utilisateurs sont seuls responsables de l’usage qu’ils font du site.
            </p>

            <h2>7. Contact</h2>
            <p>
                Pour toute demande d’information ou d’assistance, vous pouvez :  
                <ul>
                    <li>utiliser la rubrique <a href="aidecontact.php">Aide & Contact</a> ;</li>
                    <li>ou écrire à l’adresse suivante : <a href="mailto:contact@reservescene.fr">contact@reservescene.fr</a>.</li>
                </ul>
            </p>

            <p class="last-update">Dernière mise à jour : <?php echo date("d/m/Y"); ?></p>
        </div>
    </section>
</main>

<?php include(__DIR__ . '/../includes/footer.php'); ?>

</body>
</html>
