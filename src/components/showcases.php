<?php


ini_set('display_errors', 0);
error_reporting(E_ALL);

@include_once __DIR__ . '/../includes/helpers.php';
$bootstrap = __DIR__ . '/../../config/bootstrap.php';
if (file_exists($bootstrap)) require_once $bootstrap;



@include_once __DIR__ . '/../api/seatgeek.php';

$TM_API_KEY = getenv('TM_API_KEY') ?: (defined('TICKETMASTER_API_KEY') ? TICKETMASTER_API_KEY : '');
$SG_CLIENT_ID = getenv('SEATGEEK_CLIENT_ID') ?: (defined('SEATGEEK_CLIENT_ID') ? SEATGEEK_CLIENT_ID : '');
$TM_ENDPOINT = 'https://app.ticketmaster.com/discovery/v2/events.json';
$CACHE_TTL = 60;


function fetch_url(string $url, int $cacheTtl = 60, int $timeout = 8) {
    if (function_exists('fetch_with_cache')) {
        try { $r = fetch_with_cache($url, $cacheTtl); if ($r !== false) return $r; } catch (Throwable $e) { error_log('[showcases] fetch_with_cache: '.$e->getMessage()); }
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'ReserveScene/showcases',
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) { error_log('[showcases] curl error: '.$err.' for '.$url); return false; }
    if ($code >= 400) { error_log('[showcases] upstream HTTP '.$code.' for '.$url); return false; }
    return $raw;
}

function fetch_tm_events(string $classification = null, int $perPage = 24, string $city = ''): array {
    global $TM_ENDPOINT, $TM_API_KEY, $CACHE_TTL;
    if (empty($TM_API_KEY)) return [];
    $params = ['apikey'=>$TM_API_KEY,'size'=>$perPage,'page'=>0];
    if ($city !== '') $params['city'] = $city;
    if (!empty($classification)) $params['classificationName'] = $classification;
    $url = $TM_ENDPOINT . '?' . http_build_query($params);
    $raw = fetch_url($url, $CACHE_TTL, 8);
    if ($raw === false) return [];
    $dec = @json_decode($raw, true);
    if (!is_array($dec) || empty($dec['_embedded']['events'])) return [];
    $events = $dec['_embedded']['events'];
    foreach ($events as &$e) {
        if (empty($e['id'])) $e['id'] = 'tm:' . uniqid();
        if (empty($e['images']) || !is_array($e['images'])) $e['images'] = [];
        $e['_source'] = 'tm';
    } unset($e);
    return $events;
}

function fetch_sk_raw(int $perPage = 80, string $city = '', string $keyword = ''): array {
    global $CACHE_TTL, $SG_CLIENT_ID;
    if (function_exists('seatgeek_search')) {
        $opts = ['keyword'=>$keyword,'city'=>$city,'per_page'=>$perPage,'page'=>1,'cache_ttl'=>$CACHE_TTL,'timeout'=>8];
        $res = seatgeek_search($opts);
        if (is_array($res) && isset($res['events']) && is_array($res['events'])) return $res['events'];
        error_log('[showcases] seatgeek_search error: '.substr(var_export($res, true),0,200));
        return [];
    }
    $clientId = $SG_CLIENT_ID;
    if (!$clientId) { error_log('[showcases] SEATGEEK_CLIENT_ID missing'); return []; }
    $base = 'https://api.seatgeek.com/2/events';
    $params = ['client_id'=>$clientId,'per_page'=>$perPage,'page'=>1];
    if ($keyword !== '') $params['q'] = $keyword;
    if ($city !== '') $params['venue.city'] = $city;
    $url = $base . '?' . http_build_query($params);
    $raw = fetch_url($url, $CACHE_TTL, 8);
    if ($raw === false) return [];
    $dec = @json_decode($raw, true);
    if (!is_array($dec) || !isset($dec['events'])) { error_log('[showcases] SeatGeek direct invalid JSON'); return []; }
    return $dec['events'];
}

