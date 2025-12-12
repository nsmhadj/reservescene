<?php



$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$size = isset($_GET['size']) ? max(1, min(200, (int)$_GET['size'])) : 48;

$bootstrap = __DIR__ . '/../../config/bootstrap.php';
if (file_exists($bootstrap)) require_once $bootstrap;

$TM_API_KEY = getenv('TM_API_KEY') ?: (defined('TICKETMASTER_API_KEY') ? TICKETMASTER_API_KEY : '');
$SG_CLIENT_ID = getenv('SEATGEEK_CLIENT_ID') ?: (defined('SEATGEEK_CLIENT_ID') ? SEATGEEK_CLIENT_ID : '');

function fetch_url_local(string $url, int $timeout = 8) {
    if (function_exists('fetch_with_cache')) {
        try { $r = fetch_with_cache($url, 60); if ($r !== false) return $r; } catch (Throwable $e) { error_log('[recherche] fetch_with_cache: '.$e->getMessage()); }
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'ReserveScene/search',
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) { error_log('[recherche] curl error: '.$err.' for '.$url); return false; }
    if ($code >= 400) { error_log('[recherche] upstream HTTP '.$code.' for '.$url); return false; }
    return $raw;
}

function map_sk_to_tm_like_local(array $sk): array {
    $m = [];
    $m['id'] = 'sk:' . ($sk['id'] ?? uniqid('sk_'));
    $m['name'] = $sk['title'] ?? ($sk['short_title'] ?? ($sk['performers'][0]['name'] ?? ''));
    $m['images'] = [];
    if (!empty($sk['performers']) && is_array($sk['performers'])) {
        foreach ($sk['performers'] as $p) {
            $img = $p['image'] ?? ($p['image_url'] ?? ($p['images']['huge'] ?? null));
            if ($img) $m['images'][] = ['url' => $img];
        }
    }
    if (empty($m['images']) && !empty($sk['image'])) $m['images'][] = ['url' => $sk['image']];
    $m['dates'] = ['start' => []];
    if (!empty($sk['datetime_local'])) {
        $m['dates']['start']['localDate'] = substr($sk['datetime_local'], 0, 10);
        $m['dates']['start']['localTime'] = substr($sk['datetime_local'], 11);
    }
    if (!empty($sk['datetime_utc'])) $m['dates']['start']['dateTime'] = $sk['datetime_utc'];
    $m['_embedded'] = [];
    if (!empty($sk['venue'])) {
        $m['_embedded']['venues'] = [[
            'name' => $sk['venue']['name'] ?? null,
            'city' => ['name' => $sk['venue']['city'] ?? null],
        ]];
    }
    if (!empty($sk['performers'])) {
        $m['_embedded']['attractions'] = [];
        foreach ($sk['performers'] as $p) $m['_embedded']['attractions'][] = ['name' => $p['name'] ?? null];
    }
    $m['type'] = $sk['type'] ?? ($sk['taxonomies'][0]['name'] ?? null);
    $m['_source'] = 'sk';
    $m['_raw'] = $sk;
    return $m;
}

function fetch_tm_local(string $q, string $city = '', int $per_page = 40): array {
    global $TM_API_KEY;
    if (empty($TM_API_KEY)) return [];
    $params = ['apikey' => $TM_API_KEY, 'size' => $per_page, 'page' => 0];
    if ($q !== '') $params['keyword'] = $q;
    if ($city !== '') $params['city'] = $city;
    $url = 'https://app.ticketmaster.com/discovery/v2/events.json?' . http_build_query($params);
    $raw = fetch_url_local($url, 8);
    if ($raw === false) return [];
    $dec = @json_decode($raw, true);
    if (!is_array($dec) || empty($dec['_embedded']['events'])) return [];
    $events = $dec['_embedded']['events'];
    foreach ($events as &$e) { $e['_source'] = 'tm'; if (empty($e['id'])) $e['id'] = 'tm:' . uniqid(); } unset($e);
    return $events;
}

function fetch_sk_local(string $q, string $city = '', int $per_page = 80): array {
    global $SG_CLIENT_ID;
    if (function_exists('seatgeek_search')) {
        $res = seatgeek_search(['keyword' => $q, 'city' => $city, 'per_page' => $per_page, 'page' => 1, 'cache_ttl' => 60]);
        if (is_array($res) && isset($res['events'])) return $res['events'];
        return [];
    }
    if (empty($SG_CLIENT_ID)) return [];
    $params = ['client_id' => $SG_CLIENT_ID, 'per_page' => $per_page, 'page' => 1];
    if ($q !== '') $params['q'] = $q;
    if ($city !== '') $params['venue.city'] = $city;
    $url = 'https://api.seatgeek.com/2/events?' . http_build_query($params);
    $raw = fetch_url_local($url, 8);
    if ($raw === false) return [];
    $dec = @json_decode($raw, true);
    if (!is_array($dec) || !isset($dec['events'])) return [];
    return $dec['events'];
}

