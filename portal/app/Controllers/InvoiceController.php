<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Database;

class InvoiceController
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
        
        // Get invoices from usage_stats
        $invoices = [];
        if ($companyId) {
            $invoices = $db->fetchAll(
                "SELECT us.*, ak.key_prefix 
                 FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ? AND us.endpoint LIKE '%invoice%'
                 ORDER BY us.created_at DESC
                 LIMIT 50",
                [$companyId]
            );
        }
        
        return Response::view('invoices/index', [
            'user' => $auth->user(),
            'invoices' => $invoices,
            'pageTitle' => 'Facturas'
        ]);
    }

    public function show(int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        $db = Database::getInstance();
        
        $invoice = $db->fetch(
            "SELECT us.*, ak.key_prefix, c.name as company_name
             FROM usage_stats us
             JOIN api_keys ak ON us.api_key_id = ak.id
             JOIN companies c ON ak.company_id = c.id
             WHERE us.id = ?",
            [$id]
        );
        
        if (!$invoice) {
            return Response::view('errors/404', [], 'error')->setStatusCode(404);
        }
        
        // Check permissions
        if (!$auth->isAdmin()) {
            $companyId = $auth->companyId();
            $companyCheck = $db->fetch(
                "SELECT id FROM api_keys ak 
                 JOIN companies c ON ak.company_id = c.id 
                 WHERE ak.company_id = ? AND ak.id = (
                     SELECT api_key_id FROM usage_stats WHERE id = ?
                 )",
                [$companyId, $id]
            );
            
            if (!$companyCheck) {
                return Response::view('errors/403', [], 'error')->setStatusCode(403);
            }
        }
        
        return Response::view('invoices/show', [
            'user' => $auth->user(),
            'invoice' => $invoice,
            'pageTitle' => 'Detalle de Factura'
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
        
        if (!$companyId) {
            return Response::json(['error' => 'No company associated'], 422);
        }
        
        // This would typically call the ARCA API
        // For now, we'll create a sample invoice record
        
        $apiKey = $db->fetch(
            "SELECT * FROM api_keys WHERE company_id = ? AND is_revoked = 0 LIMIT 1",
            [$companyId]
        );
        
        if (!$apiKey) {
            return Response::json(['error' => 'No active API key found'], 422);
        }
        
        // Simulate API call to ARCA
        $invoiceData = [
            'cae' => $this->generateCAE(),
            'cae_vencimiento' => date('Y-m-d', strtotime('+5 days')),
            'tipo_comprobante' => $request->post('tipo_comprobante', '6'),
            'punto_venta' => $request->post('punto_venta', '0001'),
            'numero_comprobante' => $this->generateInvoiceNumber(),
            'importe_total' => $request->post('importe_total', 0),
            'fecha_operacion' => date('Y-m-d')
        ];
        
        // Log the usage
        $usageId = $db->insert('usage_stats', [
            'api_key_id' => $apiKey['id'],
            'endpoint' => '/api/v1/invoice/create',
            'method' => 'POST',
            'status_code' => 200,
            'response_time_ms' => rand(100, 500),
            'request_data' => json_encode($request->all()),
            'response_data' => json_encode($invoiceData),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        return Response::json([
            'success' => true,
            'invoice' => $invoiceData,
            'usage_id' => $usageId
        ]);
    }
    
    private function generateCAE(): string
    {
        // Generate a random CAE number (simulated)
        return rand(70000000000000, 79999999999999);
    }
    
    private function generateInvoiceNumber(): string
    {
        // Generate invoice number (simulated)
        return str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    }
}
