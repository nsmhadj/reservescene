<?php
session_start();

// 1) Infos événement (GET depuis resultat.php)
$eventId    = $_GET['id']    ?? null;
$eventTitle = $_GET['title'] ?? 'Événement';
$eventDate  = $_GET['date']  ?? '';
$eventVenue = $_GET['venue'] ?? '';

// Charger helpers pour la gestion des prix et du formatage
require_once __DIR__ . '/../includes/helpers.php';

// Déterminer le prix de l'évènement : si passé en GET, on l'utilise, sinon calcul déterministe
$eventPrice = null;
if (isset($_GET['price']) && is_numeric($_GET['price'])) {
    $eventPrice = (float)$_GET['price'];
} else {
    // utilisation d'une fourchette par défaut cohérente avec la recherche
    $eventPrice = generate_deterministic_price((string)$eventId, 25, 70);
}

if (!$eventId) {
    die('Aucun évènement sélectionné.');
}

// Inclure le header commun sur toutes les pages
include __DIR__ . '/../includes/header.php';

// 2) Vérif connexion via $_SESSION['user'] (comme dans connexion.php)
if (empty($_SESSION['user'])) {
    // on veut revenir ici après connexion
    $redirectUrl = 'form.php?' . http_build_query([
        'id'    => $eventId,
        'title' => $eventTitle,
        'date'  => $eventDate,
        'venue' => $eventVenue,
    ]);
    header('Location: connexion.php?redirect=' . urlencode($redirectUrl));
    exit;
}

// 3) Load database configuration
require_once __DIR__ . '/../../config/database.php';

$errors = [];

// Valeurs du formulaire
$holder_lastname  = $_POST['holder_lastname']  ?? '';
$holder_firstname = $_POST['holder_firstname'] ?? '';
$holder_email     = $_POST['holder_email']     ?? ($_SESSION['user_email'] ?? '');
$holder_phone     = $_POST['holder_phone']     ?? '';
$nb_places        = $_POST['nb_places']        ?? '1';
$guest_lastname   = $_POST['guest_lastname']   ?? '';
$guest_firstname  = $_POST['guest_firstname']  ?? '';

