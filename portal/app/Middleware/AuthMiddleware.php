<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;

class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            if ($request->ajax() || $this->isApiRequest($request)) {
                return Response::json(['error' => 'No autorizado. Debe iniciar sesión.'], 401);
            }
            
            // Store intended URL for redirect after login
            $_SESSION['intended_url'] = $request->uri();
            
            return Response::redirect('/login');
        }
        
        return null;
    }

    private function isApiRequest(Request $request): bool
    {
        return strpos($request->uri(), '/api/') === 0 ||
               $request->getHeader('Accept') === 'application/json';
    }
}
