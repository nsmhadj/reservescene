<?php



include_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../../config/bootstrap.php';

$rawId = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
$eventId = $rawId;
$eventData = null;
$fetchError = null;
$googleapi = getenv('GOOGLE_KEY');
function http_get(string $url, int $timeout = 10) {
    if (function_exists('curl_version')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ReserveScene/1.0');
        $body = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return [$body, $http, $err];
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'user_agent' => 'ReserveScene/1.0']]);
        $body = @file_get_contents($url, false, $ctx);
        return [$body, ($body === false ? 0 : 200), null];
    }
}

function map_seatgeek_for_resultat(array $sk): array {
    $m = [];

    $m['name'] = $sk['title'] ?? ($sk['short_title'] ?? ($sk['performers'][0]['name'] ?? ''));

    $m['images'] = [];
   
    if (!empty($sk['performers']) && is_array($sk['performers'])) {
        foreach ($sk['performers'] as $p) {
            if (!empty($p['image'])) {
                $m['images'][] = ['url' => $p['image']];
            } elseif (!empty($p['images']) && is_array($p['images'])) {
                if (!empty($p['images']['huge'])) $m['images'][] = ['url' => $p['images']['huge']];
                else {
                    foreach ($p['images'] as $v) {
                        if (is_string($v) && filter_var($v, FILTER_VALIDATE_URL)) {
                            $m['images'][] = ['url' => $v];
                            break;
                        }
                    }
                }
            }
        }
    }

    if (empty($m['images']) && !empty($sk['image'])) {
        $m['images'][] = ['url' => $sk['image']];
    }
  
    if (empty($m['images']) && !empty($sk['images']) && is_array($sk['images'])) {
        foreach ($sk['images'] as $img) {
            if (is_string($img) && filter_var($img, FILTER_VALIDATE_URL)) {
                $m['images'][] = ['url' => $img];
            } elseif (is_array($img) && !empty($img['url'])) {
                $m['images'][] = ['url' => $img['url']];
            }
            if (!empty($m['images'])) break;
        }
    }

   
    $m['dates'] = ['start' => []];
    if (!empty($sk['datetime_local'])) {
        $m['dates']['start']['localDate'] = substr($sk['datetime_local'], 0, 10);
        $m['dates']['start']['localTime'] = substr($sk['datetime_local'], 11);
    }
    if (!empty($sk['datetime_utc'])) {
        $m['dates']['start']['dateTime'] = $sk['datetime_utc'];
    }


    $m['_embedded'] = [];
    if (!empty($sk['venue']) && is_array($sk['venue'])) {
        $m['_embedded']['venues'] = [[
            'name' => $sk['venue']['name'] ?? null,
            'address' => ['line1' => $sk['venue']['address'] ?? null],
            'postalCode' => $sk['venue']['postal_code'] ?? null,
            'city' => ['name' => $sk['venue']['city'] ?? null],
            'country' => ['name' => $sk['venue']['country'] ?? null],
            'location' => [
                'latitude' => $sk['venue']['location']['lat'] ?? null,
                'longitude' => $sk['venue']['location']['lon'] ?? null,
            ],
        ]];
    }

    
    if (!empty($sk['description'])) $m['info'] = $sk['description'];
    elseif (!empty($sk['short_title'])) $m['pleaseNote'] = $sk['short_title'];

    if (!empty($sk['stats']['lowest_price']) || !empty($sk['stats']['highest_price'])) {
        $min = !empty($sk['stats']['lowest_price']) ? (float)$sk['stats']['lowest_price'] : null;
        $max = !empty($sk['stats']['highest_price']) ? (float)$sk['stats']['highest_price'] : $min;
        $m['priceRanges'] = [];
        if ($min !== null) $m['priceRanges'][] = ['min' => $min, 'max' => $max, 'currency' => $sk['currency'] ?? 'USD'];
    }

    $m['_raw_seatgeek'] = $sk;

    return $m;
}

$source = 'tm';
$tmId = $eventId;
$skId = null;
if (stripos($eventId, 'sk:') === 0) {
    $skId = substr($eventId, 3);
    $source = 'sk';
} elseif (stripos($eventId, 'tm:') === 0) {
    $tmId = substr($eventId, 3);
    $source = 'tm';
} else {
 
    $source = 'tm';
    $tmId = $eventId;
}


