<?php

header("Server: PHP-CLI-Server/Development");
header("X-Powered-By: PHP/" . PHP_VERSION);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false; 
}

if (file_exists(__DIR__ . '/index.php')) {
    require_once __DIR__ . '/index.php';
} else {
    echo "Server running successfully, but index.php was not found!";
}