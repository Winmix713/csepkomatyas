<?php

declare(strict_types=1);

// Set strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set response headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'FootballAPI\\';
    $baseDir = __DIR__ . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use FootballAPI\FootballAPI;
use FootballAPI\Utils\ResponseFormatter;

try {
    $api = new FootballAPI();
    $response = $api->processRequest();
    
    echo ResponseFormatter::formatResponse($response);
} catch (Exception $e) {
    echo ResponseFormatter::formatError($e->getMessage(), 500);
}
