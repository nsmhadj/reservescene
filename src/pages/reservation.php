<?php
session_start();

// 🔐 Vérif connexion
if (empty($_SESSION['user'])) {
    header('Location: connexion.php?redirect=' . urlencode('reservation.php'));
    exit;
}

// Load database configuration
require_once __DIR__ . '/../../config/database.php';

// 🧾 Téléchargement billet
// 🧾 Téléchargement billet EN PDF + envoi mail avec PDF en pièce jointe
if (isset($_GET['download'])) {
    $id = (int) $_GET['download'];

    // Récupération de la réservation qui appartient à l'utilisateur connecté
    $stmt = $pdo->prepare("
        SELECT *
        FROM reservations
        WHERE id = :id AND user_login = :login
        LIMIT 1
    ");
    $stmt->execute([
        ':id'    => $id,
        ':login' => $_SESSION['user']
    ]);
    $res = $stmt->fetch();

    if (!$res) {
        die('Billet introuvable.');
    }

    // URL QR code (API externe)
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($res['qr_text']);

    // URL du logo (par ex. logo.png dans le même dossier que reservation.php)
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
    $logoUrl = $baseUrl . 'logo.png';  // mets ton logo ici

    // HTML du billet (version "jolie")
    $html = '
    <html>
    <head>
      <meta charset="UTF-8">
      <style>
        body {
          font-family: DejaVu Sans, sans-serif;
          font-size: 12px;
          margin: 0;
          padding: 0;
          background: #f5f5f5;
        }
        .ticket-wrapper {
          width: 90%;
          margin: 30px auto;
          background: #ffffff;
          border-radius: 16px;
          border: 1px solid #e0e0e0;
          padding: 20px 24px;
        }
        .ticket-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 12px;
        }
        .ticket-header-left {
          display: flex;
          flex-direction: column;
          gap: 4px;
        }
        .ticket-logo {
          height: 40px;
        }
        .ticket-title {
          font-size: 18px;
          font-weight: 700;
          margin: 0;
        }
        .ticket-subtitle {
          font-size: 12px;
          color: #777;
          margin: 0;
        }
        .ticket-body {
          display: flex;
          justify-content: space-between;
          margin-top: 16px;
        }
        .ticket-info {
          width: 60%;
        }
        .info-row {
          margin-bottom: 6px;
        }
        .info-label {
          font-weight: 600;
        }
        .ticket-qr {
          width: 35%;
          text-align: center;
        }
        .ticket-qr img {
          border-radius: 8px;
          border: 1px solid #ddd;
        }
        .ticket-footer {
          margin-top: 18px;
          font-size: 11px;
          color: #555;
          border-top: 1px dashed #ccc;
          padding-top: 8px;
        }
        .code-strong {
          font-weight: 700;
          letter-spacing: 0.03em;
        }
      </style>
    </head>
    <body>
      <div class="ticket-wrapper">
        <div class="ticket-header">
          <div class="ticket-header-left">
            <h1 class="ticket-title">Billet de réservation</h1>
            <p class="ticket-subtitle">Merci pour votre réservation.</p>
          </div>
          <div>
            <img src="/public/images/logo.png" alt="Logo" class="ticket-logo">
          </div>
        </div>

        <div class="ticket-body">
          <div class="ticket-info">
            <div class="info-row">
              <span class="info-label">Évènement : </span>'
                . htmlspecialchars($res["event_title"]) .
            '</div>
            <div class="info-row">
              <span class="info-label">Date : </span>'
                . htmlspecialchars($res["event_date"]) .
            '</div>
            <div class="info-row">
              <span class="info-label">Lieu : </span>'
                . htmlspecialchars($res["event_venue"]) .
            '</div>
            <div class="info-row">
              <span class="info-label">Nom : </span>'
                . htmlspecialchars($res["main_firstname"] . " " . $res["main_lastname"]) .
            '</div>';

    if (!empty($res['guest_firstname']) || !empty($res['guest_lastname'])) {
        $html .= '
            <div class="info-row">
              <span class="info-label">2ᵉ personne : </span>'
                . htmlspecialchars(trim($res["guest_firstname"] . " " . $res["guest_lastname"])) .
            '</div>';
    }

    $html .= '
            <div class="info-row">
              <span class="info-label">Nombre de places : </span>'
                . (int)$res["nb_places"] .
            '</div>
            <div class="info-row">
              <span class="info-label">Code de réservation : </span>
              <span class="code-strong">' . htmlspecialchars($res["qr_text"]) . '</span>
            </div>
          </div>

          <div class="ticket-qr">
            <p style="margin-bottom:6px;">QR Code à présenter :</p>
            <img src="' . htmlspecialchars($qrUrl) . '" alt="QR Code" width="160" height="160">
          </div>
        </div>

        <div class="ticket-footer">
          Présentez ce billet (imprimé ou en version numérique) à l\'entrée de l\'évènement.
        </div>
      </div>
    </body>
    </html>
    ';

    // 🔹 Charger les libs
    require __DIR__ . '/../../vendor/autoload.php';

    // 1) Génération du PDF avec Dompdf
    $dompdf = new Dompdf\Dompdf([
        'isRemoteEnabled' => true, // autoriser le chargement du QR + logo
    ]);

    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    // 2) Envoi du mail avec le PDF en pièce jointe
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Sur alwaysdata, tu peux rester en mode mail() classique :
        $mail->isMail(); // utilise la fonction mail() de PHP

        $mail->setFrom('no-reply@reservescene.alwaysdata.net', 'Billetterie');
        $mail->addAddress($res['main_email'], $res['main_firstname'] . ' ' . $res['main_lastname']);

        $mail->Subject = 'Votre billet pour : ' . $res['event_title'];
        $mail->isHTML(true);

        $mail->Body = '
            <p>Bonjour ' . htmlspecialchars($res['main_firstname']) . ',</p>
            <p>Vous trouverez en pièce jointe votre billet au format PDF pour l\'évènement 
               <strong>' . htmlspecialchars($res['event_title']) . '</strong>.</p>
            <p>Vous pouvez aussi retrouver vos billets à tout moment dans la rubrique 
               <a href="' . htmlspecialchars($baseUrl . 'reservation.php') . '">Mes réservations</a>.</p>
            <p>Bonne soirée 🎵</p>
        ';

        // Pièce jointe PDF (à partir de la chaîne générée)
        $mail->addStringAttachment($pdfOutput, 'billet-' . $res['id'] . '.pdf', 'base64', 'application/pdf');

        $mail->send();
    } catch (Exception $e) {
        // En cas d'erreur mail, on loggue simplement (mais on laisse quand même télécharger le PDF)
        error_log('Erreur envoi mail billet : ' . $mail->ErrorInfo);
    }

    // 3) Envoi du PDF au navigateur (téléchargement)
    $filename = 'billet-' . $res['id'] . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfOutput));
    echo $pdfOutput;
    exit;
}


