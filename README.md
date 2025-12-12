# Projet Web – Reservescene (Plateforme de réservation d’événements)

## 1. Informations générales
- Nom : Yazid BATTOU  | Nassim HADJEBAR | Lahna MESSAHEL | Melynda AIT ALLALA 
- UE : Mineure Python: un langage Multipass 
- Projet : Plateforme web de réservation d’événements  
- Formation : L3 Informatique  
- Date  : Décembre-2025  



---


## 2. Présentation générale du projet
Reservescene est une application web permettant de :

- consulter des événements disponibles,
- effectuer des réservations,
- rechercher des événements via un moteur interne,
- gérer un compte utilisateur : profil, recharge ...,
- créer un compte, se connecter, se déconnecter, réinitialiser son mot de passe,
- afficher des sections dynamiques (Showcases, Trending),
- envoyer une demande d’aide via un formulaire de contact ,
- consulter les pages légales (cookies, mentions légales, politique, CGU).
- Le projet est structuré autour d’une architecture modulaire composée de :
  - pages principales,
  - composants et includes réutilisables,
  - API internes en PHP,
  - fichiers statiques : CSS, JS, images,
  - configuration BDD via .env et PDO.


## 3. Structure du projet

L’arborescence du projet est la suivante :
```
reservescene/
├── index.php                        
│
├── public/                          
│   ├── css/                         
│   │   ├── accueil.css
│   │   ├── banner.css
│   │   ├── conditions.css
│   │   ├── connexion.css
│   │   ├── cookies.css
│   │   ├── footer.css
│   │   ├── form.css
│   │   ├── header.css
│   │   ├── headerdeconnexion.css
│   │   ├── index.css
│   │   ├── mentions_legales.css
│   │   ├── politique.css
│   │   ├── profil.css
│   │   ├── recharge.css
│   │   ├── recherche.css
│   │   ├── reservation.css
│   │   ├── resultat.css
│   │   ├── showcases.css
│   │   └── trending.css
│   │
│   ├── js/                          
│   │   ├── banner.js
│   │   ├── form.js
│   │   ├── header.js
│   │   ├── headerdeconnexion.js
│   │   ├── profil.js
│   │   ├── recharge.js
│   │   ├── recherche.js
│   │   ├── reservation.js
│   │   ├── resultat.js
│   │   ├── showcases.js
│   │   └── trending.js
│   │
│   └── images/                      
│
├── src/
│   ├── pages/                       
│   │   ├── accueil.php
│   │   ├── aidecontact.php
│   │   ├── blog.php
│   │   ├── conditions.php
│   │   ├── connexion.php
│   │   ├── cookies.php
│   │   ├── events.php
│   │   ├── faq.php
│   │   ├── form.php
│   │   ├── inscription.php
│   │   ├── mdp_oubliee.php
│   │   ├── mdp_reset.php
│   │   ├── mentions_legales.php
│   │   ├── politique.php
│   │   ├── profil.php
│   │   ├── recharge.php
│   │   ├── recherche.php
│   │   ├── reservation.php
│   │   ├── resultat.php
│   │   └── validation_email.php
│   │
│   ├── includes/                    
│   │   ├── footer.php
│   │   ├── header.php
│   │   ├── headerdeconnexion.php
│   │   ├── helpers.php              
│   │   ├── Hash_mdp.php             
│   │   └── test_mail.php
│   │
│   ├── components/                  
│   │   ├── showcases.php
│   │   └── trending.php
│   │
│   ├── api/                         
│   │   ├── check_inscription.php
│   │   ├── check_user.php
│   │   ├── event.php
│   │   ├── logout.php
│   │   └── traitement_contact.php
│   │
│   └── config/
│       └── database.php             
│
├── .env.example                     
├── .gitignore                       
├── robots.txt                       
└── sitemap.xml                      

```

## 4. Description détaillée des fichiers du projet

Cette section présente l’ensemble des fichiers Python du projet, organisés par dossiers, avec une description de leurs rôles et de leurs principales fonctions.


## 4.1 Dossier public/ – Ressources statiques

### 4.1.1 public/css/ – Feuilles de style du site
Chaque page ou composant a son propre fichier (accueil.css, recherche.css, etc.) afin de séparer les styles et de faciliter la maintenance. Les styles suivent une palette claire et des cartes arrondies. Par exemple, recherche.css met en place une grille responsive pour l’affichage des résultats de recherche et applique des animations discrètes sur les cartes.


### 4.1.2 public/js/ – Scripts JavaScript du site
Les scripts JavaScript ajoutent l’interactivité côté client. Les fichiers notables sont :

banner.js : génère un diaporama dynamique sur la page d’accueil en récupérant des événements et en changeant automatiquement l’image toutes les quelques secondes.

form.js : gère la validation client‑side du formulaire de réservation (activation d’un bloc invité, contrôle des champs, messages d’erreur).

recherche.js : alimente le moteur de recherche avec filtres et tri en temps réel, en appelant l’API events.php et en générant dynamiquement les cartes d’événements.

