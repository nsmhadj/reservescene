<?php
if (!defined('SEATGEEK_CLIENT_ID')) {
    $bootstrap = __DIR__ . '/../../config/bootstrap.php';
    if (file_exists($bootstrap)) {
        require_once $bootstrap;
    }
}
if (!function_exists('seatgeek_search')) {
    function seatgeek_search(array $opts = []): array {
        
        $clientId = (defined('SEATGEEK_CLIENT_ID') && SEATGEEK_CLIENT_ID) ? SEATGEEK_CLIENT_ID : getenv('SEATGEEK_CLIENT_ID');
        $clientSecret = (defined('SEATGEEK_CLIENT_SECRET') && SEATGEEK_CLIENT_SECRET) ? SEATGEEK_CLIENT_SECRET : getenv('SEATGEEK_CLIENT_SECRET');

        
        if (!$clientId) $clientId =  getenv('SEATGEEK_CLIENT_ID');
        if (!$clientSecret) $clientSecret = getenv('SEATGEEK_CLIENT_SECRET');

        if (!$clientId) {
            return ['error' => 'SeatGeek credentials missing: set SEATGEEK_CLIENT_ID (and optionally SEATGEEK_CLIENT_SECRET).'];
        }

        $base = 'https://api.seatgeek.com/2/events';
        $params = [];

        if (!empty($opts['keyword'])) $params['q'] = (string)$opts['keyword'];
        if (!empty($opts['city'])) $params['venue.city'] = (string)$opts['city'];
        if (!empty($opts['per_page'])) $params['per_page'] = (int)$opts['per_page'];
        if (!empty($opts['page'])) $params['page'] = (int)$opts['page'];

        if (!empty($opts['startDateTime'])) $params['datetime_local.gte'] = (string)$opts['startDateTime'];
        if (!empty($opts['endDateTime']))   $params['datetime_local.lte'] = (string)$opts['endDateTime'];

     
        $params['client_id'] = $clientId;
        if ($clientSecret) $params['client_secret'] = $clientSecret;

        $url = $base . '?' . http_build_query($params);
        $cacheTtl = isset($opts['cache_ttl']) ? max(0, (int)$opts['cache_ttl']) : 60;

 
        if (function_exists('fetch_with_cache')) {
            $raw = fetch_with_cache($url, $cacheTtl);
            if ($raw === false || $raw === null) {
                return ['error' => 'SeatGeek fetch failed (cache)'];
            }
            $data = @json_decode($raw, true);
            if (!is_array($data)) {
                return ['error' => 'Invalid JSON from SeatGeek (cache)', 'raw' => $raw];
            }
            if (!isset($data['events'])) {
                return ['error' => 'SeatGeek response missing events', 'raw' => $raw];
            }
            return ['events' => $data['events']];
        }

        
        $timeout = isset($opts['timeout']) ? max(1, (int)$opts['timeout']) : 10;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'ReserveScene/SeatGeek',
        ]);
        $raw = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['error' => 'SeatGeek cURL error: ' . $curlErr];
        }

        $data = @json_decode($raw, true);
        if (!is_array($data)) {
            return ['error' => 'Invalid JSON from SeatGeek', 'raw' => $raw];
        }

        if ($httpCode >= 400) {
            return ['error' => "SeatGeek API returned HTTP {$httpCode}", 'raw' => $raw];
        }

        if (!isset($data['events'])) {
            return ['error' => 'SeatGeek response missing events', 'raw' => $raw];
        }

        return ['events' => $data['events']];
    }
}