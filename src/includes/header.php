<?php
/**
 * En‑tête commun à toutes les pages de l'application.
 * Ce fichier gère l'affichage du logo, du champ de recherche,
 * des liens de navigation et de l'icône utilisateur.
 * Il charge également la feuille de style header.css afin
 * d'uniformiser l'apparence du header sur toutes les pages.
 */

// Démarrer la session au besoin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonction d'échappement simple si helpers.php n'est pas inclus
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// Determine the base path for assets (relative to public/)
// Since pages are now at root level, prefix assets with public/
$scriptName = $_SERVER['SCRIPT_NAME'];
$publicBase = 'public/';
?>
<!-- Inclusion de la feuille de style du header -->
<link rel="stylesheet" href="/public/css/header.css">

<header class="header">
    <!-- Logo -->
    <div class="header-logo">
        <a href="/index.php" class="header-link" style="font-size:1.4rem;font-weight:bold;color:#ff005d;text-decoration:none;">
            <img src="/public/images/logo.png"> 
        </a>
    </div>

    <!-- Barre de recherche -->
    <form action="/src/pages/recherche.php" method="GET" class="header-search">
        <input type="text" name="q" placeholder="Rechercher un artiste, un spectacle…" value="<?php echo isset($_GET['q']) ? h($_GET['q']) : ''; ?>">
    </form>

    <!-- Navigation -->
    <nav class="header-nav">
        <!-- Lien vers les événements de type Concert/Musique -->
        <a href="/src/pages/recherche.php?classificationName=music" class="header-link">Concert</a>
        <!-- Lien vers les événements de type Théâtre -->
        <a href="/src/pages/recherche.php?classificationName=theatre" class="header-link">Théâtre</a>
        <!-- Lien vers les événements de type Comédie -->
        <a href="/src/pages/recherche.php?classificationName=comedy" class="header-link">Comédie</a>
        <!-- Lien vers la page de contact/FAQ -->
        <a href="/src/pages/aidecontact.php" class="header-link">Contact</a>

        <!-- Icône utilisateur : profil ou connexion selon la session -->
        <div class="header-profile">
            <?php if (!empty($_SESSION['user'])): ?>
                <a href="/src/pages/profil.php">
                    <img src="https://cdn-icons-png.flaticon.com/512/1077/1077063.png" alt="Profil">
                </a>
            <?php else: ?>
                <a href="/src/pages/connexion.php">
                    <img src="https://cdn-icons-png.flaticon.com/512/1077/1077063.png" alt="Connexion">
                </a>
            <?php endif; ?>
        </div>
    </nav>
</header>