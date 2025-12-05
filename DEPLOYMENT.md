# AlwaysData Deployment Guide

This repository is now optimized for deployment on AlwaysData hosting.

## Structure Overview

Files are organized for AlwaysData's root-level document serving:

- **Root level**: Entry point (index.php) and all user-facing pages
- **public/**: Static assets (CSS, JS, images) organized in subdirectories
- **src/**: Application logic separated from presentation
- **config/**: Configuration files

## Pre-Deployment Checklist

### 1. Configure Environment Variables

Create `.env` file from the template:

```bash
cp .env.example .env
```

Edit `.env` with your AlwaysData credentials:

```env
DB_HOST=mysql-reservescene.alwaysdata.net
DB_NAME=reservescene_bd
DB_USER=your_username
DB_PASS=your_password
DB_PORT=3306

TM_API_KEY=your_ticketmaster_api_key
HCAPTCHA_SITEKEY=your_hcaptcha_sitekey
HCAPTCHA_SECRET=your_hcaptcha_secret

MAIL_FROM=noreply@yourdomain.com
MAIL_CONTACT=contact@yourdomain.com
```

### 2. Upload Files to AlwaysData

Upload all files to your AlwaysData site directory:

- Via FTP/SFTP to your site's www directory
- Or via Git if you have SSH access

**Important**: Upload ALL files including:
- All `.php` files at root
- The `public/` directory with all subdirectories
- The `src/` directory with all subdirectories  
- The `config/` directory
- `.env` file (create from .env.example)
- `.gitignore` (optional, for Git deployments)

### 3. Set Up Environment Variables (Alternative)

If you prefer not to use a .env file, you can set environment variables in AlwaysData admin:

1. Log into AlwaysData admin panel
2. Go to "Environment" → "Environment variables"
3. Add all variables from .env.example

### 4. Verify Database Connection

Ensure your database credentials in .env match your AlwaysData MySQL database:

- Host: Usually `mysql-yoursite.alwaysdata.net`
- Database name: As created in AlwaysData
- Username: Your AlwaysData database user
- Password: Your database password

## Post-Deployment

### Test the Site

1. Visit your site URL (e.g., `yoursite.alwaysdata.net`)
2. Test key pages:
   - Homepage (index.php)
   - Registration (inscription.php)
   - Login (connexion.php)
   - Profile (profil.php - requires login)

### Common Issues

**Issue**: CSS/JS not loading
- **Solution**: Check that `public/` directory was uploaded with all subdirectories
- Verify paths reference `public/css/`, `public/js/`, etc.

**Issue**: Database connection errors
- **Solution**: Verify .env credentials match AlwaysData database settings
- Check that database.php is loading environment variables correctly

**Issue**: "File not found" errors
- **Solution**: Ensure all files are at root level (not in a subdirectory)
- AlwaysData serves from the www directory as root

## File Organization

### Root Level Files
All user-facing pages are simple wrappers that include the actual logic:

```php
<?php
// connexion.php (at root)
require_once __DIR__ . '/src/pages/connexion.php';
```

### Actual Page Logic
The real code is in `src/pages/`:

```php
<?php
// src/pages/connexion.php
require_once __DIR__ . '/../../config/database.php';
include __DIR__ . '/../includes/header.php';
// ... page content ...
include __DIR__ . '/../includes/footer.php';
```

This separation:
- Keeps code organized
- Maintains clean URLs (e.g., `/connexion.php` not `/src/pages/connexion.php`)
- Allows easy updates without breaking URLs

## Security Notes

- ✅ `.env` file contains sensitive data - ensure it's not publicly accessible
- ✅ Database credentials loaded from environment variables
- ✅ API keys loaded from environment variables
- ✅ No hardcoded secrets in code
- ✅ `.gitignore` prevents committing sensitive files

## Maintenance

### Updating Code

1. Make changes in `src/` directory files
2. Test locally if possible
3. Upload updated files to AlwaysData
4. Clear any caches if needed

### Adding New Pages

1. Create page logic in `src/pages/newpage.php`
2. Create wrapper at root: `newpage.php`
3. Upload both files to AlwaysData

## Support

For issues specific to AlwaysData hosting, consult:
- AlwaysData documentation: https://help.alwaysdata.com/
- AlwaysData support if needed

For code issues, check:
- README.md for development guidelines
- Individual file comments for specific functionality
