<?php

// Load database configuration
require_once __DIR__ . '/../../config/database.php';

include __DIR__ . '/../includes/header.php';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bannière dynamique</title>
  <link rel="stylesheet" href="banner.css">
  <link rel="stylesheet" href="trending.css">
  <link rel="stylesheet" href="showcases.css">

</head>
<body>

<section class="hero">
  <div id="heroViewport" class="hero__viewport"></div>
</section>
<section class="trending" id="trending">
  <div class="trending__head">
    <h2>Comédie • Musique • Théâtre — les dates à ne pas manquer</h2>
    <p class="trending__tag">#tendances</p>
  </div>
  <div class="trending__list" id="trendingList"></div>
</section>

<section class="showcases" id="showcases">
  <div class="showcase-block">
    <h2 class="showcase-title">Musique</h2>
    <div class="showcase-row" id="rowMusic"></div>
  </div>

  <div class="showcase-block">
    <h2 class="showcase-title">COMEDIE</h2>
    <div class="showcase-row" id="rowComedy"></div>
  </div>

  <div class="showcase-block">
    <h2 class="showcase-title">THEATRE</h2>
    <div class="showcase-row" id="rowTheatre"></div>
  </div>
</section>

<!-- scripts -->
<script src="showcases.js"></script>
<script src="banner.js"></script>
<script src="trending.js"></script>
</body>
</html>

<?php
// PDO connection already initialized from config/database.php

// Récupération d'un artiste
$artiste = $pdo->query("SELECT * FROM artistes LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Récupération artistes invités
$invites = $pdo->query("SELECT * FROM artistes ORDER BY RAND() LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Récupération avis
$avis = $pdo->query("SELECT * FROM avis ORDER BY id_avis DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="width:90%; margin:auto; padding-top:40px; font-family:Arial;">

    <!-- ARTISTE MIS EN AVANT -->
    <?php if($artiste): ?>
        <div style="display:flex; gap:40px; align-items:center; margin-bottom:60px;">
            <img src="<?php echo $artiste['image']; ?>" style="width:350px; height:350px; object-fit:cover; border-radius:15px;">
            <div>
                <h1 style="font-size:28px; margin-bottom:10px;">🎤 Artiste en avant : <?php echo $artiste['nom']; ?></h1>
                <p style="color:#555; margin-bottom:20px; max-width:500px;"><?php echo $artiste['description']; ?></p>
                <a href="reservation.php?id=<?php echo $artiste['id_artiste']; ?>" style="padding:12px 22px; background:black; color:white; border-radius:8px; text-decoration:none;">Réserver</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- ARTISTES INVITÉS (SCROLL HORIZONTAL) -->
    <h2 style="margin-bottom:15px;">🎶 Artistes invités</h2>
    <div style="display:flex; overflow-x:auto; gap:20px; padding-bottom:15px;">
        <?php foreach($invites as $a): ?>
            <div style="min-width:200px; background:#f8f8f8; border-radius:10px; padding:10px; text-align:center; box-shadow:0 3px 10px rgba(0,0,0,0.1);">
                <img src="<?php echo $a['image']; ?>" style="width:100%; height:150px; object-fit:cover; border-radius:8px;">
                <p style="font-weight:bold; margin-top:10px;"><?php echo $a['nom']; ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- AVIS CLIENTS -->
    <h2 style="margin:40px 0 20px;">💬 Avis des clients</h2>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:20px;">
        <?php foreach($avis as $v): ?>
            <div style="border:1px solid #ddd; padding:15px; border-radius:8px; background:#fff; box-shadow:0 3px 8px rgba(0,0,0,0.05);">
                <p style="font-weight:bold; margin-bottom:8px;">Client #<?php echo $v['id_avis']; ?></p>
                <p style="color:#555;">"<?php echo $v['contenu']; ?>"</p>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