function dedupe_key_local(array $ev): string {
    $name = mb_strtolower(trim((string)($ev['name'] ?? $ev['title'] ?? '')), 'UTF-8');
    $name = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    $name = preg_replace('/[^a-z0-9\s]/u', '', $name);
    $name = preg_replace('/\s+/u', ' ', $name);
    $date = $ev['dates']['start']['localDate'] ?? ($ev['dates']['start']['dateTime'] ?? ($ev['_raw']['datetime_local'] ?? ''));
    $date = substr((string)$date, 0, 10);
    return trim($name) . '|' . $date;
}


$tmEvents = fetch_tm_local($keyword, $city, max(12, $size));
$skRaw = fetch_sk_local($keyword, $city, max(40, $size * 2));
$skEvents = [];
foreach ($skRaw as $sk) $skEvents[] = map_sk_to_tm_like_local($sk);
$merged = [];
$seen = [];
foreach ($tmEvents as $e) {
    $key = dedupe_key_local($e);
    if ($key === '|') $key = $e['id'] ?? uniqid();
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $merged[] = $e;
}
foreach ($skEvents as $e) {
    $key = dedupe_key_local($e);
    if ($key === '|') $key = $e['id'] ?? uniqid();
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $merged[] = $e;
}


$final = array_slice($merged, 0, $size);

$results_json = json_encode($final, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Résultats de recherche<?= $keyword ? ' — ' . htmlspecialchars($keyword) : '' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/public/css/recherche.css">
</head>

<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="search-page">

<aside class="filters">
  <h3>Filtres</h3>


  <div class="filter-block">
    <p class="filter-title">Dates</p>
    <label><input type="radio" name="filter-date" class="filter-date" value="all" checked> Toutes les dates</label>
    <label><input type="radio" name="filter-date" class="filter-date" value="today"> Ce soir</label>
    <label><input type="radio" name="filter-date" class="filter-date" value="weekend"> Ce week-end</label>
    <label><input type="radio" name="filter-date" class="filter-date" value="week"> Cette semaine</label>
    <label><input type="radio" name="filter-date" class="filter-date" value="month"> Ce mois-ci</label>
  </div>


  <div class="filter-block">
    <p class="filter-title">Type d'événement</p>
    <label><input type="checkbox" class="filter-cat" value="concert"> Concert</label>
    <label><input type="checkbox" class="filter-cat" value="theatre"> Théâtre</label>
    <label><input type="checkbox" class="filter-cat" value="comedy"> Comédie</label>
  </div>


  <div class="filter-block">
    <p class="filter-title">Ville</p>
    <select id="filter-city">
      <option value="all">Toutes les villes</option>
    </select>
  </div>


  <div class="filter-block">
    <p class="filter-title">Prix</p>
    <label><input type="radio" name="filter-price" class="filter-price" value="all" checked> Tous les prix</label>
    <label><input type="radio" name="filter-price" class="filter-price" value="low"> &lt; 20€ </label>
    <label><input type="radio" name="filter-price" class="filter-price" value="mid"> 20€ – 50€ </label>
    <label><input type="radio" name="filter-price" class="filter-price" value="high"> &gt; 50€ </label>
  </div>

  <div class="filter-block">
    <p class="filter-title">Accessibilité</p>
    <label><input type="checkbox" class="filter-access" value="pmr"> Accès PMR</label>
  </div>
</aside>

  <main class="results">
    <div class="results-header">
      <h2>Résultats de recherche <?= $keyword ? '"' . htmlspecialchars($keyword) . '"' : '' ?></h2>
      <div class="sort-tabs">
        <button class="active">le plus demandé</button>
        <button>le plus proche</button>
        <button>moins cher</button>
      </div>
    </div>


    <div id="js-error" class="error" style="display:none;">Impossible de récupérer les événements.</div>

  
    <div class="cards-grid"></div>

  </main>
</div>


<script>
  window.SEARCH_KEYWORD = <?= json_encode($keyword) ?>;
  window.SEARCH_RESULTS = <?= $results_json ?: '[]' ?>;
</script>
<script src="/public/js/recherche.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>



