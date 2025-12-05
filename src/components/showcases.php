<?php
/**
 * showcases.php - rendu serveur pour les lignes de showcases (strong dedupe by artist)
 *
 * Goal:
 * - ensure every displayed event across all showcase rows is from a different artist
 * - aggressively normalize artist names to catch small variations ("Artist", "Artist - Live", "The Artist", "Artist (FR)", etc.)
 * - prefer one date per artist (earliest if multiple exist)
 *
 * Replace your existing showcases.php with this file.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../../config/bootstrap.php' ;

// Ticketmaster API key from environment variable
$TM_API_KEY = getenv('TM_API_KEY') ;
$API_ENDPOINT = 'https://app.ticketmaster.com/discovery/v2/events.json';
$CACHE_TTL = 60;

/* same fetch helper as before (keeps behaviour) */
function fetch_events_for_category(string $categoryKey, int $size = 6, string $city = '') {
    global $TM_API_KEY, $API_ENDPOINT, $CACHE_TTL;

    $categoryMap = [
        'music'   => 'Music',
        'comedy'  => 'Comedy',
        'theatre' => 'Theatre',
        'sports'  => 'Sports',
        'all'     => null,
        'other'   => 'other',
    ];

    if (empty($TM_API_KEY)) {
        return ['error' => 'TM_API_KEY not configured'];
    }

    $params = [
        'apikey' => $TM_API_KEY,
        'size' => $size,
        'page' => 0,
    ];
    if ($city !== '') $params['city'] = $city;

    $mapVal = $categoryMap[$categoryKey] ?? null;
    if ($mapVal !== null && $mapVal !== 'other') {
        $params['classificationName'] = $mapVal;
    }

    $url = $API_ENDPOINT . '?' . http_build_query($params);
    $raw = fetch_with_cache($url, $CACHE_TTL);
    $decoded = json_decode($raw, true);

    if ($decoded === null) {
        return ['error' => 'Invalid JSON from upstream', 'raw' => $raw, 'requested_url' => $url];
    }
    if (isset($decoded['errors'])) {
        return ['error' => 'Upstream error', 'details' => $decoded['errors'], 'requested_url' => $url];
    }

    $events = [];
    if (!empty($decoded['_embedded']['events']) && is_array($decoded['_embedded']['events'])) {
        $events = $decoded['_embedded']['events'];
    }

    if ($categoryKey === 'other' && !empty($events)) {
        $main = array_map('strtolower', ['Music','Comedy','Theatre','Sports']);
        $filtered = [];
        foreach ($events as $ev) {
            $labels = [];
            if (!empty($ev['classifications']) && is_array($ev['classifications'])) {
                foreach ($ev['classifications'] as $cls) {
                    if (!empty($cls['segment']['name'])) $labels[] = strtolower($cls['segment']['name']);
                    if (!empty($cls['genre']['name'])) $labels[] = strtolower($cls['genre']['name']);
                    if (!empty($cls['subGenre']['name'])) $labels[] = strtolower($cls['subGenre']['name']);
                    if (!empty($cls['family']['name'])) $labels[] = strtolower($cls['family']['name']);
                }
            }
            $isMain = false;
            foreach ($labels as $lab) {
                foreach ($main as $m) {
                    if ($m !== '' && strpos($lab, $m) !== false) {
                        $isMain = true;
                        break 2;
                    }
                }
            }
            if (!$isMain) $filtered[] = $ev;
        }
        $events = $filtered;
    }

    return ['events' => $events, 'meta' => $decoded['page'] ?? null, 'requested_url' => $url];
}

/* deterministic reorder using time-based salt to vary selection periodically */
function reorder_events_with_salt(array $events, int $saltPeriodSec = 21600) {
    $salt = (int)floor(time() / max(1, $saltPeriodSec));
    usort($events, function($a, $b) use ($salt) {
        $ida = (string)($a['id'] ?? ($a['url'] ?? ($a['name'] ?? uniqid())));
        $idb = (string)($b['id'] ?? ($b['url'] ?? ($b['name'] ?? uniqid())));
        $ha = sprintf('%u', crc32($ida . $salt));
        $hb = sprintf('%u', crc32($idb . $salt));
        if ($ha === $hb) return 0;
        return ($ha < $hb) ? -1 : 1;
    });
    return $events;
}

