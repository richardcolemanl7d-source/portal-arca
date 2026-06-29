<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Validator;

class CompanyController
{
    public function index(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        $db = Database::getInstance();
        
        // Admin sees all companies, clients see only their company
        if ($auth->isAdmin()) {
            $companies = $db->fetchAll("SELECT * FROM companies ORDER BY name");
        } else {
            $companyId = $auth->companyId();
            if ($companyId) {
                $companies = [$db->fetch("SELECT * FROM companies WHERE id = ?", [$companyId])];
            } else {
                $companies = [];
            }
        }
        
        return Response::view('companies/index', [
            'user' => $auth->user(),
            'companies' => $companies,
            'pageTitle' => 'Empresas'
        ]);
    }

    public function create(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::view('errors/403', [], 'error')->setStatusCode(403);
        }
        
        return Response::view('companies/create', [
            'user' => $auth->user(),
            'pageTitle' => 'Nueva Empresa'
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
            'name' => 'required|min:3|max:255',
            'cuit' => 'required|unique:companies,cuit',
            'address' => 'nullable|max:500',
            'phone' => 'nullable|max:50',
            'email' => 'nullable|email|max:255'
        ]);

        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }

        $db = Database::getInstance();
        $companyId = $db->insert('companies', [
            'name' => $request->post('name'),
            'cuit' => $request->post('cuit'),
            'address' => $request->post('address'),
            'phone' => $request->post('phone'),
            'email' => $request->post('email'),
            'is_active' => 1
        ]);

        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $auth->userId(),
            'action' => 'company_created',
            'entity_type' => 'companies',
            'entity_id' => $companyId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return Response::json(['success' => true, 'company_id' => $companyId]);
    }

    public function show(int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        $db = Database::getInstance();
        
        // Check permissions
        if (!$auth->isAdmin() && $auth->companyId() !== $id) {
            return Response::view('errors/403', [], 'error')->setStatusCode(403);
        }
        
        $company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$id]);
        
        if (!$company) {
            return Response::view('errors/404', [], 'error')->setStatusCode(404);
        }
        
        // Get API keys for this company
        $apiKeys = $db->fetchAll(
            "SELECT * FROM api_keys WHERE company_id = ? ORDER BY created_at DESC",
            [$id]
        );
        
        // Mask API keys
        foreach ($apiKeys as &$key) {
            $key['key_hash'] = substr($key['key_hash'], 0, 8) . '...' . substr($key['key_hash'], -4);
        }
        
        return Response::view('companies/show', [
            'user' => $auth->user(),
            'company' => $company,
            'apiKeys' => $apiKeys,
            'pageTitle' => $company['name']
        ]);
    }

    public function edit(int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::view('errors/403', [], 'error')->setStatusCode(403);
        }
        
        $db = Database::getInstance();
        $company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$id]);
        
        if (!$company) {
            return Response::view('errors/404', [], 'error')->setStatusCode(404);
        }
        
        return Response::view('companies/edit', [
            'user' => $auth->user(),
            'company' => $company,
            'pageTitle' => 'Editar Empresa'
        ]);
    }

    public function update(Request $request, int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $db = Database::getInstance();
        $company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$id]);
        
        if (!$company) {
            return Response::json(['error' => 'Company not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|max:255',
            'cuit' => "required|unique:companies,cuit,{$id}",
            'address' => 'nullable|max:500',
            'phone' => 'nullable|max:50',
            'email' => 'nullable|email|max:255'
        ]);

        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }

        $db->update('companies', [
            'name' => $request->post('name'),
            'cuit' => $request->post('cuit'),
            'address' => $request->post('address'),
            'phone' => $request->post('phone'),
            'email' => $request->post('email')
        ], ['id' => $id]);

        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $auth->userId(),
            'action' => 'company_updated',
            'entity_type' => 'companies',
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
        
        $db = Database::getInstance();
        $db->delete('companies', ['id' => $id]);

        // Log audit
        $db->insert('audit_logs', [
            'user_id' => $auth->userId(),
            'action' => 'company_deleted',
            'entity_type' => 'companies',
            'entity_id' => $id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return Response::json(['success' => true]);
    }

    public function uploadCertificate(Request $request, int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        if (!$auth->isAdmin() && $auth->companyId() !== $id) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $db = Database::getInstance();
        $company = $db->fetch("SELECT * FROM companies WHERE id = ?", [$id]);
        
        if (!$company) {
            return Response::json(['error' => 'Company not found'], 404);
        }
        
        $config = require __DIR__ . '/../../config/app.php';
        
        // Handle certificate upload
        if ($request->hasFile('certificate')) {
            $file = $request->file('certificate');
            $allowedTypes = $config['allowed_certificate_types'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedTypes)) {
                return Response::json(['errors' => ['certificate' => ['Tipo de archivo no permitido']]], 422);
            }
            
            if ($file['size'] > $config['max_file_size']) {
                return Response::json(['errors' => ['certificate' => ['El archivo es muy grande']]], 422);
            }
            
            $filename = 'cert_' . $id . '_' . time() . '.' . $extension;
            $uploadPath = __DIR__ . '/../../public/uploads/certificates/' . $filename;
            
            if (!is_dir(dirname($uploadPath))) {
                mkdir(dirname($uploadPath), 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Extract expiration date from certificate
                $certContent = file_get_contents($uploadPath);
                $expiresDate = $this->extractCertExpiry($certContent);
                
                $db->update('companies', [
                    'certificate_path' => 'uploads/certificates/' . $filename,
                    'certificate_expires' => $expiresDate
                ], ['id' => $id]);
                
                // Log audit
                $db->insert('audit_logs', [
                    'user_id' => $auth->userId(),
                    'action' => 'certificate_uploaded',
                    'entity_type' => 'companies',
                    'entity_id' => $id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }
        }
        
        // Handle private key upload
        if ($request->hasFile('private_key')) {
            $file = $request->file('private_key');
            $allowedTypes = $config['allowed_key_types'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedTypes)) {
                return Response::json(['errors' => ['private_key' => ['Tipo de archivo no permitido']]], 422);
            }
            
            if ($file['size'] > $config['max_file_size']) {
                return Response::json(['errors' => ['private_key' => ['El archivo es muy grande']]], 422);
            }
            
            $filename = 'key_' . $id . '_' . time() . '.' . $extension;
            $uploadPath = __DIR__ . '/../../public/uploads/keys/' . $filename;
            
            if (!is_dir(dirname($uploadPath))) {
                mkdir(dirname($uploadPath), 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $db->update('companies', [
                    'private_key_path' => 'uploads/keys/' . $filename
                ], ['id' => $id]);
                
                // Log audit
                $db->insert('audit_logs', [
                    'user_id' => $auth->userId(),
                    'action' => 'private_key_uploaded',
                    'entity_type' => 'companies',
                    'entity_id' => $id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }
        }
        
        return Response::json(['success' => true]);
    }
    
    private function extractCertExpiry(string $certContent): ?string
    {
        // Try to extract expiry date from X.509 certificate
        if (strpos($certContent, '-----BEGIN CERTIFICATE-----') !== false) {
            $certInfo = openssl_x509_parse($certContent);
            if ($certInfo && isset($certInfo['validTo_time_t'])) {
                return date('Y-m-d', $certInfo['validTo_time_t']);
            }
        }
        return null;
    }
}