// 4) Traitement du POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $holder_lastname  = trim($holder_lastname);
    $holder_firstname = trim($holder_firstname);
    $holder_email     = trim($holder_email);
    $holder_phone     = trim($holder_phone);
    $nb_places        = in_array($nb_places, ['1','2'], true) ? $nb_places : '1';
    $guest_lastname   = trim($guest_lastname);
    $guest_firstname  = trim($guest_firstname);

    if ($holder_lastname === '')  $errors[] = "Le nom est obligatoire.";
    if ($holder_firstname === '') $errors[] = "Le prénom est obligatoire.";
    if ($holder_email === '' || !filter_var($holder_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email est invalide.";
    }
    if ($nb_places === '2') {
        if ($guest_lastname === '' || $guest_firstname === '') {
            $errors[] = "Les informations de la 2ᵉ personne sont obligatoires.";
        }
    }

    if (empty($errors)) {
        // Vérifier le solde suffisant
        $nbPlacesInt = (int)$nb_places;
        $totalPrice = $eventPrice * $nbPlacesInt;

        // Récupération du solde de l'utilisateur
        $stmtSolde = $pdo->prepare("SELECT solde FROM client WHERE login = :login LIMIT 1");
        $stmtSolde->execute(['login' => $_SESSION['user']]);
        $soldeData = $stmtSolde->fetch();
        $soldeActuel = $soldeData ? (float)$soldeData['solde'] : 0.0;

        if ($soldeActuel < $totalPrice) {
            $errors[] = "Solde insuffisant pour cette réservation. Veuillez recharger votre compte.";
        }
    }

    if (empty($errors)) {
        $qrText = 'RES-' . time() . '-' . bin2hex(random_bytes(4));
        try {
            $pdo->beginTransaction();
            // Débiter le solde
            $stmtDebit = $pdo->prepare("UPDATE client SET solde = solde - :amount WHERE login = :login");
            $stmtDebit->execute([
                'amount' => $totalPrice,
                'login'  => $_SESSION['user'],
            ]);

            // Insérer la réservation
            $stmtInsert = $pdo->prepare("INSERT INTO reservations (
                user_login,
                event_id,
                event_title,
                event_date,
                event_venue,
                nb_places,
                main_firstname,
                main_lastname,
                main_email,
                main_phone,
                guest_firstname,
                guest_lastname,
                qr_text,
                created_at
            ) VALUES (
                :user_login,
                :event_id,
                :event_title,
                :event_date,
                :event_venue,
                :nb_places,
                :main_firstname,
                :main_lastname,
                :main_email,
                :main_phone,
                :guest_firstname,
                :guest_lastname,
                :qr_text,
                NOW()
            )");
            $stmtInsert->execute([
                'user_login'      => $_SESSION['user'],
                'event_id'        => $eventId,
                'event_title'     => $eventTitle,
                'event_date'      => $eventDate,
                'event_venue'     => $eventVenue,
                'nb_places'       => $nbPlacesInt,
                'main_firstname'  => $holder_firstname,
                'main_lastname'   => $holder_lastname,
                'main_email'      => $holder_email,
                'main_phone'      => $holder_phone,
                'guest_firstname' => $nb_places === '2' ? $guest_firstname : null,
                'guest_lastname'  => $nb_places === '2' ? $guest_lastname : null,
                'qr_text'         => $qrText,
            ]);

            $pdo->commit();

            // Envoi du mail de confirmation
            $to      = $holder_email;
            $subject = "Votre billet pour : " . $eventTitle;
            $qrUrl   = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($qrText);
            $hostHttp = $_SERVER['HTTP_HOST'];
            $basePath = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
            $message = "
        <html>
        <body>
          <h2>Confirmation de réservation</h2>
          <p>Bonjour " . htmlspecialchars($holder_firstname) . ",</p>
          <p>Votre réservation pour <strong>" . htmlspecialchars($eventTitle) . "</strong> est confirmée.</p>
          <p><strong>Date :</strong> " . htmlspecialchars($eventDate) . "<br>
             <strong>Lieu :</strong> " . htmlspecialchars($eventVenue) . "<br>
             <strong>Nombre de places :</strong> " . htmlspecialchars($nb_places) . "<br>
             <strong>Montant total :</strong> " . format_money_eur($totalPrice) . "
          </p>
          <p>Code de réservation : <strong>" . htmlspecialchars($qrText) . "</strong></p>
          <p>QR Code :</p>
          <p><img src=\"" . $qrUrl . "\" alt=\"QR Code\"></p>
          <p>Vous pouvez retrouver et télécharger votre billet sur votre espace : 
             <a href=\"https://" . $hostHttp . $basePath . "/reservation.php\">Mes réservations</a>
          </p>
        </body>
        </html>
        ";
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Billetterie <no-reply@" . $hostHttp . ">\r\n";
            @mail($to, $subject, $message, $headers);

            header('Location: reservation.php?success=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Une erreur est survenue lors de la réservation. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réservation – <?php echo htmlspecialchars($eventTitle); ?></title>
    <link rel="stylesheet" href="/public/css/form.css">
    <script defer src="/public/js/form.js"></script>
</head>
<body>
    <main class="form-page">
        <section class="form-layout">
            <div class="event-summary-card">
                <h1><?php echo htmlspecialchars($eventTitle); ?></h1>
                <?php if ($eventDate): ?>
                    <p class="event-summary-date"><?php echo htmlspecialchars($eventDate); ?></p>
                <?php endif; ?>
                <?php if ($eventVenue): ?>
                    <p class="event-summary-venue"><?php echo htmlspecialchars($eventVenue); ?></p>
                <?php endif; ?>
                <p class="event-summary-price">Prix par place : <?php echo format_money_eur($eventPrice); ?></p>
                <p class="event-summary-note">
                    Vous êtes sur le point de réserver vos billets pour cet évènement.
                </p>
            </div>

            <div class="reservation-card">
                <h2>Informations de réservation</h2>

                <?php if (!empty($errors)): ?>
                    <div class="form-error-box">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="reservation-form" id="reservation-form">
                    <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($eventId); ?>">
                    <input type="hidden" name="event_title" value="<?php echo htmlspecialchars($eventTitle); ?>">
                    <input type="hidden" name="event_date" value="<?php echo htmlspecialchars($eventDate); ?>">
                    <input type="hidden" name="event_venue" value="<?php echo htmlspecialchars($eventVenue); ?>">

                    <div class="form-row">
                        <div class="field">
                            <label for="holder_lastname">Nom *</label>
                            <input type="text" id="holder_lastname" name="holder_lastname"
                                   value="<?php echo htmlspecialchars($holder_lastname); ?>" required>
                        </div>
                        <div class="field">
                            <label for="holder_firstname">Prénom *</label>
                            <input type="text" id="holder_firstname" name="holder_firstname"
                                   value="<?php echo htmlspecialchars($holder_firstname); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="holder_email">Email *</label>
                            <input type="email" id="holder_email" name="holder_email"
                                   value="<?php echo htmlspecialchars($holder_email); ?>" required>
                        </div>
                        <div class="field">
                            <label for="holder_phone">Téléphone</label>
                            <input type="text" id="holder_phone" name="holder_phone"
                                   value="<?php echo htmlspecialchars($holder_phone); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="nb_places">Nombre de personnes *</label>
                            <select name="nb_places" id="nb_places">
                                <option value="1" <?php echo $nb_places === '1' ? 'selected' : ''; ?>>1 personne</option>
                                <option value="2" <?php echo $nb_places === '2' ? 'selected' : ''; ?>>2 personnes (max)</option>
                            </select>
                        </div>
                    </div>

                    <div class="guest-block <?php echo $nb_places === '2' ? 'guest-visible' : ''; ?>" id="guest-block">
                        <h3>Informations de la 2ᵉ personne</h3>
                        <div class="form-row">
                            <div class="field">
                                <label for="guest_lastname">Nom 2ᵉ personne</label>
                                <input type="text" id="guest_lastname" name="guest_lastname"
                                       value="<?php echo htmlspecialchars($guest_lastname); ?>">
                            </div>
                            <div class="field">
                                <label for="guest_firstname">Prénom 2ᵉ personne</label>
                                <input type="text" id="guest_firstname" name="guest_firstname"
                                       value="<?php echo htmlspecialchars($guest_firstname); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="primary-btn">Valider la réservation</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
<?php include_once __DIR__ . '/../../src/includes/footer.php'; ?>

</body>
</html>