function map_sk_to_tm_like(array $sk): array {
    $m = [];
    $m['id'] = 'sk:' . ($sk['id'] ?? uniqid('sk_'));
    $m['name'] = $sk['title'] ?? ($sk['short_title'] ?? ($sk['performers'][0]['name'] ?? ''));
    $m['images'] = [];
    if (!empty($sk['performers']) && is_array($sk['performers'])) {
        foreach ($sk['performers'] as $p) {
            $img = $p['image'] ?? ($p['image_url'] ?? ($p['images']['huge'] ?? null));
            if ($img) $m['images'][] = ['url'=>$img];
        }
    }
    if (empty($m['images']) && !empty($sk['image'])) $m['images'][] = ['url'=>$sk['image']];
    $m['dates'] = ['start'=>[]];
    if (!empty($sk['datetime_local'])) {
        $m['dates']['start']['localDate'] = substr($sk['datetime_local'], 0, 10);
        $m['dates']['start']['localTime'] = substr($sk['datetime_local'], 11);
    }
    if (!empty($sk['datetime_utc'])) $m['dates']['start']['dateTime'] = $sk['datetime_utc'];
    $m['_embedded'] = [];
    if (!empty($sk['venue'])) {
        $m['_embedded']['venues'] = [[
            'name'=>$sk['venue']['name'] ?? null,
            'city'=>['name'=>$sk['venue']['city'] ?? null],
        ]];
    }
    if (!empty($sk['performers'])) {
        $m['_embedded']['attractions'] = [];
        foreach ($sk['performers'] as $p) $m['_embedded']['attractions'][] = ['name'=>$p['name'] ?? null];
    }
    $m['type'] = $sk['type'] ?? ($sk['taxonomies'][0]['name'] ?? null);
    $m['taxonomies'] = $sk['taxonomies'] ?? [];
    $m['orig'] = $sk;
    $m['_source'] = 'sk';
    return $m;
}

function sk_event_matches_category(array $ev, string $categoryKey): bool {
    if (!$categoryKey || $categoryKey === 'all') return true;
    $map = ['music'=>'concert','comedy'=>'comedy','theatre'=>'theater','theater'=>'theater'];
    $token = $map[strtolower($categoryKey)] ?? strtolower($categoryKey);

    $orig = $ev['orig'] ?? [];

    $black = ['parking','hotel','merch','package','shuttle','transport','valet','parkingpass','garage'];
    $blob = strtolower(implode(' ', array_filter([
        $orig['title'] ?? '',
        $orig['short_title'] ?? '',
        $orig['type'] ?? '',
        implode(' ', array_map(function($t){ return is_array($t) ? implode(' ',$t) : (string)$t; }, $orig['taxonomies'] ?? []))
    ])));
    foreach ($black as $b) if ($b !== '' && strpos($blob,$b)!==false) return false;

    $type = strtolower((string)($ev['type'] ?? $orig['type'] ?? ''));
    if ($type !== '' && strpos($type, $token) !== false) return true;

    $taxes = [];
    if (!empty($orig['taxonomies']) && is_array($orig['taxonomies'])) $taxes = $orig['taxonomies'];
    if (!empty($ev['taxonomies']) && is_array($ev['taxonomies'])) $taxes = array_merge($taxes, $ev['taxonomies']);
    foreach ($taxes as $tx) {
        $n = strtolower((string)($tx['seo_event_type'] ?? $tx['name'] ?? ''));
        if ($n !== '' && strpos($n,$token)!==false) return true;
    }

    if (!empty($orig['performers']) && is_array($orig['performers'])) {
        foreach ($orig['performers'] as $p) {
            if (!empty($p['taxonomies']) && is_array($p['taxonomies'])) {
                foreach ($p['taxonomies'] as $ptx) {
                    $pn = strtolower((string)($ptx['seo_event_type'] ?? $ptx['name'] ?? ''));
                    if ($pn !== '' && strpos($pn,$token)!==false) return true;
                }
            }
        }
    }

    if (!empty($ev['_embedded']['attractions']) && is_array($ev['_embedded']['attractions'])) {
        foreach ($ev['_embedded']['attractions'] as $a) {
            $n = strtolower((string)($a['name']??''));
            if ($n!=='' && strpos($n,$token)!==false) return true;
        }
    }
    $name = strtolower((string)($ev['name'] ?? $orig['title'] ?? ''));
    if ($name !== '' && strpos($name,$token)!==false) return true;

    return false;
}