reservation.js, resultat.js : ajoutent des effets d’interface et récupèrent les détails d’un événement via AJAX pour remplir la page de résultat.


### 4.1.3 public/images/ – Images et assets
assets graphiques (logos, bannières, photos d’événements).




## 4.2 Dossier src/ – Logique interne du projet




### 4.2.1 src/pages/ – Pages principales du site

connexion.php (accueil)	gère l’affichage de la page d’accueil : bannière d’acceptation des cookies, section Trending, section Showcases et inclusion du footer.

inscription.php	affiche le formulaire d’inscription, vérifie les champs (email valide, mot de passe min 8 caractères), supprime les comptes en attente expirés et envoie un e‑mail d’activation contenant un code.

connexion.php	(non, connexion.php n’est pas l’accueil) gère la connexion utilisateur : vérifie l’identité en base, démarre la session et redirige l’utilisateur sur la page demandée ou le profil.

profil.php	affiche et permet de modifier les informations de l’utilisateur (login, nom, e‑mail, téléphone, sexe), ainsi que de changer le mot de passe via password_hash() et password_verify().

recharge.php	permet de recharger le solde grâce à un code prépayé : vérifie la validité du code, marque le code comme utilisé et incrémente le solde de l’utilisateur dans une transaction SQL.

recherche.php	page du moteur de recherche : présente les filtres et appelle recherche.js pour afficher les événements correspondants.

reservation.php	processus de réservation : collecte les informations de l’utilisateur et de l’invité éventuel, vérifie la cohérence des champs et confirme la réservation en réduisant le solde.

resultat.php	page de détail d’un événement : récupère les informations via l’API event.php, affiche l’image, la date, le lieu, le prix et permet de réserver.

mdp_oubliee.php / mdp_reset.php	pages dédiées à la réinitialisation du mot de passe (demande d’envoi de lien et formulaire de nouveau mot de passe).

Pages légales (conditions.php, cookies.php, mentions_legales.php, politique.php) : affichent les textes réglementaires et gèrent l’affichage du consentement aux cookies.

Pages informatives (faq.php, aidecontact.php, events.php, blog.php) : fournissent des contenus ou des formulaires (FAQ, blog, contact).


### 4.2.2 src/includes/ – Elements réutilisables

header.php / headerdeconnexion.php : entêtes du site qui adaptent la navigation en fonction de l’état de connexion (menus, boutons de profil ou de connexion).

footer.php : pied de page commun avec liens et mentions légales.

helpers.php : regroupe les fonctions utilitaires :

h() : échappement HTML sécurisé,

format_money_eur() : formatage de montants en euros,

fonctions de validation et de redirection, etc.

Hash_mdp.php : fournit deux fonctions simples : hashPassword($password) pour hacher les mots de passe avec l’algorithme actuel (PASSWORD_DEFAULT), et verifyPassword($password, $hash) pour vérifier un mot de passe proposé.

test_mail.php : script de test d’envoi de mail avec la configuration du serveur.


### 4.2.3 src/components/ – Composants dynamiques affichés sur les pages

showcases.php : génère un bloc mettant en avant quelques événements sélectionnés en base de données (image, titre, date, lieu). Ce composant est appelé dans la page d’accueil.

trending.php : récupère les événements les plus populaires (via un tri par réservations ou par “vues”) et les affiche sous forme de cartes sombres avec image, description et bouton d’action.

Ces composants sont écrits en PHP et séparés des pages pour pouvoir être réutilisés où nécessaire.


### 4.2.4 src/api/ – API internes en PHP (AJAX) 

Les endpoints ci‑dessous renvoient des données en JSON aux scripts JS pour rendre l’interface plus réactive :

check_inscription.php	API de vérification d’un champ d’inscription. Reçoit deux paramètres : type (login ou email) et value (valeur saisie). Utilise un SELECT COUNT(*) pour vérifier si le login ou l’adresse e‑mail existe déjà dans la table client et renvoie ```{"exists": true```
check_user.php	API de pré‑vérification d’un identifiant lors de la connexion. Reçoit username (pouvant être un login ou un e‑mail) et renvoie ```{"exists": true```
event.php	Passerelle vers l’API Ticketmaster. Reçoit un identifiant d’événement (id), construit l’URL Ticketmaster avec la clé API stockée dans le bootstrap, récupère les détails de l’événement via file_get_contents() et renvoie la réponse JSON brut au client.
logout.php	Détruit la session et redirige l’utilisateur vers la page d’accueil.
traitement_contact.php	Traite le formulaire de contact : récupère le nom, l’e‑mail, le sujet et le message envoyés en POST, construit un e‑mail et l’envoie à l’adresse configurée (MAIL_CONTACT). Affiche ensuite un message JavaScript de succès ou d’échec et redirige vers la page de contact.

Ces API utilisent des requêtes préparées (PDO) pour éviter les injections SQL et renvoient des réponses simples pour être exploitées en JavaScript.



