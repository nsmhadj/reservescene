<?php 
// Génération d'un hash sécurisé pour le mot de passe
$motDePasseClair = 'Lahna';
$hash = password_hash($motDePasseClair, PASSWORD_DEFAULT);

// Affichage du hash et de sa longueur
echo "Mot de passe : " . htmlspecialchars($motDePasseClair) . "<br>";
echo "Hash généré : " . htmlspecialchars($hash) . "<br>";
echo "Longueur du hash : " . strlen($hash);
?>
