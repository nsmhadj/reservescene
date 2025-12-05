<?php
// resultat.php - always show a price: real if available, otherwise deterministic estimation
include __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$eventId = isset($_GET['id']) ? trim($_GET['id']) : '';
$eventData = null;
$fetchError = null;

if ($eventId !== '') {
    // Load API key from environment variable
    $apiKey = getenv('TM_API_KEY') ;
    $url = "https://app.ticketmaster.com/discovery/v2/events/" . rawurlencode($eventId) . ".json?apikey=" . rawurlencode($apiKey);

    // prefer cURL
    if (function_exists('curl_version')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ReserveScene/1.0');
        $json = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($json === false || $json === '') {
            $fetchError = "cURL error: " . $curlErr . " (http: " . intval($http) . ")";
            error_log("[resultat.php] fetch failed for $url : $fetchError");
        } else {
            $eventData = @json_decode($json, true);
            if ($eventData === null) {
                $fetchError = "Invalid JSON or empty response (http: " . intval($http) . ")";
                error_log("[resultat.php] json decode failed for $url : raw=" . substr($json, 0, 1000));
            }
        }
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'ReserveScene/1.0']]);
        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) {
            $fetchError = "file_get_contents failed (check allow_url_fopen / firewall)";
            error_log("[resultat.php] fetch failed for $url : $fetchError");
        } else {
            $eventData = @json_decode($json, true);
            if ($eventData === null) {
                $fetchError = "Invalid JSON from upstream";
                error_log("[resultat.php] json decode failed for $url : raw=" . substr($json, 0, 1000));
            }
        }
    }
}

// Compute price display:
// - if we have event data: use helper (real price or helper fallback)
// - otherwise: generate deterministic price from event id so there's ALWAYS a price shown
$priceInfo = ['display' => 'Prix non renseigné', 'estimated' => true];
if (is_array($eventData)) {
    // this will return real price if present or generated fallback if not
    $priceInfo = get_price_display_for_event($eventData);
} else {
    // eventData missing -> create deterministic estimate from event id so user sees a price
    if ($eventId) {
        $det = generate_deterministic_price($eventId, 25, 70);
        $priceInfo = ['display' => format_money_eur($det), 'estimated' => true, 'value' => $det, 'currency' => 'EUR'];
    }
}

// 🔗 URL absolue vers form.php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
$formBaseUrl = $baseUrl . 'form.php';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Détail événement</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/public/css/resultat.css">
  <style>
    .estimated-badge { display:inline-block; background:#ffecb3; color:#6b4d00; padding:2px 6px; border-radius:4px; font-size:0.75rem; margin-left:8px; }
  </style>
</head>
<body>

<div class="event-page">

  <div class="event-main">
    <div class="event-info">
      <h1 id="event-title"><?= h($eventData['name'] ?? ('Événement #' . ($eventId ?: '')) ) ?></h1>

      <p id="event-price" class="event-price">
        <?= h($priceInfo['display']) ?>
        <?php if (!empty($priceInfo['estimated'])): ?>
          <span class="estimated-badge">Estimation</span>
        <?php endif; ?>
      </p>

      <p id="event-venue" class="event-venue"><?= h($eventData['_embedded']['venues'][0]['name'] ?? '') ?></p>
      <p id="event-date" class="event-date"><?= h($eventData['dates']['start']['localDate'] ?? '') ?></p>

      <p id="event-description" class="event-description"><?= h($eventData['info'] ?? $eventData['pleaseNote'] ?? '') ?></p>

      <!-- Bouton RÉSERVER -->
      <a
        href="<?= h($formBaseUrl . '?id=' . urlencode($eventId)); ?>"
        id="reserve-btn"
        class="reserve-btn">
        RÉSERVER
      </a>
    </div>

    <div class="event-image">
      <img id="event-image" src="<?= h($eventData['images'][0]['url'] ?? '') ?>" alt="Image événement">
    </div>
  </div>

  <section class="invited-artists">
    <h2>Artistes invités</h2>
    <div id="invited-list" class="invited-list">
      <!-- rempli par JS -->
    </div>
  </section>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
  // expose server values to JS
  window.EVENT_ID = <?= json_encode($eventId) ?>;
  window.EVENT_DATA = <?= json_encode($eventData) ?>;
  window.EVENT_PRICE = <?= json_encode($priceInfo) ?>;
  window.FORM_BASE_URL = <?= json_encode($formBaseUrl) ?>;
</script>

<!-- main client script -->
<script src="/public/js/resultat.js"></script>

<!-- Small defensive script: reapply server price after other scripts run -->
<script>
  window.addEventListener('load', function(){
    try {
      var serverPrice = <?= json_encode($priceInfo) ?>;
      var el = document.getElementById('event-price');
      if (!el) return;
      // always set server-side display (prevents client scripts from accidentally removing it)
      el.textContent = serverPrice.display || el.textContent || '';
      if (serverPrice.estimated) {
        var span = document.createElement('span');
        span.className = 'estimated-badge';
        span.textContent = 'Estimation';
        el.appendChild(document.createTextNode(' '));
        el.appendChild(span);
      }
    } catch (e) {
      console.warn('Reapply server price failed', e);
    }
  });
</script>

</body>
</html>