### 4.2.5 src/config/ – Configuration

database.php (non fourni) contient la connexion à la base de données MySQL via PDO. Il récupère l’hôte, la base, l’utilisateur et le mot de passe depuis les variables d’environnement définies dans .env.

bootstrap.php (inclus dans event.php et inscription.php) charge les librairies nécessaires et met à disposition des constantes (ex. $TICKETMASTER_API_KEY).




### 5. Fonctionnalités JavaScript importantes

Les scripts JS apportent une vraie dynamique à l’application. Voici un résumé des fonctionnalités clés :

Chargement et slideshow de la bannière (banner.js) : récupère des événements mis en avant via une API, crée un carrousel et fait défiler automatiquement les images toutes les quelques secondes.

Validation et gestion des formulaires (form.js) : montre ou cache les champs d’invité selon le nombre de places choisi, valide les noms et e‑mails au clavier et affiche les messages d’erreur.

Moteur de recherche (recherche.js) : applique des filtres (catégorie, date, prix, ville, accessibilité), trie les événements par date ou par prix, met à jour l’interface à chaque modification et redirige vers la page de résultat lors de la sélection d’un événement.

Récupération des détails d’un événement (resultat.js) : si la page n’a pas été rendue serveur‑side, interroge event.php pour obtenir la description, l’image, le prix et les coordonnées GPS ; remplit ensuite les éléments HTML et initialise la carte Google Maps.

Gestion des réservations (reservation.js) : ajoute un simple effet de survol sur les cartes de l’historique de réservation.

Affichage des messages (profil.js, recharge.js) : masquent automatiquement les alertes de succès après quelques secondes.



### 6.Fonctionnement des API et aspects de sécurité

Les API internes utilisent PDO et des requêtes préparées pour interroger la base de données et renvoient des structures JSON simples. Les pages et les scripts font appel à ces endpoints via fetch() en JavaScript pour valider ou récupérer des données sans rafraîchir la page.

Le processus d’inscription s’appuie sur password_hash() pour stocker les mots de passe de manière sécurisée et envoie un code d’activation par e‑mail afin de valider le compte. Lors de la connexion, l’utilisateur peut se connecter via son login ou son adresse e‑mail ; l’API check_user.php permet de vérifier l’existence du compte avant d’envoyer le formulaire.

La page profil permet de modifier les informations personnelles et de changer le mot de passe. Elle vérifie que le nouveau login ou e‑mail n’est pas déjà utilisé et met à jour la table client en conséquence. La modification du mot de passe nécessite l’ancien mot de passe et applique un contrôle de longueur et de confirmation.

La page recharge utilise une transaction SQL pour incrémenter le solde du client et marquer le code prépayé comme utilisé afin d’assurer la cohérence des données.



### 7.Fichiers de configuration supplémentaires

.env : modèle des variables d’environnement nécessaires : connexion MySQL (DB_HOST, DB_NAME, DB_USER, DB_PASS), clé de l’API Ticketmaster (TICKETMASTER_API_KEY), clés hCaptcha (HCAPTCHA_SITEKEY, HCAPTCHA_SECRET), adresse de contact (MAIL_CONTACT)…

.gitignore : exclut du suivi vendor/, cache/, .env et autres fichiers sensibles.

robots.txt : indique aux robots des moteurs de recherche de ne pas indexer certaines parties du site en développement.

sitemap.xml : référence les principales pages afin d’améliorer le référencement SEO.



### 8.Mise en route et déploiement

Cloner le dépôt : git clone <url-du-projet> et placer le dossier dans le répertoire Web de votre serveur local (ex. C:/xampp/htdocs/).

Créer un fichier .env à partir de .env.example et renseigner les variables de connexion à la base de données et les clés API.

Importer la base de données : exécuter le script SQL fourni (db.sql) dans MySQL/MariaDB.

Installer les dépendances : aucune dépendance back‑end particulière n’est nécessaire, mais il est conseillé d’activer les extensions PHP pdo, pdo_mysql, curl et mbstring.

Lancer le serveur : démarrer Apache et MySQL (XAMPP/Laragon) et accéder au site à l’adresse http://localhost/reservescene.

Tester : créer un compte, valider par e‑mail, rechercher un événement, recharger votre solde et effectuer une réservation.



### 9.Conclusion

Cette plateforme met en œuvre les bonnes pratiques de développement web en PHP : séparation des responsabilités, utilisation d’APIs internes sécurisées, validations côté serveur et côté client, gestion des sessions et des mots de passe, transactions SQL pour les opérations sensibles. Les scripts JavaScript apportent une expérience utilisateur fluide, tandis que les fichiers CSS assurent un rendu moderne et responsive.

Les prochaines évolutions envisagées pourraient inclure :

l’intégration d’un système de paiement en ligne,

la mise en cache des requêtes d’événements pour accélérer l’affichage,

ou encore un panneau d’administration pour gérer les codes, les événements et les réservations.
