<?php

session_start();
if (empty($_SESSION['user'])) {
  
    header('Location: connexion.php?redirect=' . urlencode('recharge.php'));
    exit;
}

require_once __DIR__ . '/../../config/database.php';

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('format_money_eur')) {
    function format_money_eur($montant) {
        return number_format((float)$montant, 2, ',', ' ') . ' €';
    }
}
$rechargeMessage = '';
$rechargeSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code_recharge'])) {
    $codeSaisi = trim($_POST['code_recharge']);
    if ($codeSaisi === '') {
        $rechargeMessage = 'Veuillez saisir un code.';
    } else {
    
        $stmt = $pdo->prepare(
            "SELECT id_code, valeur, utiliser, code FROM code
             WHERE (id_code = :id_code) OR (code = :code_saisi)
             LIMIT 1"
        );
        $stmt->execute([
            'id_code'    => $codeSaisi,
            'code_saisi' => $codeSaisi,
        ]);
        $codeRow = $stmt->fetch();
        if (!$codeRow) {
            // Code inexistant ou invalide
            $rechargeMessage = 'Code inexistant ou invalide.';
        } elseif ((int)$codeRow['utiliser'] === 1) {
            // Code déjà utilisé
            $rechargeMessage = 'Ce code a déjà été utilisé.';
        } else {
            // Code valide et non utilisé : on procède au rechargement
            $valeur = (float)$codeRow['valeur'];
            try {
                // Démarrer une transaction pour garantir la cohérence des opérations
                $pdo->beginTransaction();

                // Incrémenter le solde du client
                // On utilise ici le paramètre :val comme dans les autres pages afin de rester cohérent
                $stmt1 = $pdo->prepare("UPDATE client SET solde = solde + :val WHERE login = :login");
                $stmt1->execute([
                    'val'   => $valeur,
                    'login' => $_SESSION['user'],
                ]);

                // Marquer le code comme utilisé et enregistrer l'identifiant de l'utilisateur
                // On conserve la valeur de la colonne `code`, car elle contient le code d'origine
                $stmt2 = $pdo->prepare("UPDATE code SET utiliser = 1, login = :login WHERE id_code = :id_code");
                $stmt2->execute([
                    'login'  => $_SESSION['user'],
                    'id_code' => $codeRow['id_code'],
                ]);

                // Valider les modifications
                $pdo->commit();

                // Message de succès
                $rechargeMessage = 'Votre solde a été rechargé de ' . format_money_eur($valeur) . '.';
                $rechargeSuccess = true;
            } catch (Exception $e) {
                // Retour arrière en cas d'erreur
                $pdo->rollBack();
                $rechargeMessage = "Une erreur est survenue lors du rechargement.";
            }
        }
    }
}

// Inclure l'en‑tête commun
include __DIR__ . '/../includes/header.php';
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rechargement du solde</title>
    <!-- Feuille de styles spécifique à la page de recharge -->
    <link rel="stylesheet" href="/public/css/recharge.css">
</head>
<body>

<main class="recharge-page">
    <section class="recharge-container">
        <h2>Recharger mon solde</h2>
        <?php if ($rechargeMessage): ?>
            <div class="recharge-message <?php echo $rechargeSuccess ? 'success' : 'error'; ?>">
                <?= h($rechargeMessage) ?>
            </div>
        <?php endif; ?>
        <form method="post" class="recharge-form">
            <label for="code_recharge" class="recharge-label">Code de rechargement</label>
            <input type="text" id="code_recharge" name="code_recharge" placeholder="Entrez votre code" required>
            <button type="submit" class="recharge-button">Recharger</button>
        </form>
    </section>
</main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
<script src="/public/js/recharge.js"></script>
</body>
</html>