if ($source === 'sk' && $skId !== null && $skId !== '') {
  
    $clientId = getenv('SEATGEEK_CLIENT_ID') ?: (defined('SEATGEEK_CLIENT_ID') ? SEATGEEK_CLIENT_ID : '');
    $clientSecret = getenv('SEATGEEK_CLIENT_SECRET') ?: (defined('SEATGEEK_CLIENT_SECRET') ? SEATGEEK_CLIENT_SECRET : '');
    if (!$clientId) {
        $fetchError = "SEATGEEK_CLIENT_ID missing";
        error_log("[resultat.php] $fetchError");
    } else {
        $url = 'https://api.seatgeek.com/2/events/' . rawurlencode($skId) . '?' . http_build_query(['client_id' => $clientId] + ($clientSecret ? ['client_secret' => $clientSecret] : []));
        list($body, $http, $curlErr) = http_get($url, 10);
        if ($body === false || $http < 200 || $http >= 300) {
            $fetchError = "SeatGeek fetch failed (http: $http, err: $curlErr)";
            error_log("[resultat.php] $fetchError for $url");
        } else {
            $sk = @json_decode($body, true);
            if (!is_array($sk)) {
                $fetchError = "SeatGeek returned invalid JSON";
                error_log("[resultat.php] invalid JSON from SeatGeek: " . substr($body, 0, 1000));
            } else {
             
                $eventData = map_seatgeek_for_resultat($sk);
                
                $eventData['id'] = 'sk:' . $skId;
            }
        }
    }
} else {
   
    if ($tmId !== '') {
        $apiKey = getenv('TM_API_KEY');
        if (!$apiKey) {
            $fetchError = "TM_API_KEY missing";
            error_log("[resultat.php] $fetchError");
        } else {
            $url = "https://app.ticketmaster.com/discovery/v2/events/" . rawurlencode($tmId) . ".json?apikey=" . rawurlencode($apiKey);
            list($body, $http, $curlErr) = http_get($url, 10);
            if ($body === false || $http < 200 || $http >= 300) {
                $fetchError = "Ticketmaster fetch failed (http: $http, err: $curlErr)";
                error_log("[resultat.php] $fetchError for $url");
            } else {
                $ev = @json_decode($body, true);
                if (!is_array($ev)) {
                    $fetchError = "Invalid JSON or empty response from Ticketmaster";
                    error_log("[resultat.php] json decode failed for $url : raw=" . substr($body, 0, 1000));
                } else {
                    $eventData = $ev;
                  
                    if (empty($eventData['id'])) $eventData['id'] = $tmId;
                }
            }
        }
    } else {
        $fetchError = "No event id provided";
    }
}

$priceInfo = ['display' => 'Prix non renseigné', 'estimated' => true];
if (is_array($eventData)) {
 
    $priceInfo = get_price_display_for_event($eventData);
} else {
 
    if ($eventId) {
        $det = generate_deterministic_price($eventId, 25, 70);
        $priceInfo = ['display' => format_money_eur($det), 'estimated' => true, 'value' => $det, 'currency' => 'EUR'];
    }
}

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
    .source-badge { display:inline-block; background:#222; color:#fff; padding:2px 6px; border-radius:4px; font-size:0.7rem; margin-left:8px; }
  </style>
</head>

<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="event-page">

  <div class="event-main">
    <div class="event-info">
      <h1 id="event-title"><?= h($eventData['name'] ?? ('Événement #' . ($eventId ?: '')) ) ?>
        <?php if (isset($eventData['_raw_seatgeek']) || (isset($rawId) && stripos($rawId, 'sk:') === 0)): ?>
          <span class="source-badge">SeatGeek</span>
        <?php elseif (isset($eventData['_source']) && $eventData['_source'] === 'tm'): ?>
          <span class="source-badge">Ticketmaster</span>
        <?php endif; ?>
      </h1>

      <p id="event-price" class="event-price">
        <?= h($priceInfo['display']) ?>
        <?php if (!empty($priceInfo['estimated'])): ?>
          <span class="estimated-badge">Estimation</span>
        <?php endif; ?>
      </p>

      <p id="event-venue" class="event-venue"><?= h($eventData['_embedded']['venues'][0]['name'] ?? '') ?></p>
      <p id="event-date" class="event-date"><?= h($eventData['dates']['start']['localDate'] ?? '') ?></p>

      <p id="event-description" class="event-description"><?= h($eventData['info'] ?? $eventData['pleaseNote'] ?? '') ?></p>

      <a
        href="<?= h($formBaseUrl . '?id=' . urlencode($rawId)); ?>"
        id="reserve-btn"
        class="reserve-btn">
        RÉSERVER
      </a>
    </div>

    <div class="event-side">
      <div class="event-image">
        <img id="event-image" src="<?= h($eventData['images'][0]['url'] ?? '') ?>" alt="Image événement">
      </div>
      <div id="event-map" class="event-map"></div>
    </div>
  </div>

  <section class="invited-artists">
    <h2>Artistes invités</h2>
    <div id="invited-list" class="invited-list">

    </div>
  </section>
</div>
<?php 

$gglurl = "https://maps.googleapis.com/maps/api/js?key=" . rawurlencode($googleapi) . "&callback=onGoogleMapsLoaded&loading=async" ;
?>

<script>
  
  window.EVENT_ID = <?= json_encode($rawId) ?>;
  window.EVENT_DATA = <?= json_encode($eventData) ?>;
  window.EVENT_PRICE = <?= json_encode($priceInfo) ?>;
  window.EVENT_SOURCE = <?= json_encode($source) ?>;
  window.FORM_BASE_URL = <?= json_encode($formBaseUrl) ?>;
</script>

<script src="/public/js/resultat.js"></script>
<script src="<?= $gglurl ?>" async></script>

<script>
  
  window.addEventListener('load', function(){
    try {
      var serverPrice = <?= json_encode($priceInfo) ?>;
      var el = document.getElementById('event-price');
      if (!el) return;
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
