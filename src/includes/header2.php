<link rel="stylesheet" href="header.css">

<header class="header">

    <!-- Logo -->
    <div class="header-logo">
        <a href="index.php">
            <img src="logo.png" alt="Logo ReserveScene">
        </a>
    </div>

    <!-- Barre de recherche -->
    <form action="recherche.php" method="GET" class="header-search">
        <input type="text" name="q" placeholder="Rechercher un artiste, un spectacle...">
    </form>

    <!-- Navigation -->
    <nav class="header-nav">

        <a class="header-link" href="concert.php">Concert</a>
        <a class="header-link" href="theatre.php">Théâtre</a>
        <a class="header-link" href="comedie.php">Comédie</a>

        <!-- Profil dropdown -->
        <div class="header-profile-dropdown">
            <button class="profile-btn" type="button">
                <img src="https://cdn-icons-png.flaticon.com/512/1077/1077063.png" alt="Profil">
            </button>

            <div class="profile-menu">
                <a href="index.php">Se déconnecter</a>
            </div>
        </div>

        <!-- Burger menu -->
        <button class="burger-btn" type="button">
            <span></span>
            <span></span>
            <span></span>
        </button>

    </nav>

</header>

<script src="header.js"></script>
