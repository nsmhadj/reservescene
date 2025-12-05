# ReserveScene

## Structure du dépôt

```
reservescene/
├── index.php                 # Point d'entrée à la racine
├── [page files].php          # Fichiers wrapper pour toutes les pages
├── public/                   # Actifs statiques
│   ├── css/                  # Feuilles de style
│   ├── js/                   # Fichiers JavaScript
│   └── images/               # Images
├── src/
│   ├── pages/                # Logique principale des pages PHP
│   ├── includes/             # Composants partagés (header, footer, etc.)
│   ├── api/                  # Endpoints API
│   └── components/           # Composants rendus
├── config/
│   └── database.php          # Connexion à la base de données (utilise des variables d'environnement)
├── .env.example              # Modèle pour les variables d'environnement
├── .gitignore                # Règles gitignore
├── robots.txt                # Fichier SEO robots
└── sitemap.xml               # Plan du site SEO
```

### 1. Cloner le dépôt
```bash
git clone https://github.com/nsmhadj/reservescene.git
cd reservescene
```

### Structure des répertoires

- **Root/** - Point d'entrée (index.php) et wrappers de pages
- **public/** - Contient les ressources statiques (CSS, JS, images)
- **src/pages/** - Logique des pages côté serveur
- **src/includes/** - Composants partagés (en-tête, pied de page, etc.)
- **src/api/** - Routes / points d'accès API
- **src/components/** - Composants réutilisables pour le rendu
- **config/database.php** - Gestion de la connexion à la base de données (se configure via .env)
- **.env.example** - Exemple des variables d'environnement nécessaires
- **robots.txt** et **sitemap.xml** - Fichiers pour le référencement

Notes :
- Assurez-vous de copier  `.env` et de remplir les variables nécessaires (base de données, etc.).
- Le fichier `config/database.php` attend les variables d'environnement pour établir la connexion.
