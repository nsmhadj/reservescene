<?php include __DIR__ . '/../includes/header.php'; ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Blog - Reservescene</title>

  <style>
    body {
      font-family: "Poppins", sans-serif;
      background-color: #f5f5f5;
      color: #222;
      margin: 0;
      padding: 0;
    }

    section.blog {
      max-width: 1200px;
      margin: 80px auto;
      padding: 0 25px;
    }

    section.blog h1 {
      text-align: center;
      color: #1a1a1a;
      font-size: 2.5em;
      margin-bottom: 50px;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .blog-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 30px;
    }

    .blog-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.08);
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      display: flex;
      flex-direction: column;
    }

    .blog-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .blog-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }

    .blog-content {
      padding: 20px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .blog-title {
      font-size: 1.4em;
      font-weight: 700;
      margin-bottom: 12px;
      color: #1a1a1a;
    }

    .blog-excerpt {
      flex: 1;
      font-size: 15px;
      line-height: 1.6em;
      color: #444;
      margin-bottom: 15px;
    }

    .blog-link {
      align-self: flex-start;
      text-decoration: none;
      color: #1a1a1a;
      font-weight: 600;
      border-bottom: 2px solid transparent;
      transition: all 0.3s ease;
    }

    .blog-link:hover {
      border-bottom: 2px solid #1a1a1a;
    }

    @media (max-width: 768px) {
      section.blog h1 {
        font-size: 2em;
      }
      .blog-card img {
        height: 160px;
      }
    }
  </style>
</head>

<body>

<section class="blog">
  <h1>Blog Reservescene</h1>

  <div class="blog-grid">

   
    <div class="blog-card">
      <img src="/public/images/concert.jpg" alt="Concert de musique">
      <div class="blog-content">
        <div class="blog-title">Les meilleures scènes à réserver ce mois-ci</div>
        <div class="blog-excerpt">
          Découvrez notre sélection des événements incontournables de ce mois, avec des concerts, comédies et théâtres à ne pas manquer.
        </div>
        <a href="concert.php" class="blog-link">Lire la suite →</a>
      </div>
    </div>

    <div class="blog-card">
      <img src="/public/images/theatre.jpg" alt="Spectacle de théâtre">
      <div class="blog-content">
        <div class="blog-title">Comment réussir votre première réservation de scène</div>
        <div class="blog-excerpt">
          Un guide complet pour les artistes et associations qui souhaitent réserver leur première scène facilement et sans stress.
        </div>
        <a href="reservation.php" class="blog-link">Lire la suite →</a>
      </div>
    </div>


    <div class="blog-card">
      <img src="/public/images/comedy.jpg" alt="Comédie en live">
      <div class="blog-content">
        <div class="blog-title">Top 5 des comédies à venir sur Reservescene</div>
        <div class="blog-excerpt">
          Retrouvez les spectacles comiques les plus attendus et préparez votre planning pour ne rien manquer.
        </div>
        <a href="comedie.php" class="blog-link">Lire la suite →</a>
      </div>
    </div>

    
    
  </div>
</section>

</body>
</html>

<?php include __DIR__ . '/../includes/footer.php'; ?>
