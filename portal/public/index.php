<?php

// PSR-4 Autoload
require __DIR__ . '/../autoload.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Auth;

// Load routes
$router = require __DIR__ . '/../config/routes.php';

// Create request
$request = new Request();

try {
    // Dispatch router
    $response = $router->dispatch($request);
    $response->send();
} catch (\Exception $e) {
    // Log error
    error_log("Application error: " . $e->getMessage());
    error_log($e->getTraceAsString());
    
    // Show error page
    if ($request->ajax() || strpos($request->uri(), '/api/') === 0) {
        Response::json([
            'error' => 'Internal Server Error',
            'message' => getenv('APP_DEBUG') ? $e->getMessage() : 'An unexpected error occurred'
        ], 500)->send();
    } else {
        Response::view('errors/500', [
            'message' => getenv('APP_DEBUG') ? $e->getMessage() : 'Ha ocurrido un error inesperado'
        ], 'error')->setStatusCode(500)->send();
    }
}
