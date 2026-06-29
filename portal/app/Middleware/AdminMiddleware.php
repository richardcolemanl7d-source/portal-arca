<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;

class AdminMiddleware
{
    public function handle(Request $request): ?Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        if (!$auth->isAdmin()) {
            if ($request->ajax() || $this->isApiRequest($request)) {
                return Response::json(['error' => 'Acceso denegado. Se requieren privilegios de administrador.'], 403);
            }
            
            return Response::view('errors/403', [], 'error')->setStatusCode(403);
        }
        
        return null;
    }

    private function isApiRequest(Request $request): bool
    {
        return strpos($request->uri(), '/api/') === 0 ||
               $_SERVER['HTTP_ACCEPT'] === 'application/json';
    }
}
