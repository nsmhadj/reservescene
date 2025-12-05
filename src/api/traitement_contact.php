<?php
// traitement_contact.php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // On récupère les données envoyées par le formulaire
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $sujet = htmlspecialchars($_POST['sujet']);
    $message = htmlspecialchars($_POST['message']);

    // Email recipient - loaded from environment variable
    $destinataire = getenv('MAIL_CONTACT') ?: "no-reply@reservescene.tld";

    // Sujet de l'email
    $sujetMail = "Nouveau message de contact : $sujet";

    // Contenu du message
    $contenu = "
    Nom : $nom
    Email : $email

    Message :
    $message
    ";

    // En-têtes pour l'email
    $headers = "From: $email\r\nReply-To: $email\r\n";

    // Envoi du mail
    if (mail($destinataire, $sujetMail, $contenu, $headers)) {
        echo "<script>alert(' Merci, votre message a bien été envoyé !'); window.location.href='aidecontact.php';</script>";
    } else {
        echo "<script>alert('Une erreur est survenue. Veuillez réessayer plus tard.'); window.history.back();</script>";
    }
} else {
    header("Location: aidecontact.php");
    exit();
}
?>
