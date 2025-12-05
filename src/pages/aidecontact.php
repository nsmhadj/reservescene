<?php include __DIR__ . '/../includes/header.php'; ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aide & Contact - Reservescene</title>

  <style>
    body {
      font-family: "Poppins", sans-serif;
      background-color: #f5f5f5;
      color: #222;
      margin: 0;
      padding: 0;
    }

    section.contact {
      max-width: 950px;
      margin: 80px auto;
      padding: 0 25px;
    }

    section.contact h1 {
      text-align: center;
      color: #1a1a1a;
      font-size: 2.3em;
      margin-bottom: 40px;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .contact-wrapper {
      display: flex;
      flex-wrap: wrap;
      gap: 40px;
      justify-content: space-between;
    }

    /* Bloc aide */
    .help-box {
      flex: 1;
      min-width: 300px;
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 3px 8px rgba(0,0,0,0.08);
      padding: 25px 30px;
    }

    .help-box h2 {
      color: #1a1a1a;
      font-size: 1.4em;
      margin-bottom: 15px;
    }

    .help-box ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .help-box li {
      margin-bottom: 12px;
      line-height: 1.5em;
    }

    .help-box li a {
      text-decoration: none;
      color: #1a1a1a;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .help-box li a:hover {
      color:  #0c9440ff;
    }

    /* Bloc contact */
    .contact-form {
      flex: 1.3;
      min-width: 320px;
      background: #ffffff;
      border-radius: 14px;
      box-shadow: 0 3px 8px rgba(0,0,0,0.08);
      padding: 30px;
    }

    .contact-form h2 {
      color: #1a1a1a;
      font-size: 1.4em;
      margin-bottom: 20px;
    }

    .contact-form form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .contact-form input,
    .contact-form textarea {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
      background: #fafafa;
      transition: all 0.3s ease;
    }

    .contact-form input:focus,
    .contact-form textarea:focus {
      border-color: #1a1a1a;
      outline: none;
      background: #fff;
    }

.contact-form button {
  background: linear-gradient(90deg, #1a1a1a, #2b2b2b);
  color: #f9f9f9;
  font-weight: 600;
  border: none;
  border-radius: 8px;
  padding: 12px;
  cursor: pointer;
  transition: background 0.3s ease, transform 0.2s ease;
}

.contact-form button:hover {
  background: linear-gradient(90deg, #0c9440ff, #0c9440ff); /* Dégradé vert */
  transform: translateY(-2px);
}


    /* Bloc téléphone */
    .phone-box {
      margin-top: 25px;
      padding: 18px;
      background: #1a1a1a;
      color: #f9f9f9;
      border-radius: 10px;
      text-align: center;
      font-size: 16px;
      font-weight: 500;
      letter-spacing: 0.3px;
    }

    .phone-box a {
      color: #f9f9f9;
      text-decoration: none;
      font-weight: 600;
    }

    .phone-box a:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .contact-wrapper {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>

<section class="contact">
  <h1>Aide & Contact</h1>

  <div class="contact-wrapper">

    <!-- Bloc AIDE -->
    <div class="help-box">
      <h2>Besoin d’un coup de main ?</h2>
      <ul>
        <li> <a href="faq.php">Consultez la FAQ</a> — les réponses aux questions les plus courantes.</li>
        <li> <a href="reserveevent.php">Comment reserver un événement</a> — guide étape par étape.</li>
        <li> <a href="connexion.php">Problème de connexion</a> — réinitialisez votre mot de passe.</li>
      </ul>

      <!-- Bloc téléphone -->
      <div class="phone-box">
         Appelez-nous au  
        <a href="tel:+33612345678">+33 6 12 34 56 78</a>
      </div>
    </div>

    <!-- Bloc FORMULAIRE -->
    <div class="contact-form">
      <h2>Contactez-nous</h2>
      <form action="traitement_contact.php" method="post">
        <input type="text" name="nom" placeholder="Votre nom" required>
        <input type="email" name="email" placeholder="Votre e-mail" required>
        <input type="text" name="sujet" placeholder="Sujet">
        <textarea name="message" rows="5" placeholder="Votre message..." required></textarea>
        <button type="submit">Envoyer le message</button>
      </form>
    </div>

  </div>
</section>

</body>
</html>

<?php include __DIR__ . '/../includes/footer.php'; ?>
