<?php
// ----------------------
// footer.php
// ----------------------

// Determine the base path for assets (relative to public/)
if (!isset($publicBase)) {
    $publicBase = 'public/';
}
?>
<!-- ✅ Lien CSS du footer -->
<link rel="stylesheet" href="/public/css/footer.css?v=6">

<!-- Ajout de Font Awesome pour les icônes -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<footer class="site-footer">
    <div class="footer-container">

        <!-- Colonne 1 : logo + réseaux sociaux -->
        <div class="footer-col">
            <div class="footer-logo">
                <!-- 🔹 Nouveau logo image -->
                <img src="/public/images/logo.png" alt="ReserveScene Logo" class="logo-img">
            </div>

            <div class="footer-socials">
                <a href="#" title="Twitter" class="social-link"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="#" title="Instagram" class="social-link"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" title="YouTube" class="social-link"><i class="fa-brands fa-youtube"></i></a>
                <a href="#" title="LinkedIn" class="social-link"><i class="fa-brands fa-linkedin"></i></a>
            </div>
        </div>

        <!-- Colonne 2 -->
        <div class="footer-col">
            <h4>Engagement qualité</h4>
            <ul>
                <li>Billetterie 100% Officielle</li>
                <li>Paiement 100% sécurisé</li>
                <li>Avis Vérifiés</li>
            </ul>
        </div>

        <!-- Colonne 3 -->
        <div class="footer-col">
            <h4>ReserveScene et Vous</h4>
            <ul>
                <li><a href="/src/pages/aidecontact.php">Aide / contact</a></li>
                <li><a href="/src/pages/faq.php">FAQ</a></li>
                <li><a href="/src/pages/artistes.php">Artistes & Salles</a></li>
                <li><a href="/src/pages/blog.php">Blog ReserveScene</a></li>
            </ul>
        </div>

        <!-- Colonne 4 -->
        <div class="footer-col">
            <h4>Services ReserveScene</h4>
            <ul>
                <li><a href="/src/pages/conditions.php">Conditions générales</a></li>
                <li><a href="/src/pages/politique.php">Politique de confidentialité</a></li>
                <li><a href="/src/pages/mentions_legales.php">Mentions légales</a></li>
                <li><a href="/src/pages/cookies.php">Gérer mes cookies</a></li>
            </ul>
        </div>
    </div>

    <!-- Ligne du bas -->
    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> ReserveScene — Tous droits réservés.
    </div>
</footer>

</body>
</html>
