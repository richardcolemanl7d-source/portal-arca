<?php

namespace App\Core;

use PDOException;

class Auth
{
    private static ?Auth $instance = null;
    private ?array $user = null;
    private bool $initialized = false;

    private function __construct()
    {
    }

    public static function getInstance(): Auth
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        Session::start();
        
        if (Session::has('user_id')) {
            $this->loadUser(Session::get('user_id'));
        }
        
        $this->initialized = true;
    }

    private function loadUser(int $userId): void
    {
        try {
            $db = Database::getInstance();
            $this->user = $db->fetch(
                "SELECT u.*, c.name as company_name, c.cuit as company_cuit 
                 FROM users u 
                 LEFT JOIN companies c ON u.company_id = c.id 
                 WHERE u.id = ? AND u.is_active = 1",
                [$userId]
            );
            
            if ($this->user && isset($this->user['password'])) {
                unset($this->user['password']);
            }
        } catch (PDOException $e) {
            error_log("Auth loadUser error: " . $e->getMessage());
            $this->user = null;
        }
    }

    public function login(string $email, string $password): bool
    {
        try {
            $db = Database::getInstance();
            
            $user = $db->fetch(
                "SELECT * FROM users WHERE email = ? AND is_active = 1",
                [$email]
            );
            
            if (!$user) {
                return false;
            }
            
            if (!password_verify($password, $user['password'])) {
                return false;
            }
            
            // Update last login
            $db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $user['id']]);
            
            // Store in session
            Session::start();
            Session::set('user_id', $user['id']);
            Session::set('user_role', $user['role']);
            
            // Log audit
            $this->logAudit($user['id'], 'login', 'User logged in');
            
            $this->user = $user;
            unset($this->user['password']);
            
            return true;
        } catch (PDOException $e) {
            error_log("Auth login error: " . $e->getMessage());
            return false;
        }
    }

    public function logout(): void
    {
        if ($this->isLoggedIn()) {
            $this->logAudit($this->user['id'], 'logout', 'User logged out');
        }
        
        Session::destroy();
        $this->user = null;
        $this->initialized = false;
    }

    public function isLoggedIn(): bool
    {
        return $this->user !== null;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function userId(): ?int
    {
        return $this->user['id'] ?? null;
    }

    public function userEmail(): ?string
    {
        return $this->user['email'] ?? null;
    }

    public function userName(): ?string
    {
        return $this->user['name'] ?? null;
    }

    public function userRole(): ?string
    {
        return $this->user['role'] ?? null;
    }

    public function companyId(): ?int
    {
        return $this->user['company_id'] ?? null;
    }

    public function isAdmin(): bool
    {
        return $this->userRole() === 'admin';
    }

    public function isCliente(): bool
    {
        return $this->userRole() === 'cliente';
    }

    public function isOperador(): bool
    {
        return $this->userRole() === 'operador';
    }

    public function hasRole(array $roles): bool
    {
        return in_array($this->userRole(), $roles);
    }

    public function generateResetToken(int $userId): ?string
    {
        try {
            $db = Database::getInstance();
            
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $db->update('users', [
                'reset_token' => hash('sha256', $token),
                'reset_token_expires' => $expires
            ], ['id' => $userId]);
            
            return $token;
        } catch (PDOException $e) {
            error_log("Auth generateResetToken error: " . $e->getMessage());
            return null;
        }
    }

    public function validateResetToken(string $token): ?array
    {
        try {
            $db = Database::getInstance();
            
            $hashedToken = hash('sha256', $token);
            
            $user = $db->fetch(
                "SELECT * FROM users 
                 WHERE reset_token = ? 
                 AND reset_token_expires > NOW() 
                 AND is_active = 1",
                [$hashedToken]
            );
            
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("Auth validateResetToken error: " . $e->getMessage());
            return null;
        }
    }

    public function resetPassword(int $userId, string $newPassword): bool
    {
        try {
            $db = Database::getInstance();
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $db->update('users', [
                'password' => $hashedPassword,
                'reset_token' => null,
                'reset_token_expires' => null
            ], ['id' => $userId]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Auth resetPassword error: " . $e->getMessage());
            return false;
        }
    }

    private function logAudit(?int $userId, string $action, string $description): void
    {
        try {
            $db = Database::getInstance();
            
            $request = new Request();
            
            $db->insert('audit_logs', [
                'user_id' => $userId,
                'action' => $action,
                'old_values' => json_encode(['description' => $description]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        } catch (PDOException $e) {
            error_log("Auth logAudit error: " . $e->getMessage());
        }
    }
}