function normalize_artist_name(string $s=''): string {
    $s = trim((string)$s);
    if ($s==='') return '';
    $s = preg_replace('/\([^)]*\)/u',' ',$s);
    $s = preg_replace('/\s*[\-\–\—]\s*.*/u',' ',$s);
    $s = mb_strtolower($s,'UTF-8');
    if (function_exists('iconv')) { $t=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s); if($t!==false) $s=$t; }
    $s = preg_replace('/[^a-z0-9\s]/u',' ',$s);
    $s = preg_replace('/\s+/u',' ',$s);
    return trim($s);
}
function extract_artist_candidates(array $ev): array {
    $c = [];
    if (!empty($ev['_embedded']['attractions']) && is_array($ev['_embedded']['attractions'])) {
        foreach ($ev['_embedded']['attractions'] as $a) if (!empty($a['name'])) $c[] = (string)$a['name'];
    }
    if (!empty($ev['name'])) $c[] = (string)$ev['name'];
    if (!empty($ev['promoter']['name'])) $c[] = (string)$ev['promoter']['name'];
    $out = [];
    foreach ($c as $it) { $it = trim($it); if ($it!=='' && !in_array($it,$out,true)) $out[]=$it; }
    return $out;
}
function canonical_artist_for_event(array $ev): string {
    $cands = extract_artist_candidates($ev);
    foreach ($cands as $c) {
        $n = normalize_artist_name($c);
        if ($n!=='') return $n;
    }
    if (!empty($ev['id'])) return normalize_artist_name((string)$ev['id']);
    if (!empty($ev['name'])) return normalize_artist_name((string)$ev['name']);
    return uniqid('unknown_');
}
function dedupe_key(array $ev): string {
    $name = mb_strtolower(trim((string)($ev['name'] ?? $ev['title'] ?? '')),'UTF-8');
    $name = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$name) ?: $name;
    $name = preg_replace('/[^a-z0-9\s]/u','',$name);
    $name = preg_replace('/\s+/u',' ',$name);
    $date = $ev['dates']['start']['localDate'] ?? ($ev['dates']['start']['dateTime'] ?? ($ev['datetime_local'] ?? ''));
    $date = substr((string)$date,0,10);
    return trim($name) . '|' . $date;
}


$rows = [
    ['id'=>'rowMusic','label'=>'Musique','category'=>'music','size'=>6,'classification'=>'Music'],
    ['id'=>'rowComedy','label'=>'Comédie','category'=>'comedy','size'=>6,'classification'=>'Comedy'],
    ['id'=>'rowTheatre','label'=>'Théâtre','category'=>'theatre','size'=>6,'classification'=>'Theatre'],
];

$usedArtistNames = [];
$usedEventIds = [];


$total_used_tm = 0;
$total_used_sk = 0;

