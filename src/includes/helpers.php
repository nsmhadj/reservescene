<?php
/**
 * helpers.php
 * Fonctions partagées. Inclure via include_once __DIR__ . '/../src/includes/helpers.php'; (from public/)
 * or include_once __DIR__ . '/../includes/helpers.php'; (from src/pages/)
 */

// h()
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// fetch_with_cache()
if (!function_exists('fetch_with_cache')) {
    function fetch_with_cache($url, $ttl = 60) {
        $cacheFile = sys_get_temp_dir() . '/tm_shared_' . md5($url) . '.json';
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $ttl)) {
            $raw = @file_get_contents($cacheFile);
            if ($raw !== false) return $raw;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return json_encode(['error' => 'Curl error', 'details' => $err, 'http' => $http, 'url' => $url]);
        }
        if ($http >= 400) {
            return $resp;
        }
        @file_put_contents($cacheFile, $resp);
        return $resp;
    }
}

// choose_image_url()
if (!function_exists('choose_image_url')) {
    function choose_image_url(array $event) {
        if (empty($event['images']) || !is_array($event['images'])) return null;
        foreach ($event['images'] as $img) {
            if (!empty($img['ratio']) && stripos($img['ratio'], '16') !== false && !empty($img['url'])) {
                return $img['url'];
            }
        }
        foreach ($event['images'] as $img) {
            if (!empty($img['url'])) return $img['url'];
        }
        return null;
    }
}

// shuffle_array_safe()
if (!function_exists('shuffle_array_safe')) {
    function shuffle_array_safe(array $arr) {
        $a = $arr;
        for ($i = count($a) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            $tmp = $a[$i];
            $a[$i] = $a[$j];
            $a[$j] = $tmp;
        }
        return $a;
    }
}

// get_artist_name()
if (!function_exists('get_artist_name')) {
    function get_artist_name(array $ev) : string {
        if (!empty($ev['_embedded']['attractions']) && is_array($ev['_embedded']['attractions']) && count($ev['_embedded']['attractions'])) {
            return trim((string)($ev['_embedded']['attractions'][0]['name'] ?? ''));
        }
        return trim((string)($ev['name'] ?? ''));
    }
}

/**
 * Extract price info from Ticketmaster event object (if present).
 * Returns array: ['min' => float|null, 'max' => float|null, 'currency' => string|null]
 */
if (!function_exists('extract_event_price')) {
    function extract_event_price(array $ev) : array {
        $out = ['min' => null, 'max' => null, 'currency' => null];

        if (!empty($ev['priceRanges']) && is_array($ev['priceRanges'])) {
            $pr = $ev['priceRanges'][0];
            $out['min'] = isset($pr['min']) ? (float)$pr['min'] : null;
            $out['max'] = isset($pr['max']) ? (float)$pr['max'] : null;
            $out['currency'] = isset($pr['currency']) ? strtoupper($pr['currency']) : null;
            return $out;
        }

        if (!empty($ev['offers']) && is_array($ev['offers'])) {
            foreach ($ev['offers'] as $off) {
                if (isset($off['price'])) {
                    $out['min'] = (float)$off['price'];
                    $out['currency'] = isset($off['currency']) ? strtoupper($off['currency']) : (isset($off['priceCurrency']) ? strtoupper($off['priceCurrency']) : null);
                    return $out;
                }
                if (isset($off['minPrice'])) {
                    $out['min'] = (float)$off['minPrice'];
                    $out['currency'] = isset($off['currency']) ? strtoupper($off['currency']) : (isset($off['priceCurrency']) ? strtoupper($off['priceCurrency']) : null);
                    return $out;
                }
            }
        }

        return $out;
    }
}

/**
 * Generate a deterministic "random" price for an event id
 * - seed: a string (event id) so price is stable across reloads
 * - min/max: integers (e.g. 25, 70)
 * Returns float (2 decimals) e.g. 24.99
 */
if (!function_exists('generate_deterministic_price')) {
    function generate_deterministic_price(string $seed, int $min = 25, int $max = 70) : float {
        $seed = (string)$seed;
        $hash = sprintf('%u', crc32($seed));
        $hashInt = (int)($hash % 1000000);

        $range = max(1, $max - $min);
        $intPart = $min + ($hashInt % $range);

        // realistic endings with weights embedded by selection
        $endings = [0.99, 0.50, 0.00, 0.90];
        $idx = ($hashInt >> 8) % count($endings);
        $fraction = $endings[$idx];

        $price = round($intPart + $fraction, 2);

        if ($price < $min) $price = (float)$min;
        if ($price > $max) $price = (float)$max;

        return $price;
    }
}

/**
 * Format number to French euro display: 24,99 €
 */
if (!function_exists('format_money_eur')) {
    function format_money_eur($amount) : string {
        if ($amount === null || $amount === '') return '';
        $num = number_format((float)$amount, 2, ',', ' ');
        return $num . ' €';
    }
}

/**
 * Return a display object for an event price:
 * ['display' => string (already formatted), 'estimated' => bool, 'value' => float, 'currency' => string]
 */
if (!function_exists('get_price_display_for_event')) {
    function get_price_display_for_event(array $ev) : array {
        $ex = extract_event_price($ev);
        if ($ex['min'] !== null) {
            $currency = $ex['currency'] ?? 'EUR';
            $value = $ex['min'];
            $display = ($currency === 'EUR') ? format_money_eur($value) : (number_format((float)$value,2,',',' ') . ' ' . h($currency));
            return ['display' => $display, 'estimated' => false, 'value' => $value, 'currency' => $currency];
        }

        // fallback deterministic random based on id
        $seed = $ev['id'] ?? ($ev['name'] ?? uniqid('evt_'));
        $price = generate_deterministic_price((string)$seed, 25, 70);
        $display = format_money_eur($price);
        return ['display' => $display, 'estimated' => true, 'value' => $price, 'currency' => 'EUR'];
    }
}