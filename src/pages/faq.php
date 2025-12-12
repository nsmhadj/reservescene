<!doctype html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FAQ - Reservescene</title>
 
 

 <style>

    body {
      font-family: "Poppins", sans-serif;
      background-color: #f5f5f5;
      color: #222;
      margin: 0;
      padding: 0;
    }

    section.faq {
      max-width: 900px;
      margin: 100px auto;
      padding: 0 20px;
    }

    section.faq h1 {
      text-align: center;
      color: #1a1a1a;
      font-size: 2.3em;
      margin-bottom: 50px;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .faq-item {
      background: #ffffff;
      border-radius: 14px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.08);
      margin-bottom: 20px;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .faq-question {
      background: linear-gradient(90deg, #1a1a1a, #2b2b2b);
      color: #f9f9f9;
      cursor: pointer;
      padding: 20px 25px;
      font-weight: 600;
      display: flex;
      justify-content: space-between;
      align-items: center;
      letter-spacing: 0.3px;
      transition: background 0.3s ease;
    }

    .faq-question:hover {
      background: linear-gradient(90deg, #2b2b2b, #3a3a3a);
    }

    .faq-question::after {
      content: "+";
      font-size: 22px;
      font-weight: bold;
      transition: transform 0.3s ease;
    }

    .faq-item.active .faq-question::after {
      content: "−";
      transform: rotate(180deg);
    }

    .faq-answer {
      max-height: 0;
      overflow: hidden;
      background: #fff;
      color: #333;
      font-size: 15px;
      line-height: 1.6em;
      padding: 0 25px;
      transition: max-height 0.4s ease, padding 0.3s ease;
    }

    .faq-item.active .faq-answer {
      max-height: 300px;
      padding: 22px 25px;
    }

    .faq-answer a {
      color: #1a1a1a;
      text-decoration: underline;
      font-weight: 500;
    }

    @media (max-width: 600px) {
      section.faq h1 {
        font-size: 1.9em;
      }
      .faq-question {
        font-size: 16px;
        padding: 18px 20px;
      }
      .faq-answer {
        font-size: 14px;
      }
    }
  </style>
</head>

<body>
 <?php include __DIR__ . '/../includes/header.php'; ?>
<section class="faq">
  <h1>Foire Aux Questions</h1>

  <div class="faq-item">
    <div class="faq-question"> Comment réserver une scène ?</div>
    <div class="faq-answer">
      Connectez-vous à votre compte Reservescene, choisissez la scène, la date et l’heure souhaitées, puis validez. Vous recevrez un e-mail de confirmation.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question"> Puis-je modifier ou annuler une réservation ?</div>
    <div class="faq-answer">
      Oui, vous pouvez modifier ou annuler une réservation jusqu’à 24 heures avant la date prévue, depuis votre espace personnel.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question"> Quels sont les moyens de paiement disponibles ?</div>
    <div class="faq-answer">
      Nous acceptons les paiements par carte bancaire, PayPal et virement bancaire sécurisé.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question"> Comment savoir si une scène est disponible ?</div>
    <div class="faq-answer">
      Le calendrier de chaque scène indique les créneaux encore disponibles. Les créneaux complets sont grisés automatiquement.
    </div>
  </div>

  <div class="faq-item">
    <div class="faq-question"> Comment contacter l’équipe Reservescene ?</div>
    <div class="faq-answer">
      Vous pouvez nous contacter via la page <a href="aidecontact.php">Contact</a> ou par e-mail à <b>support@reservescene.fr</b>.
    </div>
  </div>
</section>

<script>
  document.querySelectorAll('.faq-question').forEach((question) => {
    question.addEventListener('click', () => {
      const parent = question.parentNode;
      document.querySelectorAll('.faq-item').forEach(item => {
        if (item !== parent) item.classList.remove('active');
      });
      parent.classList.toggle('active');
    });
  });
</script>



<?php include __DIR__ . '/../includes/footer.php'; ?>
