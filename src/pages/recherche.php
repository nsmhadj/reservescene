<?php
// recherche.php
include __DIR__ . '/../includes/header.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Résultats de recherche<?= $keyword ? ' — ' . htmlspecialchars($keyword) : '' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/public/css/recherche.css">
</head>
<body>

<div class="search-page">

  <!-- colonne filtres -->
<aside class="filters">
  <h3>Filtres</h3>

  <!-- Dates : choix unique -->
  <div class="filter-block">
    <p class="filter-title">Dates</p>
    <label><input type="radio" name="filter-date" class="filter-date" value="all" checked> Toutes les dates</label>
    <label><input type="radio" name="filter-date" class="filter-date" value="today"> Ce soir</label>
    <label><input type="radio" name="filter-date" class="filter-date" value="weekend"> Ce week-end</label>
    <label><input type="radio" name="filter-date" class="filter-date" value="week"> Cette semaine</label>
    <label><input type="radio" name="filter-date" class="filter-date" value="month"> Ce mois-ci</label>
  </div>

  <!-- Types d'événements -->
  <div class="filter-block">
    <p class="filter-title">Type d'événement</p>
    <label><input type="checkbox" class="filter-cat" value="concert"> Concert</label>
    <label><input type="checkbox" class="filter-cat" value="theatre"> Théâtre</label>
    <label><input type="checkbox" class="filter-cat" value="comedy"> Comédie</label>
  </div>

  <!-- Ville (remplie par JS) -->
  <div class="filter-block">
    <p class="filter-title">Ville</p>
    <select id="filter-city">
      <option value="all">Toutes les villes</option>
      <!-- le JS va ajouter d'autres options ici -->
    </select>
  </div>

  <!-- Prix -->
  <div class="filter-block">
    <p class="filter-title">Prix</p>
    <label><input type="radio" name="filter-price" class="filter-price" value="all" checked> Tous les prix</label>
    <label><input type="radio" name="filter-price" class="filter-price" value="low"> &lt; 20€ </label>
    <label><input type="radio" name="filter-price" class="filter-price" value="mid"> 20€ – 50€ </label>
    <label><input type="radio" name="filter-price" class="filter-price" value="high"> &gt; 50€ </label>
  </div>

  <!-- Accessibilité (optionnel) -->
  <div class="filter-block">
    <p class="filter-title">Accessibilité</p>
    <label><input type="checkbox" class="filter-access" value="pmr"> Accès PMR</label>
  </div>
</aside>



  <!-- contenu principal -->
  <main class="results">
    <div class="results-header">
      <h2>Résultats de recherche <?= $keyword ? '"' . htmlspecialchars($keyword) . '"' : '' ?></h2>
      <div class="sort-tabs">
        <button class="active">le plus demandé</button>
        <button>le plus proche</button>
        <button>moins cher</button>
      </div>
    </div>

    <!-- zone d'erreur gérée par JS -->
    <div id="js-error" class="error" style="display:none;">Impossible de récupérer les événements.</div>

    <!-- le JS va remplir cette grille -->
    <div class="cards-grid"></div>

  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- on passe le mot-clé au JS -->
<script>
  window.SEARCH_KEYWORD = <?= json_encode($keyword) ?>;
</script>
<script src="/public/js/recherche.js"></script>
</body>
</html>
