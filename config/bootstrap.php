<?php

$startDir = __DIR__; 
$maxLevels = 6;     
$cwd = $startDir;
$foundRoot = null;
$foundAutoload = null;
$foundEnv = null;


for ($i = 0; $i <= $maxLevels; $i++) {
    if (file_exists($cwd . '/vendor/autoload.php')) {
        $foundAutoload = $cwd . '/vendor/autoload.php';
        $foundRoot = $cwd;
        break;
    }
    if (file_exists($cwd . '/.env') && $foundEnv === null) {
        $foundEnv = $cwd . '/.env';
        $foundRoot = $cwd;
  
    }
    $parent = dirname($cwd);
    if ($parent === $cwd) break;
    $cwd = $parent;
}

if ($foundRoot === null) {
    $maybeRoot = dirname(__DIR__); 
    if (file_exists($maybeRoot . '/vendor/autoload.php')) {
        $foundAutoload = $maybeRoot . '/vendor/autoload.php';
        $foundRoot = $maybeRoot;
    } elseif (file_exists($maybeRoot . '/.env')) {
        $foundEnv = $maybeRoot . '/.env';
        $foundRoot = $maybeRoot;
    }
}

if ($foundAutoload) {
    try {
        require_once $foundAutoload;
    } catch (Throwable $e) {
        error_log("bootstrap: failed to require autoload at {$foundAutoload}: " . $e->getMessage());
    }
}

if (class_exists('Dotenv\Dotenv')) {
    try {
        $dotRoot = $foundRoot ?: dirname(__DIR__);
        $dotenv = Dotenv\Dotenv::createImmutable($dotRoot);
        $dotenv->safeLoad();
     
        error_log("bootstrap: phpdotenv loaded from {$dotRoot}/.env (if present)");
        return;
    } catch (Throwable $e) {
        error_log('bootstrap: phpdotenv error: ' . $e->getMessage());
       
    }
}

$envFile = $foundEnv ?? (file_exists(dirname(__DIR__) . '/.env') ? dirname(__DIR__) . '/.env' : null);
if ($envFile && file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;

      
        if (strpos($line, 'export ') === 0) {
            $line = trim(substr($line, 7));
        }
        if (strpos($line, '=') === false) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
          
            $value = str_replace(['\\n','\\r','\\t','\\"',"\\'","\\\\"] , ["\n","\r","\t",'"',"'","\\"], $value);
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
    error_log("bootstrap: parsed .env from {$envFile}");
    return;
}
define('TICKETMASTER_API_KEY', getenv('TM_API_KEY'));

$SEATGEEK_ID = getenv('SEATGEEK_CLIENT_ID') ;
$SEATGEEK_SECRET = getenv('SEATGEEK_CLIENT_SECRET');
if (!defined('SEATGEEK_CLIENT_ID')) {
    define('SEATGEEK_CLIENT_ID',$SEATGEEK_ID);
}
if (!defined('SEATGEEK_CLIENT_SECRET')) {
    define('SEATGEEK_CLIENT_SECRET', $SEATGEEK_SECRET);
}

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


error_log("bootstrap: no .env or composer autoload found (searched up to {$maxLevels} levels).");