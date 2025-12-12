<?php
// src/api/tm-json.php
//
// Compatibility endpoint backed by SeatGeek only (uses seatgeek_search()).
// Returns JSON: { events: [...], count: N, fetched_at: "..."}
//
// Usage: /src/api/tm-json.php?category=comedy&size=6&city=Paris

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Try to load bootstrap (so env vars are available)
$bootstrap = __DIR__ . '/../../config/bootstrap.php';
if (file_exists($bootstrap)) require_once $bootstrap;

// Try to include the integration helper if not already loaded
$helper1 = 'seatgeek.php';

if (file_exists($helper1)) include_once $helper1;

// Read params
$category = isset($_GET['category']) ? trim((string)$_GET['category']) : '';
$size = isset($_GET['size']) ? max(1, min(100, (int)$_GET['size'])) : 6;
$city = isset($_GET['city']) ? trim((string)$_GET['city']) : '';
// optionally use category as keyword to bias results (useful for theatre/comedy)
$keyword = $category !== '' && $category !== 'all' ? $category : '';

// Ensure seatgeek_search exists
if (!function_exists('seatgeek_search')) {
    http_response_code(500);
    echo json_encode(['events' => [], 'count' => 0, 'error' => 'seatgeek_search helper not available. Ensure src/integrations/seatgeek.php is present.']);
    exit;
}

// Prepare call
$opts = [
    'keyword' => $keyword,
    'city' => $city,
    'per_page' => max(6, $size * 2),
    'page' => 1,
    'cache_ttl' => 60,
    'timeout' => 8,
];

$res = seatgeek_search($opts);
if (!is_array($res) || !isset($res['events']) || !is_array($res['events'])) {
    $err = is_array($res) && isset($res['error']) ? $res['error'] : 'SeatGeek returned invalid response';
    http_response_code(502);
    echo json_encode(['events' => [], 'count' => 0, 'error' => $err]);
    exit;
}

// Map SK events to TM-like minimal shape used by front
function map_sk_to_tm_like(array $sk): array {
    $m = [];
    $m['id'] = 'sk:' . ($sk['id'] ?? uniqid('sk_'));
    $m['name'] = $sk['title'] ?? ($sk['short_title'] ?? ($sk['performers'][0]['name'] ?? ''));
    // images: prefer performers images then top-level
    $m['images'] = [];
    if (!empty($sk['performers']) && is_array($sk['performers'])) {
        foreach ($sk['performers'] as $p) {
            $img = $p['image'] ?? ($p['image_url'] ?? ($p['images']['huge'] ?? null));
            if ($img) $m['images'][] = ['url' => $img];
        }
    }
    if (empty($m['images'])) {
        if (!empty($sk['image'])) $m['images'][] = ['url' => $sk['image']];
        elseif (!empty($sk['performers'][0]['images']['huge'])) $m['images'][] = ['url' => $sk['performers'][0]['images']['huge']];
    }
    // dates
    $m['dates'] = ['start' => []];
    if (!empty($sk['datetime_local'])) {
        $m['dates']['start']['localDate'] = substr($sk['datetime_local'], 0, 10);
        $m['dates']['start']['localTime'] = substr($sk['datetime_local'], 11);
    }
    if (!empty($sk['datetime_utc'])) $m['dates']['start']['dateTime'] = $sk['datetime_utc'];
    // embedded venues
    $m['_embedded'] = [];
    if (!empty($sk['venue']) && is_array($sk['venue'])) {
        $m['_embedded']['venues'] = [[
            'name' => $sk['venue']['name'] ?? null,
            'city' => ['name' => $sk['venue']['city'] ?? null],
            'country' => ['name' => $sk['venue']['country'] ?? null],
            'address' => ['line1' => $sk['venue']['address'] ?? null],
        ]];
    }
    // type (use SK type or taxonomies if present)
    $m['type'] = $sk['type'] ?? null;
    if (empty($m['type']) && !empty($sk['taxonomies'][0]['name'])) $m['type'] = $sk['taxonomies'][0]['name'];
    // priceRanges
    $m['priceRanges'] = [];
    if (!empty($sk['stats']['lowest_price'])) {
        $m['priceRanges'][] = [
            'min' => (float)$sk['stats']['lowest_price'],
            'max' => (float)($sk['stats']['highest_price'] ?? $sk['stats']['lowest_price']),
            'currency' => $sk['currency'] ?? null,
        ];
    }
    // preserve raw if needed (comment out in prod)
    // $m['_raw'] = $sk;
    $m['source'] = 'sk';
    return $m;
}

$mapped = [];
foreach ($res['events'] as $sk) {
    $mapped[] = map_sk_to_tm_like($sk);
}

// Deduplicate by normalized name+date (simple) and limit to $size
function norm_key($name, $date) {
    $s = trim(mb_strtolower((string)$name, 'UTF-8'));
    $s2 = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($s2 !== false) $s = $s2;
    $s = preg_replace('/[^a-z0-9\s]/u', '', $s);
    $s = preg_replace('/\s+/u', ' ', $s);
    $d = substr((string)$date, 0, 10);
    return trim($s) . '|' . $d;
}

$seen = [];
$out = [];
foreach ($mapped as $ev) {
    $title = $ev['name'] ?? '';
    $date = $ev['dates']['start']['localDate'] ?? ($ev['dates']['start']['dateTime'] ?? '');
    $k = norm_key($title, $date);
    if ($k === '|') $k = $ev['id'] ?? uniqid();
    if (isset($seen[$k])) continue;
    $seen[$k] = true;
    $out[] = $ev;
    if (count($out) >= $size) break;
}

$response = [
    'events' => $out,
    'count' => count($out),
    'fetched_at' => date('c'),
];

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;