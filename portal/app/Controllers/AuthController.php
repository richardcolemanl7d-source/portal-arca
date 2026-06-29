<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Validator;

class AuthController
{
    public function showLogin(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if ($auth->isLoggedIn()) {
            return Response::redirect('/dashboard');
        }
        
        return Response::view('auth/login', [
            'pageTitle' => 'Iniciar Sesión'
        ], 'auth');
    }

    public function login(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return Response::view('auth/login', [
                'errors' => $validator->errors(),
                'old' => $request->all(),
                'pageTitle' => 'Iniciar Sesión'
            ], 'auth')->setStatusCode(422);
        }

        $auth = Auth::getInstance();
        
        if ($auth->login($request->post('email'), $request->post('password'))) {
            $intendedUrl = $_SESSION['intended_url'] ?? '/dashboard';
            unset($_SESSION['intended_url']);
            
            return Response::redirect($intendedUrl);
        }

        return Response::view('auth/login', [
            'errors' => ['email' => ['Credenciales inválidas']],
            'old' => $request->all(),
            'pageTitle' => 'Iniciar Sesión'
        ], 'auth')->setStatusCode(422);
    }

    public function logout(): Response
    {
        $auth = Auth::getInstance();
        $auth->logout();
        
        return Response::redirect('/login');
    }

    public function showForgotPassword(): Response
    {
        return Response::view('auth/forgot-password', [
            'pageTitle' => 'Recuperar Contraseña'
        ], 'auth');
    }

    public function forgotPassword(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return Response::view('auth/forgot-password', [
                'errors' => $validator->errors(),
                'old' => $request->all(),
                'pageTitle' => 'Recuperar Contraseña'
            ], 'auth')->setStatusCode(422);
        }

        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE email = ? AND is_active = 1", [
            $request->post('email')
        ]);

        if ($user) {
            $auth = Auth::getInstance();
            $token = $auth->generateResetToken($user['id']);
            
            // In production, send email here
            // For now, we'll just log it
            error_log("Password reset token for {$user['email']}: {$token}");
            
            // Log audit
            $db->insert('audit_logs', [
                'user_id' => $user['id'],
                'action' => 'password_reset_requested',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        // Always show success message to prevent email enumeration
        return Response::view('auth/forgot-password', [
            'success' => 'Si el correo existe en nuestro sistema, recibirá instrucciones para restablecer su contraseña.',
            'pageTitle' => 'Recuperar Contraseña'
        ], 'auth');
    }

    public function showResetPassword(string $token): Response
    {
        $auth = Auth::getInstance();
        $user = $auth->validateResetToken($token);

        if (!$user) {
            return Response::view('auth/reset-password', [
                'errors' => ['token' => ['El token de restablecimiento es inválido o ha expirado.']],
                'pageTitle' => 'Restablecer Contraseña'
            ], 'auth')->setStatusCode(422);
        }

        return Response::view('auth/reset-password', [
            'token' => $token,
            'pageTitle' => 'Restablecer Contraseña'
        ], 'auth');
    }

    public function resetPassword(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return Response::view('auth/reset-password', [
                'errors' => $validator->errors(),
                'token' => $request->post('token'),
                'pageTitle' => 'Restablecer Contraseña'
            ], 'auth')->setStatusCode(422);
        }

        $auth = Auth::getInstance();
        $user = $auth->validateResetToken($request->post('token'));

        if (!$user) {
            return Response::view('auth/reset-password', [
                'errors' => ['token' => ['El token de restablecimiento es inválido o ha expirado.']],
                'pageTitle' => 'Restablecer Contraseña'
            ], 'auth')->setStatusCode(422);
        }

        if ($auth->resetPassword($user['id'], $request->post('password'))) {
            // Log audit
            $db = Database::getInstance();
            $db->insert('audit_logs', [
                'user_id' => $user['id'],
                'action' => 'password_reset_completed',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return Response::redirect('/login')->setHeader('X-Message', 'Contraseña restablecida exitosamente');
        }

        return Response::view('auth/reset-password', [
            'errors' => ['general' => ['No se pudo restablecer la contraseña. Intente nuevamente.']],
            'token' => $request->post('token'),
            'pageTitle' => 'Restablecer Contraseña'
        ], 'auth')->setStatusCode(500);
    }

    public function showProfile(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        return Response::view('auth/profile', [
            'user' => $auth->user(),
            'pageTitle' => 'Mi Perfil'
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        $userId = $auth->userId();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:100',
            'email' => "required|email|unique:users,email,{$userId}"
        ]);

        if ($validator->fails()) {
            return Response::view('auth/profile', [
                'user' => $auth->user(),
                'errors' => $validator->errors(),
                'pageTitle' => 'Mi Perfil'
            ])->setStatusCode(422);
        }

        $db = Database::getInstance();
        $db->update('users', [
            'name' => $request->post('name'),
            'email' => $request->post('email')
        ], ['id' => $userId]);

        // Reload user data
        $auth->initialize();

        return Response::view('auth/profile', [
            'user' => $auth->user(),
            'success' => 'Perfil actualizado exitosamente',
            'pageTitle' => 'Mi Perfil'
        ]);
    }

    public function changePassword(Request $request): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return Response::view('auth/profile', [
                'user' => $auth->user(),
                'errors' => $validator->errors(),
                'pageTitle' => 'Mi Perfil'
            ])->setStatusCode(422);
        }

        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$auth->userId()]);

        if (!password_verify($request->post('current_password'), $user['password'])) {
            return Response::view('auth/profile', [
                'user' => $auth->user(),
                'errors' => ['current_password' => ['La contraseña actual es incorrecta']],
                'pageTitle' => 'Mi Perfil'
            ])->setStatusCode(422);
        }

        $db->update('users', [
            'password' => password_hash($request->post('password'), PASSWORD_DEFAULT)
        ], ['id' => $auth->userId()]);

        return Response::view('auth/profile', [
            'user' => $auth->user(),
            'success' => 'Contraseña cambiada exitosamente',
            'pageTitle' => 'Mi Perfil'
        ]);
    }

    // Admin methods
    public function index(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::view('errors/403', [], 'error')->setStatusCode(403);
        }
        
        $db = Database::getInstance();
        $users = $db->fetchAll(
            "SELECT u.*, c.name as company_name 
             FROM users u 
             LEFT JOIN companies c ON u.company_id = c.id 
             ORDER BY u.created_at DESC"
        );
        
        return Response::view('admin/users/index', [
            'user' => $auth->user(),
            'users' => $users,
            'pageTitle' => 'Gestión de Usuarios'
        ]);
    }

    public function store(Request $request): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:admin,cliente,operador',
            'company_id' => 'nullable|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }

        $db = Database::getInstance();
        $userId = $db->insert('users', [
            'name' => $request->post('name'),
            'email' => $request->post('email'),
            'password' => password_hash($request->post('password'), PASSWORD_DEFAULT),
            'role' => $request->post('role'),
            'company_id' => $request->post('company_id') ?: null,
            'is_active' => 1
        ]);

        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $auth->userId(),
            'action' => 'user_created',
            'entity_type' => 'users',
            'entity_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return Response::json(['success' => true, 'user_id' => $userId]);
    }

    public function update(Request $request, int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:100',
            'email' => "required|email|unique:users,email,{$id}",
            'role' => 'required|in:admin,cliente,operador',
            'company_id' => 'nullable|exists:companies,id',
            'is_active' => 'nullable|in:0,1'
        ]);

        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }

        $db = Database::getInstance();
        
        $data = [
            'name' => $request->post('name'),
            'email' => $request->post('email'),
            'role' => $request->post('role'),
            'company_id' => $request->post('company_id') ?: null
        ];
        
        if ($request->has('is_active')) {
            $data['is_active'] = (int) $request->post('is_active');
        }
        
        $db->update('users', $data, ['id' => $id]);

        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $auth->userId(),
            'action' => 'user_updated',
            'entity_type' => 'users',
            'entity_id' => $id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return Response::json(['success' => true]);
    }

    public function destroy(int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        // Prevent deleting yourself
        if ($id === $auth->userId()) {
            return Response::json(['error' => 'No puedes eliminarte a ti mismo'], 422);
        }
        
        $db = Database::getInstance();
        $db->delete('users', ['id' => $id]);

        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $auth->userId(),
            'action' => 'user_deleted',
            'entity_type' => 'users',
            'entity_id' => $id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return Response::json(['success' => true]);
    }
}
