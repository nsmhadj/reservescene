# ReserveScene

A ticket reservation platform for concerts, shows, and cultural events.

## Repository Structure

This structure is optimized for AlwaysData hosting where the root directory is the document root:

```
reservescene/
├── index.php                 # Entry point at root
├── [page files].php          # Wrapper files for all pages
├── public/                   # Static assets
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript files
│   └── images/               # Images
├── src/
│   ├── pages/                # Main PHP page logic
│   ├── includes/             # Shared components (header, footer, etc.)
│   ├── api/                  # API endpoints
│   └── components/           # Rendered components
├── config/
│   └── database.php          # Database connection (uses env vars)
├── .env.example              # Template for environment variables
├── .gitignore                # Git ignore rules
├── robots.txt                # SEO robots file
└── sitemap.xml               # SEO sitemap
```

## Setup for AlwaysData

### 1. Clone the repository
```bash
git clone https://github.com/nsmhadj/reservescene.git
cd reservescene
```

### 2. Configure environment variables

Copy the example environment file and configure it with your credentials:

```bash
cp .env.example .env
```

Edit `.env` and set the following variables:

```env
# Database Configuration
DB_HOST=mysql-reservescene.alwaysdata.net
DB_NAME=reservescene_bd
DB_USER=your_database_user
DB_PASS=your_database_password
DB_PORT=3306

# Ticketmaster API
TM_API_KEY=your_ticketmaster_api_key

# hCaptcha Configuration (optional, skip verification if not set)
HCAPTCHA_SITEKEY=your_hcaptcha_sitekey
HCAPTCHA_SECRET=your_hcaptcha_secret

# Email Configuration
MAIL_FROM=noreply@reservescene.tld
MAIL_CONTACT=contact@reservescene.tld
```

### 3. Upload to AlwaysData

Upload all files to your AlwaysData site directory. The root of your repository becomes the document root.

**Important**: Make sure to set your environment variables in AlwaysData's environment configuration or load them in your PHP configuration.

### 4. Load environment variables

For AlwaysData, you can set environment variables in the admin panel under "Environment" settings, or create a script to load the .env file:

```php
// In a bootstrap file or at the start of index.php
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}
```

## Development

### Directory Structure

- **Root/** - Entry point (index.php) and page wrappers for backward compatibility
- **public/** - Static assets (CSS, JS, images)
- **src/pages/** - Main application page logic
- **src/includes/** - Reusable components (headers, footers, helpers)
- **src/api/** - API endpoints for AJAX requests
- **src/components/** - Rendered components (trending, showcases)
- **config/** - Configuration files (database connection)

### File Organization

Each user-facing page has two parts:
1. **Wrapper at root** (e.g., `connexion.php`) - Simple file that includes the logic
2. **Logic in src/pages/** (e.g., `src/pages/connexion.php`) - The actual page code

This structure maintains backward compatibility while keeping code organized.

### Adding a New Page

1. Create the page logic in `src/pages/your-page.php`:
   ```php
   <?php
   // Database or business logic
   require_once __DIR__ . '/../../config/database.php';
   
   // Include header
   include __DIR__ . '/../includes/header.php';
   ?>
   <!-- Your HTML content -->
   <?php include __DIR__ . '/../includes/footer.php'; ?>
   ```

2. Create a wrapper at root `your-page.php`:
   ```php
   <?php
   require_once __DIR__ . '/src/pages/your-page.php';
   ```

### Asset Paths

Since pages execute from root, reference assets in the public/ directory:
```html
<link rel="stylesheet" href="public/css/style.css">
<script src="public/js/script.js"></script>
<img src="public/images/logo.png">
```

The header and footer automatically handle asset paths using the `$publicBase` variable.

### Database Access

All database connections are centralized in `config/database.php`. Use it in your page files:

```php
require_once __DIR__ . '/../../config/database.php';
// $pdo is now available
```

### API Keys and Secrets

Always use environment variables for sensitive data:

```php
$apiKey = getenv('TM_API_KEY');
$secret = getenv('HCAPTCHA_SECRET');
```

## Security

- Never commit `.env` file (it's in `.gitignore`)
- All database credentials are loaded from environment variables
- API keys are not hardcoded in the source
- hCaptcha verification protects forms from bots (when configured)

## License

This project is for educational purposes.