<?php


ini_set('display_errors', 0);
error_reporting(E_ALL);


include_once __DIR__ . '/../includes/helpers.php';


if (!function_exists('shorten_text')) {
    function shorten_text(string $s, int $max = 220): string {
        $s = trim($s);
        if ($s === '') return '';
        if (mb_strlen($s) <= $max) return $s;
      
        $cut = mb_substr($s, 0, $max);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }
        return $cut . '…';
    }
}
require_once __DIR__ . '/../../config/bootstrap.php' ;


$TM_API_KEY = getenv('TM_API_KEY') ;
$API_ENDPOINT = 'https://app.ticketmaster.com/discovery/v2/events.json';
$CACHE_TTL = 60;

try {
    if (empty($TM_API_KEY)) {
        echo '<div class="trending__empty">Erreur configuration API (TM_API_KEY manquante).</div>';
        return;
    }

    $params = [
        'apikey' => $TM_API_KEY,
        'classificationName' => 'Music',
        'size' => 50,
        'page' => 0,
    ];
    $url = $API_ENDPOINT . '?' . http_build_query($params);
    $raw = fetch_with_cache($url, $CACHE_TTL);
    $decoded = json_decode($raw, true);

    if (!is_array($decoded) || isset($decoded['errors'])) {
        echo '<div class="trending__empty">Impossible de récupérer les événements.</div>';
        return;
    }

    $events = $decoded['_embedded']['events'] ?? [];
    if (empty($events)) {
        echo '<div class="trending__empty">Aucun concert trouvé.</div>';
        return;
    }

    $events = shuffle_array_safe($events);


    $seen = [];
    $selected = [];
    foreach ($events as $ev) {
        $artist = mb_strtolower(get_artist_name($ev));
        if ($artist === '') continue;
        if (isset($seen[$artist])) continue;
        $seen[$artist] = true;
        $selected[] = $ev;
        if (count($selected) >= 3) break;
    }
    if (count($selected) < 3) {
        foreach ($events as $ev) {
            if (in_array($ev, $selected, true)) continue;
            $selected[] = $ev;
            if (count($selected) >= 3) break;
        }
    }

    echo '<div class="trending-cards">';
    foreach ($selected as $ev) {
        $img = choose_image_url($ev);
        $title = $ev['name'] ?? 'Concert';
        $desc = $ev['info'] ?? ($ev['pleaseNote'] ?? 'Concert à venir.');
        $urlEvent = $ev['url'] ?? '';
        $eventId = $ev['id'] ?? '';

   
        $resultUrl = $eventId ? 'resultat.php?id=' . rawurlencode($eventId) : '';

        $priceInfo = get_price_display_for_event($ev);
        $priceHtml = '<div class="trending-card__price">' . h($priceInfo['display']) . '</div>';

        echo '<article class="trending-card">';
        echo '<div class="trending-card__imgwrap">';
        if ($img) {
            echo '<img src="' . h($img) . '" alt="' . h($title) . '" class="trending-card__img">';
        }
        echo '</div>';
        echo '<div class="trending-card__body">';
        echo '<h3 class="trending-card__title">' . h($title) . '</h3>';
      
        echo '<p class="trending-card__desc">' . h(shorten_text($desc, 220)) . '</p>';
        echo $priceHtml;

        if ($resultUrl) {
            echo '<p><a href="' . h($resultUrl) . '" class="trending-card__btn">Voir / Acheter</a></p>';
        } elseif ($urlEvent) {
            echo '<p><a href="' . h($urlEvent) . '" target="_blank" rel="noopener" class="trending-card__btn">Voir / Acheter</a></p>';
        } else {
            echo '<p><button class="trending-card__btn" type="button">Voir</button></p>';
        }

        echo '</div></article>';
    }
    echo '</div>';

} catch (Throwable $e) {
    error_log('trending.php error: ' . $e->getMessage());
    echo '<div class="trending__empty">Erreur interne lors du rendu des tendances.</div>';
    return;
}