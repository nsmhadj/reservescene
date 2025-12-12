<?php


header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$API_KEY  = getenv('TM_API_KEY') ?: '63ukbG5uWxs4cm2VFh7H3JDtN8CGFSPl';
$BASE_URL = 'https://app.ticketmaster.com/discovery/v2/events.json';
$SIZE     = 200; 
$TIMEOUT  = 10;


if (!$API_KEY || $API_KEY === 'YOUR_TICKETMASTER_API_KEY') {
    http_response_code(500);
    echo json_encode(['error' => 'Missing Ticketmaster API key.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$params = [
    'apikey' => $API_KEY,
    'size'   => $SIZE,
];
$forward = [
    'keyword','countryCode','city','classificationName','startDateTime','endDateTime',
    'latlong','radius','unit','marketId','dmaId','segmentId','genreId','subGenreId'
];
foreach ($forward as $k) {
    if (!empty($_GET[$k])) $params[$k] = $_GET[$k];
}
$url = $BASE_URL . '?' . http_build_query($params);


$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => $TIMEOUT,
    CURLOPT_TIMEOUT        => $TIMEOUT,
    CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    CURLOPT_USERAGENT      => 'events.php (+your site)',
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false) {
    http_response_code(502);
    echo json_encode(['error' => $err], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($code >= 400) {
    http_response_code($code);
    echo json_encode(['error' => "Ticketmaster API returned $code"], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($resp, true);
if (!$data) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid JSON from Ticketmaster'], JSON_UNESCAPED_UNICODE);
    exit;
}


$events = $data['_embedded']['events'] ?? [];
$events = array_slice($events, 0, 200); 

echo json_encode([
    'count'  => count($events),
    'events' => $events,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
