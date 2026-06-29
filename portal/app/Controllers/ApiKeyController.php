<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Database;

class ApiKeyController
{
    public function index(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        $db = Database::getInstance();
        $companyId = $auth->companyId();
        
        if ($auth->isAdmin()) {
            $apiKeys = $db->fetchAll(
                "SELECT ak.*, u.name as user_name, c.name as company_name 
                 FROM api_keys ak
                 JOIN users u ON ak.user_id = u.id
                 JOIN companies c ON ak.company_id = c.id
                 ORDER BY ak.created_at DESC"
            );
        } else {
            $apiKeys = $db->fetchAll(
                "SELECT * FROM api_keys WHERE company_id = ? ORDER BY created_at DESC",
                [$companyId]
            );
        }
        
        // Mask API keys
        foreach ($apiKeys as &$key) {
            $key['key_hash'] = $key['key_prefix'] . '...' . substr($key['key_hash'], -4);
        }
        
        return Response::view('api-keys/index', [
            'user' => $auth->user(),
            'apiKeys' => $apiKeys,
            'pageTitle' => 'API Keys'
        ]);
    }

    public function store(Request $request): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $db = Database::getInstance();
        $companyId = $auth->companyId();
        $userId = $auth->userId();
        
        // Admin can create for any company
        if ($auth->isAdmin() && $request->post('company_id')) {
            $companyId = (int) $request->post('company_id');
        }
        
        // Generate API key
        $rawKey = 'arca_' . bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $rawKey);
        $keyPrefix = substr($rawKey, 0, 12);
        
        $expiresAt = null;
        if ($request->post('expires_in')) {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+ ' . $request->post('expires_in') . ' days'));
        }
        
        $permissions = json_encode($request->post('permissions') ?? ['invoices:write', 'invoices:read']);
        
        $apiKeyId = $db->insert('api_keys', [
            'user_id' => $userId,
            'company_id' => $companyId,
            'key_hash' => $keyHash,
            'key_prefix' => $keyPrefix,
            'name' => $request->post('name'),
            'permissions' => $permissions,
            'expires_at' => $expiresAt
        ]);
        
        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $userId,
            'action' => 'api_key_created',
            'entity_type' => 'api_keys',
            'entity_id' => $apiKeyId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Return the raw key only once
        return Response::json([
            'success' => true,
            'api_key' => $rawKey,
            'message' => 'Guarde esta clave API. No se mostrará nuevamente.'
        ]);
    }

    public function revoke(Request $request, int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $db = Database::getInstance();
        $apiKey = $db->fetch("SELECT * FROM api_keys WHERE id = ?", [$id]);
        
        if (!$apiKey) {
            return Response::json(['error' => 'API Key not found'], 404);
        }
        
        // Check permissions
        if (!$auth->isAdmin() && $apiKey['company_id'] !== $auth->companyId()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $db->update('api_keys', [
            'is_revoked' => 1,
            'revoked_at' => date('Y-m-d H:i:s'),
            'revoked_reason' => $request->post('reason') ?? 'Revoked by user'
        ], ['id' => $id]);
        
        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $auth->userId(),
            'action' => 'api_key_revoked',
            'entity_type' => 'api_keys',
            'entity_id' => $id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        return Response::json(['success' => true]);
    }

    public function rotate(Request $request, int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $db = Database::getInstance();
        $apiKey = $db->fetch("SELECT * FROM api_keys WHERE id = ?", [$id]);
        
        if (!$apiKey) {
            return Response::json(['error' => 'API Key not found'], 404);
        }
        
        // Check permissions
        if (!$auth->isAdmin() && $apiKey['company_id'] !== $auth->companyId()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        // Generate new key
        $rawKey = 'arca_' . bin2hex(random_bytes(32));
        $keyHash = hash('sha256', $rawKey);
        $keyPrefix = substr($rawKey, 0, 12);
        
        $db->update('api_keys', [
            'key_hash' => $keyHash,
            'key_prefix' => $keyPrefix,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
        
        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $auth->userId(),
            'action' => 'api_key_rotated',
            'entity_type' => 'api_keys',
            'entity_id' => $id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        return Response::json([
            'success' => true,
            'api_key' => $rawKey,
            'message' => 'Nueva clave API generada. Guarde esta clave, no se mostrará nuevamente.'
        ]);
    }

    public function destroy(int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $db = Database::getInstance();
        $apiKey = $db->fetch("SELECT * FROM api_keys WHERE id = ?", [$id]);
        
        if (!$apiKey) {
            return Response::json(['error' => 'API Key not found'], 404);
        }
        
        // Check permissions
        if (!$auth->isAdmin() && $apiKey['company_id'] !== $auth->companyId()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $db->delete('api_keys', ['id' => $id]);
        
        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $auth->userId(),
            'action' => 'api_key_deleted',
            'entity_type' => 'api_keys',
            'entity_id' => $id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        return Response::json(['success' => true]);
    }
}
