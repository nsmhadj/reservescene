GET https://www.eventbriteapi.com/v3/organizations/{organization_id}/events/?status=live
``` :contentReference[oaicite:0]{index=0}  

Donc :  
➡ Ton token est bon.  
➡ C’est juste **le mauvais endpoint**.

On corrige ça maintenant avec **un seul gros fichier PHP** 👇

---

## ✅ Nouveau `eventbrite_events.php` (version qui marche en 2025)

- Utilise ton **Private Token** (comme dans ton test).
- 1️⃣ Récupère tes organizations : `/v3/users/me/organizations/`
- 2️⃣ Prend la première (ou celle choisie avec `org_index`).
- 3️⃣ Récupère ses events : `/v3/organizations/{org_id}/events/`
- 4️⃣ Renvoie un **gros JSON propre** avec `events` dedans.

👉 Tu n’as qu’à **remplacer ton token** en haut.

```php
<?php
// eventbrite_events.php
//
// Un seul fichier qui :
//  1) récupère tes organizations Eventbrite
//  2) récupère les events d'une organization
//  3) renvoie un gros JSON pour ton front (comme events.php)

header('Content-Type: application/json; charset=utf-8');

// --------------------------------------------------
// 1. CONFIG : METS TON PRIVATE TOKEN ICI
// --------------------------------------------------
$EVENTBRITE_TOKEN = '"H4LWYGPWZL7FNXFFIR63'; // ← remplace par ton vrai token

if ($EVENTBRITE_TOKEN === 'TON_PRIVATE_TOKEN_ICI' || empty($EVENTBRITE_TOKEN)) {
    echo json_encode([
        'error'   => true,
        'step'    => 'config',
        'message' => 'Configure $EVENTBRITE_TOKEN avec ton vrai private token Eventbrite.'
    ]);
    exit;
}

// --------------------------------------------------
// Petite fonction utilitaire pour appeler l’API
// --------------------------------------------------
function eb_call(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return [
            'ok'       => false,
            'httpCode' => 0,
            'data'     => null,
            'error'    => 'Erreur cURL: ' . $curlErr,
            'rawBody'  => null,
        ];
    }

    $data = json_decode($response, true);

    return [
        'ok'       => $httpCode >= 200 && $httpCode < 300,
        'httpCode' => $httpCode,
        'data'     => $data,
        'error'    => $httpCode >= 200 && $httpCode < 300 ? null : 'HTTP '.$httpCode,
        'rawBody'  => $response,
    ];
}

// --------------------------------------------------
// 2. Récupérer les organizations de l’utilisateur
//    GET /v3/users/me/organizations/
// --------------------------------------------------

// Tu peux choisir l’index avec ?org_index=0,1,2...
$orgIndex = isset($_GET['org_index']) ? (int)$_GET['org_index'] : 0;
if ($orgIndex < 0) $orgIndex = 0;

$orgRes = eb_call('https://www.eventbriteapi.com/v3/users/me/organizations/', $EVENTBRITE_TOKEN);

if (!$orgRes['ok']) {
    echo json_encode([
        'error'     => true,
        'step'      => 'get_organizations',
        'http_code' => $orgRes['httpCode'],
        'message'   => 'Impossible de récupérer les organizations (users/me/organizations).',
        'details'   => $orgRes['data'] ?? $orgRes['error'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$orgData = $orgRes['data'] ?? [];
$orgs    = $orgData['organizations'] ?? [];

if (empty($orgs)) {
    echo json_encode([
        'error'   => true,
        'step'    => 'no_organizations',
        'message' => 'Aucune organization trouvée pour cet utilisateur Eventbrite.',
        'raw'     => $orgData,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// On choisit une organization (par défaut la première)
if (!isset($orgs[$orgIndex])) {
    // si l’index demandé n’existe pas, on retombe sur la première
    $orgIndex = 0;
}
$org = $orgs[$orgIndex];

$organizationId   = $org['id']   ?? null;
$organizationName = $org['name'] ?? null;

if (!$organizationId) {
    echo json_encode([
        'error'   => true,
        'step'    => 'no_org_id',
        'message' => 'Organization trouvée mais sans id.',
        'organization' => $org,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// --------------------------------------------------
// 3. Récupérer les events de cette organization
//    GET /v3/organizations/{organization_id}/events/
// --------------------------------------------------
//
// Tu peux filtrer avec ?status=live / completed / draft / all
// et ?page=1,2,3...
//
$status = isset($_GET['status']) ? $_GET['status'] : 'live';
$page   = isset($_GET['page'])   ? (int)$_GET['page'] : 1;
if ($page <= 0) $page = 1;

$query = [
    'status' => $status,
    'page'   => $page,
    'expand' => 'venue,organizer,category,subcategory,format',
];

$eventsUrl = 'https://www.eventbriteapi.com/v3/organizations/' . urlencode($organizationId) . '/events/?' . http_build_query($query);

$eventsRes = eb_call($eventsUrl, $EVENTBRITE_TOKEN);

if (!$eventsRes['ok']) {
    echo json_encode([
        'error'         => true,
        'step'          => 'get_events',
        'http_code'     => $eventsRes['httpCode'],
        'message'       => 'Impossible de récupérer les événements (organizations/{id}/events).',
        'organization'  => [
            'id'   => $organizationId,
            'name' => $organizationName,
        ],
        'details'       => $eventsRes['data'] ?? $eventsRes['error'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$eventsData = $eventsRes['data'] ?? [];
$events     = $eventsData['events'] ?? [];

// --------------------------------------------------
// 4. Réponse finale "gros JSON" pour ton front
// --------------------------------------------------

echo json_encode([
    'error'         => false,
    'source'        => 'eventbrite',
    'http_code'     => $eventsRes['httpCode'],
    'organization'  => [
        'id'   => $organizationId,
        'name' => $organizationName,
        'index'=> $orgIndex,
    ],
    'organizations_count' => count($orgs),
    'total'         => $eventsData['pagination']['object_count'] ?? count($events),
    'page'          => $eventsData['pagination']['page_number'] ?? $page,
    'page_size'     => $eventsData['pagination']['page_size'] ?? count($events),
    'events'        => $events, // brut : utilise ça côté JS
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