// 📜 Liste des réservations de ce login
$stmt = $pdo->prepare("
    SELECT *
    FROM reservations
    WHERE user_login = :login
    ORDER BY created_at DESC
");
$stmt->execute([':login' => $_SESSION['user']]);
$reservations = $stmt->fetchAll();

$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes réservations</title>
    <link rel="stylesheet" href="/public/css/reservation.css">
    <script defer src="/public/js/reservation.js"></script>
</head>
<body>
  <?php include  __DIR__ . '/../includes/header.php'; ?>
    <main class="reservation-page">
        <header class="reservation-header">
            <div>
                <h1>Mes réservations</h1>
                <p>Consultez et téléchargez vos billets.</p>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="reservation-success">
                Votre réservation a bien été enregistrée. Vous pouvez retrouver votre billet ci-dessous.
            </div>
        <?php endif; ?>

        <?php if (empty($reservations)): ?>
            <p>Aucune réservation pour le moment.</p>
        <?php else: ?>
            <section class="reservation-list">
                <?php foreach ($reservations as $res): ?>
                    <article class="reservation-card-item" data-res-id="<?php echo (int)$res['id']; ?>">
                        <div class="reservation-main-info">
                            <h2><?php echo htmlspecialchars($res['event_title']); ?></h2>
                            <?php if (!empty($res['event_date'])): ?>
                                <p class="res-date"><?php echo htmlspecialchars($res['event_date']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($res['event_venue'])): ?>
                                <p class="res-venue"><?php echo htmlspecialchars($res['event_venue']); ?></p>
                            <?php endif; ?>
                            <p class="res-people">
                                Pour <?php echo (int)$res['nb_places']; ?> personne<?php echo $res['nb_places'] > 1 ? 's' : ''; ?>
                            </p>
                            <p class="res-code">Code : <strong><?php echo htmlspecialchars($res['qr_text']); ?></strong></p>
                            <p class="res-created">Réservé le : <?php echo htmlspecialchars($res['created_at']); ?></p>
                        </div>
                        <div class="reservation-actions">
                            <?php
                             $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($res['qr_text']);
                            ?>
                            <div class="res-qr-wrapper">
                                <img src="<?php echo $qrUrl; ?>" alt="QR code">
                            </div>
                            <a href="reservation.php?download=<?php echo (int)$res['id']; ?>" class="download-btn">
                                Télécharger le billet
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