/* Aggressive normalization to get a canonical artist key */
function normalize_artist_name(string $s = '') : string {
    $s = trim((string)$s);
    if ($s === '') return '';

    // remove parenthesized parts e.g. "Artist (FR)" -> "Artist"
    $s = preg_replace('/\([^)]*\)/u', ' ', $s);

    // remove content after " - " or " — " often used to append extras
    $s = preg_replace('/\s*[\-\–\—]\s*.*/u', ' ', $s);

    // lower
    $s = mb_strtolower($s, 'UTF-8');

    // transliterate accents to ASCII if possible
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) $s = $t;
    }

    // remove common noise words that don't change the core artist identity
    $noise = [
        ' live', ' concert', ' ft ', ' featuring ', ' with orchestra', ' with the orchestra',
        ' the film', ' film', ' presents ', ' saison ', ' tour', ' featuring', ' feat ', ' ft.',
    ];
    foreach ($noise as $n) {
        $s = str_replace($n, ' ', $s);
    }

    // strip punctuation -> leave letters and numbers and spaces
    $s = preg_replace('/[^a-z0-9\s]/u', ' ', $s);

    // remove leading articles (english/french) like "the", "le", "la", "les", "l'"
    $s = preg_replace('/^\s*(the|le|la|les|l|l\')\s+/u', '', $s);

    // collapse spaces
    $s = preg_replace('/\s+/u', ' ', $s);
    $s = trim($s);

    return $s;
}

/* helper to extract a reliable artist string from event (more robust than just first attraction) */
function extract_artist_candidates(array $ev) : array {
    $candidates = [];

    // try attractions (often best)
    if (!empty($ev['_embedded']['attractions']) && is_array($ev['_embedded']['attractions'])) {
        foreach ($ev['_embedded']['attractions'] as $attr) {
            if (!empty($attr['name'])) $candidates[] = (string)$attr['name'];
        }
    }

    // fallback to event name
    if (!empty($ev['name'])) $candidates[] = (string)$ev['name'];

    // also try promoter/venue/other fields if present (rare)
    if (!empty($ev['promoter']['name'])) $candidates[] = (string)$ev['promoter']['name'];

    // unique, preserve order
    $out = [];
    foreach ($candidates as $c) {
        $c = trim($c);
        if ($c === '') continue;
        if (!in_array($c, $out, true)) $out[] = $c;
    }
    return $out;
}

/* choose canonical normalized artist for an event (tries several candidates) */
function canonical_artist_for_event(array $ev) : string {
    $cands = extract_artist_candidates($ev);
    foreach ($cands as $c) {
        $norm = normalize_artist_name($c);
        if ($norm !== '') return $norm;
    }
    // fallback to normalized event id/name
    if (!empty($ev['id'])) return normalize_artist_name((string)$ev['id']);
    if (!empty($ev['name'])) return normalize_artist_name((string)$ev['name']);
    return uniqid('unknown_');
}

/* helper: prefer earliest date among events for the same artist if available */
function event_start_timestamp(array $ev) {
    // try ISO datetime -> timestamp; fallback to localDate+localTime (approx)
    if (!empty($ev['dates']['start']['dateTime'])) {
        $dt = strtotime($ev['dates']['start']['dateTime']);
        if ($dt !== false) return $dt;
    }
    if (!empty($ev['dates']['start']['localDate'])) {
        $d = $ev['dates']['start']['localDate'];
        $t = $ev['dates']['start']['localTime'] ?? '00:00:00';
        $dt = strtotime($d . ' ' . $t);
        if ($dt !== false) return $dt;
    }
    return PHP_INT_MAX;
}

/* configuration */
$rows = [
    ['id' => 'rowMusic', 'label' => 'Musique', 'key' => 'music', 'size' => 6],
    ['id' => 'rowComedy', 'label' => 'Comédie', 'key' => 'comedy', 'size' => 6],
    ['id' => 'rowTheatre', 'label' => 'Théâtre', 'key' => 'theatre', 'size' => 6],
];

$usedArtistNames = []; // canonical normalized names used across all rows
$usedEventIds = [];    // event ids used across all rows

$candidateMultiplier = 4;
$saltPeriodSec = 6 * 3600;

