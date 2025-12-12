<?php
$to = "tonadresse@gmail.com";
$subject = "Test mail AlwaysData";
$message = "Ceci est un test d'envoi depuis AlwaysData avec mail().";
$headers = "From: test@reservescene.alwaysdata.net\r\n";

if (mail($to, $subject, $message, $headers)) {
    echo " Mail envoyé (ou au moins tenté)";
} else {
    echo " Erreur d’envoi (mail() a échoué)";
}
?>
