<?php
// smtp_test.php - TEMPORARY. Drop in public folder and open in browser.
// Requires composer autoload if PHPMailer installed.

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php'; // adjust path if necessary

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$to = 'you@your-domain.example'; // set your address to receive the test

// Read SMTP config from env (or .env via bootstrap if you added one)
$smtpHost = getenv('SMTP_HOST') ?: '';
$smtpPort = getenv('SMTP_PORT') ?: 587;
$smtpUser = getenv('SMTP_USER') ?: '';
$smtpPass = getenv('SMTP_PASS') ?: '';
$smtpSecure = getenv('SMTP_SECURE') ?: 'tls';
$mailFrom = getenv('MAIL_FROM') ?: 'no-reply@reservescene.alwaysdata.net';
$mailFromName = getenv('MAIL_FROM_NAME') ?: 'RéserveScene';

echo "<pre>SAPI: " . PHP_SAPI . "\n";
echo "Attempting SMTP send using host: {$smtpHost}:{$smtpPort}\n\n";

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPDebug = SMTP::DEBUG_OFF; // change to DEBUG_CLIENT or DEBUG_SERVER for verbose output
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = !empty($smtpUser);
    if ($mail->SMTPAuth) {
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
    }
    $mail->SMTPSecure = $smtpSecure ?: PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)$smtpPort;

    $mail->setFrom($mailFrom, $mailFromName);
    $mail->addAddress($to);

    $mail->isHTML(false);
    $mail->Subject = 'SMTP test from ReserveScene';
    $mail->Body = "Test message from ReserveScene on " . date('c');

    $mail->send();
    echo "SMTP send: OK\n";
} catch (Exception $e) {
    echo "SMTP send failed: " . $e->getMessage() . "\n";
    // If you need lower level debugging, uncomment:
    // echo "PHPMailer ErrorInfo: " . $mail->ErrorInfo;
}