foreach ($rows as $r) {
    $desired = max(1, (int)($r['size'] ?? 6));
    $fetchSize = max($desired, $desired * $candidateMultiplier);

    $result = fetch_events_for_category($r['key'], $fetchSize);
    echo '<div class="showcase-block">';
    echo '<h2 class="showcase-title">' . h($r['label']) . '</h2>';
    echo '<div class="showcase-row" id="' . h($r['id']) . '">';

    if (isset($result['error'])) {
        echo '<div class="showcase-item" style="width:100%;height:80px;display:flex;align-items:center;justify-content:center">';
        echo '<div style="color:#b00">Erreur: ' . h(is_string($result['error']) ? $result['error'] : json_encode($result['error'])) . '</div>';
        echo '</div>';
        echo '</div></div>';
        continue;
    }

    $candidates = $result['events'] ?? [];
    if (empty($candidates)) {
        echo '<div class="showcase-item" style="width:100%;height:80px;display:flex;align-items:center;justify-content:center"><div>Aucun événement</div></div>';
        echo '</div></div>';
        continue;
    }

    // reorder to get variety over time
    $candidates = reorder_events_with_salt($candidates, $saltPeriodSec);

    // First: bucket candidates by canonical artist, keep the earliest event per artist
    $artistBuckets = [];
    foreach ($candidates as $ev) {
        $canon = canonical_artist_for_event($ev);
        $ts = event_start_timestamp($ev);
        if (!isset($artistBuckets[$canon])) {
            $artistBuckets[$canon] = $ev + ['__start_ts' => $ts];
        } else {
            // keep earliest date for that artist
            $existingTs = $artistBuckets[$canon]['__start_ts'] ?? PHP_INT_MAX;
            if ($ts < $existingTs) {
                $artistBuckets[$canon] = $ev + ['__start_ts' => $ts];
            }
        }
    }

    // Flatten while preserving the salted order of the original candidates:
    // keep only one event per artist, but in the order defined by $candidates above.
    $seenArtistLocal = [];
    $uniqCandidates = [];
    foreach ($candidates as $ev) {
        $canon = canonical_artist_for_event($ev);
        if ($canon === '') continue;
        if (isset($seenArtistLocal[$canon])) continue;
        if (!isset($artistBuckets[$canon])) continue;
        $uniqCandidates[] = $artistBuckets[$canon];
        $seenArtistLocal[$canon] = true;
    }

    // Now select up to $desired events skipping any artists/events already used globally
    $selected = [];
    foreach ($uniqCandidates as $ev) {
        $evId = $ev['id'] ?? ($ev['url'] ?? null);
        $canon = canonical_artist_for_event($ev);

        if ($canon !== '' && isset($usedArtistNames[$canon])) continue;
        if ($evId !== null && isset($usedEventIds[$evId])) continue;

        $selected[] = $ev;
        if ($canon !== '') $usedArtistNames[$canon] = true;
        if ($evId !== null) $usedEventIds[$evId] = true;

        if (count($selected) >= $desired) break;
    }

    // If we still don't have enough (rare), allow filling with remaining uniqCandidates ignoring usedArtistNames,
    // but keep event IDs unique.
    if (count($selected) < $desired) {
        foreach ($uniqCandidates as $ev) {
            if (in_array($ev, $selected, true)) continue;
            $evId = $ev['id'] ?? ($ev['url'] ?? null);
            if ($evId !== null && isset($usedEventIds[$evId])) continue;
            $selected[] = $ev;
            if ($evId !== null) $usedEventIds[$evId] = true;
            if (count($selected) >= $desired) break;
        }
    }

    // Render
    if (empty($selected)) {
        echo '<div class="showcase-item" style="width:100%;height:80px;display:flex;align-items:center;justify-content:center"><div>Aucun événement</div></div>';
    } else {
        foreach ($selected as $ev) {
            $img = choose_image_url($ev);
            $eventId = $ev['id'] ?? '';
            $resultUrl = $eventId ? 'resultat.php?id=' . rawurlencode($eventId) : '#';

            echo '<div class="showcase-item">';
            if ($img) {
                if ($eventId) {
                    echo '<a href="' . h($resultUrl) . '"><img src="' . h($img) . '" alt="' . h($ev['name'] ?? '') . '"></a>';
                } else {
                    echo '<img src="' . h($img) . '" alt="' . h($ev['name'] ?? '') . '">';
                }
            } else {
                echo '<div style="width:160px;height:90px;background:#f3f3f3;display:flex;align-items:center;justify-content:center;color:#999;border:1px solid #eee">No image</div>';
            }
            echo '</div>';
        }
    }

    echo '</div></div>';
}