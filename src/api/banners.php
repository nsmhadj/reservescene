<?php


header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

$per_page = isset($_GET['per_page']) ? max(1, min(20, (int)$_GET['per_page'])) : 8;
$variant = isset($_GET['variant']) ? strtolower(trim((string)$_GET['variant'])) : 'music';

$curated_music = [
    ['url'=>'https://parisjetaime.com/data/layout_image/24553_Foule-concert--630x405--%C2%A9-DR-Pixhere_panoramic_2-1_l.jpg?ver=1700702737','title'=>'Live Concert','source'=>'unsplash'],
    ['url'=>'https://cdn.paris.fr/paris/2025/03/21/huge-81b73d782c506c2af02be61142afe290.jpg','title'=>'Stage Lights','source'=>'unsplash'],
    ['url'=>'https://www.opera-comique.com/sites/default/files/styles/width_1440px/public/2021-08/OperaComique_StefanBrion_1920x1080.png','title'=>'Crowd','source'=>'unsplash'],
    ['url'=>'https://cdn.paris.fr/paris/2024/12/26/huge-43bc10303eba8989cc01195205b5a80c.jpg','title'=>'Guitarist','source'=>'unsplash'],
    ['url'=>'https://www.adobe.com/fr/creativecloud/photography/discover/media_15955bf89f635a586d897b5c35f7a447b495f6ed7.jpg?width=1200&format=pjpg&optimize=medium','title'=>'Singer','source'=>'unsplash'],
    ['url'=>'https://www.radiofrance.fr/pikapi/images/c58c98cf-30e8-4db0-8004-111b0314de96/1200x680?webp=false','title'=>'DJ','source'=>'unsplash'],
    ['url'=>'https://images.unsplash.com/photo-1613093335399-829e30811789?fm=jpg&q=60&w=3000&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D','title'=>'Festival','source'=>'unsplash'],
    ['url'=>'https://symphony.live/_next/image?url=https%3A%2F%2Fwww.datocms-assets.com%2F82489%2F1708000449-orchestre-de-paris.webp%3Far64%3DMTY6OQ%26fit%3Dcrop&w=3840&q=75','title'=>'Band','source'=>'unsplash'],

    ['url'=>'https://picsum.photos/id/1011/1600/900','title'=>'Stage view','source'=>'picsum'],
    ['url'=>'https://picsum.photos/id/1035/1600/900','title'=>'Audience','source'=>'picsum'],
    ['url'=>'https://picsum.photos/id/1042/1600/900','title'=>'Lights','source'=>'picsum'],
    ['url'=>'https://picsum.photos/id/1069/1600/900','title'=>'Performance','source'=>'picsum'],
];

$curated = ($variant === 'music') ? $curated_music : $curated_music;

$banners = array_slice($curated, 0, $per_page);

$seen = [];
$out = [];
foreach ($banners as $b) {
    $u = isset($b['url']) ? trim($b['url']) : '';
    if ($u === '') continue;
    if (!filter_var($u, FILTER_VALIDATE_URL)) continue;
    if (isset($seen[$u])) continue;
    $seen[$u] = true;
    $out[] = ['url'=>$u, 'title'=>($b['title'] ?? ''), 'source'=>($b['source'] ?? 'curated')];
}

if (empty($out)) {
    $fallback = [
        'https://cdn.paris.fr/paris/2025/03/21/huge-81b73d782c506c2af02be61142afe290.jpg',
        'https://parisjetaime.com/data/layout_image/24553_Foule-concert--630x405--%C2%A9-DR-Pixhere_panoramic_2-1_l.jpg?ver=1700702737',
        'https://parisjetaime.com/data/layout_image/24553_Foule-concert--630x405--%C2%A9-DR-Pixhere_panoramic_2-1_l.jpg?ver=1700702737',
    ];
    foreach ($fallback as $u) $out[] = ['url'=>$u,'title'=>'Fallback','source'=>'picsum'];
}

$response = [
    'meta'=>['variant'=>$variant,'per_page_requested'=>$per_page,'returned'=>count($out),'fetched_at'=>date('c')],
    'banners'=>$out
];

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;