<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
?>



    

<body>
   <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/accessibility.css">
<header class="header">
    <div class="header-logo">
  
        <a href="/index.php" class="header-link" style="font-size:1.4rem;font-weight:bold;color:#ff005d;text-decoration:none;">
            <img src="/public/images/logo.png" alt="Logo ReserveScene">
        </a>
    </div>

    <form action="/src/pages/recherche.php" method="GET" class="header-search">
        <input type="text" aria-label="Recherche" name="q" placeholder="Rechercher un artiste, un spectacle…" value="<?php echo isset($_GET['q']) ? h($_GET['q']) : ''; ?>">
    </form>

    <nav class="header-nav">
        <a href="/src/pages/recherche.php?q=music" class="header-link">Concert</a>
        <a href="/src/pages/recherche.php?q=theatre" class="header-link">Théâtre</a>
        <a href="/src/pages/recherche.php?q=comedy" class="header-link">Comédie</a>
        <a href="/src/pages/aidecontact.php" class="header-link">Contact</a>

        <div class="accessibility-menu">
            <button id="accessibility-toggle" aria-expanded="false" aria-controls="accessibility-dropdown">
                Accessibilité
            </button>
            <div id="accessibility-dropdown" class="accessibility-dropdown" aria-hidden="true">
                <button id="font-decrease">A- Diminuer</button>
                <button id="font-default">A Normal</button>
                <button id="font-increase">A+ Augmenter</button>
                <hr>
                <button id="contrast-toggle">Contraste élevé</button>
                <button id="dyslexia-toggle">Police Dyslexie</button>
            </div>
        </div>

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

<script src="/public/js/accessibility.js" defer></script>