foreach ($rows as $r) {
    $desired = max(1, (int)$r['size']);

    $tmCandidates = [];
    if (!empty($r['classification'])) {
        
        $tmPool = fetch_tm_events($r['classification'], max(12, $desired * 3), '');
        
        foreach ($tmPool as $ev) {
            
            $tmCandidates[] = $ev;
        }
    }

    
    if (!empty($tmCandidates)) shuffle($tmCandidates);

    $selected = [];
    
    foreach ($tmCandidates as $ev) {
        if (count($selected) >= $desired) break;
        $evId = $ev['id'] ?? null;
        $canon = canonical_artist_for_event($ev);
        if ($canon !== '' && isset($usedArtistNames[$canon])) continue;
        if ($evId !== null && isset($usedEventIds[$evId])) continue;
        $selected[] = $ev;
        if ($canon !== '') $usedArtistNames[$canon] = true;
        if ($evId !== null) $usedEventIds[$evId] = true;
        $total_used_tm++;
    }

   
    if (count($selected) < $desired) {
      
        $skRaw = fetch_sk_raw(max(40, $desired * 6), '', '');
        $skMapped = [];
        foreach ($skRaw as $sk) {
          
            $m = map_sk_to_tm_like($sk);
            $m['orig'] = $sk;
            if (!sk_event_matches_category($m, $r['category'])) continue; // keep only matching category
            $skMapped[] = $m;
        }

        if (!empty($skMapped)) shuffle($skMapped);

        foreach ($skMapped as $ev) {
            if (count($selected) >= $desired) break;
            $evId = $ev['id'] ?? null;
            $canon = canonical_artist_for_event($ev);
            if ($canon !== '' && isset($usedArtistNames[$canon])) continue;
            if ($evId !== null && isset($usedEventIds[$evId])) continue;
            $selected[] = $ev;
            if ($canon !== '') $usedArtistNames[$canon] = true;
            if ($evId !== null) $usedEventIds[$evId] = true;
            $total_used_sk++;
        }
    }

    if (count($selected) < $desired) {
      
        $mergedPool = array_merge($tmCandidates, $skMapped ?? []);
        foreach ($mergedPool as $ev) {
            if (count($selected) >= $desired) break;
            if (in_array($ev, $selected, true)) continue;
            $evId = $ev['id'] ?? null;
            if ($evId !== null && isset($usedEventIds[$evId])) continue;
            $selected[] = $ev;
            if ($evId !== null) $usedEventIds[$evId] = true;
            if (($ev['_source'] ?? '') === 'tm') $total_used_tm++; else $total_used_sk++;
        }
    }

    echo '<div class="showcase-block">';
    echo '<h2 class="showcase-title">' . htmlspecialchars($r['label']) . '</h2>';
    echo '<div class="showcase-row" id="' . htmlspecialchars($r['id']) . '">';

    if (empty($selected)) {
        echo '<div class="showcase-item" style="width:100%;height:80px;display:flex;align-items:center;justify-content:center"><div>Aucun événement</div></div>';
    } else {
        foreach ($selected as $ev) {
        
            if (function_exists('choose_image_url')) {
                try { $img = choose_image_url($ev); } catch (Throwable $e) { $img = null; }
            }
            if (!$img && !empty($ev['images'][0]['url'])) $img = $ev['images'][0]['url'];
            $eventId = $ev['id'] ?? '';
            $resultUrl = $eventId ? 'resultat.php?id=' . rawurlencode($eventId) : '#';
            echo '<div class="showcase-item">';
            if ($img) {
                if ($eventId) echo '<a href="' . htmlspecialchars($resultUrl) . '"><img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($ev['name'] ?? '') . '"></a>';
                else echo '<img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($ev['name'] ?? '') . '">';
            } else {
                echo '<div style="width:160px;height:90px;background:#f3f3f3;display:flex;align-items:center;justify-content:center;color:#999;border:1px solid #eee">No image</div>';
            }
            $src = htmlspecialchars(strtoupper((string)($ev['_source'] ?? 'SK')));
            echo '<div style="position:relative;line-height:0"><span style="position:absolute;bottom:8px;left:8px;background:rgba(0,0,0,0.6);color:#fff;padding:2px 6px;font-size:11px;border-radius:3px">' . $src . '</span></div>';
            echo '</div>';
        }
    }

    echo '</div></div>';
}

error_log("[showcases] total used TM={$total_used_tm}, SK={$total_used_sk}");