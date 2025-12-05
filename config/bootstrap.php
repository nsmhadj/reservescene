<?php
// config/bootstrap.php
// Robust environment loader for web requests.
//
// What this does:
// - Searches upward from this config directory to find project root (where vendor/ or .env likely live).
// - Loads composer autoload if present.
// - If vlucas/phpdotenv is installed, uses it to load .env (safeLoad).
// - Otherwise, finds a .env file (searching upward) and parses it, populating getenv()/$_ENV/$_SERVER.
// - Also loads config/app.php (if present) as a final fallback (expects array of keys).
// - Writes a few debug log lines when it loads values (helpful during troubleshooting).
//
// Usage: require_once __DIR__ . '/config/bootstrap.php'; (adjust path from pages/ to config/)
// Make sure you call this very early in each page request, before using getenv().

$startDir = __DIR__; // config directory
$maxLevels = 6;      // how many parent directories to search
$cwd = $startDir;
$foundRoot = null;
$foundAutoload = null;
$foundEnv = null;

// Walk up the directory tree looking for vendor/autoload.php or .env
for ($i = 0; $i <= $maxLevels; $i++) {
    if (file_exists($cwd . '/vendor/autoload.php')) {
        $foundAutoload = $cwd . '/vendor/autoload.php';
        $foundRoot = $cwd;
        break;
    }
    if (file_exists($cwd . '/.env') && $foundEnv === null) {
        $foundEnv = $cwd . '/.env';
        $foundRoot = $cwd;
        // don't break yet — prefer vendor/autoload if it exists higher up
    }
    $parent = dirname($cwd);
    if ($parent === $cwd) break;
    $cwd = $parent;
}

// If we didn't find anything in the upward search, also try project root = one level above config as a fallback
if ($foundRoot === null) {
    $maybeRoot = dirname(__DIR__); // parent of config
    if (file_exists($maybeRoot . '/vendor/autoload.php')) {
        $foundAutoload = $maybeRoot . '/vendor/autoload.php';
        $foundRoot = $maybeRoot;
    } elseif (file_exists($maybeRoot . '/.env')) {
        $foundEnv = $maybeRoot . '/.env';
        $foundRoot = $maybeRoot;
    }
}

// 1) Load composer autoload if present
if ($foundAutoload) {
    try {
        require_once $foundAutoload;
    } catch (Throwable $e) {
        error_log("bootstrap: failed to require autoload at {$foundAutoload}: " . $e->getMessage());
    }
}

// 2) If phpdotenv is available, use it to load .env from the detected root (or from startDir parent)
if (class_exists('Dotenv\Dotenv')) {
    try {
        $dotRoot = $foundRoot ?: dirname(__DIR__);
        $dotenv = Dotenv\Dotenv::createImmutable($dotRoot);
        $dotenv->safeLoad();
        // echo debug to log
        error_log("bootstrap: phpdotenv loaded from {$dotRoot}/.env (if present)");
        return;
    } catch (Throwable $e) {
        error_log('bootstrap: phpdotenv error: ' . $e->getMessage());
        // continue to fallback parser
    }
}

// 3) Fallback: parse .env file manually if found
$envFile = $foundEnv ?? (file_exists(dirname(__DIR__) . '/.env') ? dirname(__DIR__) . '/.env' : null);
if ($envFile && file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;

        // support "export KEY=VALUE" or "KEY=VALUE"
        if (strpos($line, 'export ') === 0) {
            $line = trim(substr($line, 7));
        }
        if (strpos($line, '=') === false) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove surrounding quotes if present
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
            // unescape common sequences inside double quotes
            $value = str_replace(['\\n','\\r','\\t','\\"',"\\'","\\\\"] , ["\n","\r","\t",'"',"'","\\"], $value);
        }

        // set env in multiple SAPI-visible places
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
    error_log("bootstrap: parsed .env from {$envFile}");
    return;
}
define('TICKETMASTER_API_KEY', getenv('TM_API_KEY'));


// 4) Fallback: load config/app.php if present (returns array)
$configApp = dirname(__DIR__) . '/config/app.php';
if (file_exists($configApp)) {
    try {
        $cfg = require $configApp;
        if (is_array($cfg)) {
            foreach ($cfg as $k => $v) {
                if (!is_scalar($v)) continue;
                $val = (string)$v;
                putenv("{$k}={$val}");
                $_ENV[$k] = $val;
                $_SERVER[$k] = $val;
            }
            error_log("bootstrap: loaded config/app.php");
        }
    } catch (Throwable $e) {
        error_log("bootstrap: failed loading config/app.php: " . $e->getMessage());
    }
    return;
}

// Nothing loaded; write a small debug log
error_log("bootstrap: no .env or composer autoload found (searched up to {$maxLevels} levels).");