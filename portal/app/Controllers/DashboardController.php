<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Database;

class DashboardController
{
    public function index(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        $db = Database::getInstance();
        $userId = $auth->userId();
        $companyId = $auth->companyId();
        
        // Get dashboard statistics
        $stats = [
            'total_invoices' => 0,
            'total_cae' => 0,
            'monthly_consumption' => 0,
            'active_api_keys' => 0,
            'certificate_status' => 'N/A',
            'certificate_expires' => null,
        ];
        
        if ($companyId) {
            // Get invoice count
            $invoiceData = $db->fetch(
                "SELECT COUNT(*) as count FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ? AND us.endpoint LIKE '%invoice%'",
                [$companyId]
            );
            $stats['total_invoices'] = $invoiceData['count'] ?? 0;
            
            // Get CAE count
            $caeData = $db->fetch(
                "SELECT COUNT(*) as count FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ? AND us.status_code = 200",
                [$companyId]
            );
            $stats['total_cae'] = $caeData['count'] ?? 0;
            
            // Get monthly consumption
            $consumptionData = $db->fetch(
                "SELECT COUNT(*) as count FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ? 
                 AND us.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                [$companyId]
            );
            $stats['monthly_consumption'] = $consumptionData['count'] ?? 0;
            
            // Get active API keys
            $keysData = $db->fetch(
                "SELECT COUNT(*) as count FROM api_keys
                 WHERE company_id = ? AND is_revoked = 0",
                [$companyId]
            );
            $stats['active_api_keys'] = $keysData['count'] ?? 0;
            
            // Get certificate info
            $company = $db->fetch(
                "SELECT certificate_path, certificate_expires FROM companies WHERE id = ?",
                [$companyId]
            );
            
            if ($company && $company['certificate_path']) {
                $stats['certificate_status'] = 'Activo';
                $stats['certificate_expires'] = $company['certificate_expires'];
                
                // Check if expiring soon (30 days)
                if ($company['certificate_expires']) {
                    $expiresDate = new \DateTime($company['certificate_expires']);
                    $now = new \DateTime();
                    $diff = $now->diff($expiresDate);
                    
                    if ($diff->days <= 30 && !$diff->invert) {
                        $stats['certificate_status'] = 'Por vencer';
                    } elseif ($diff->invert) {
                        $stats['certificate_status'] = 'Vencido';
                    }
                }
            } else {
                $stats['certificate_status'] = 'No cargado';
            }
        }
        
        // Get recent activity
        $recentActivity = [];
        if ($companyId) {
            $recentActivity = $db->fetchAll(
                "SELECT us.*, ak.key_prefix 
                 FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ?
                 ORDER BY us.created_at DESC
                 LIMIT 10",
                [$companyId]
            );
        }
        
        return Response::view('dashboard/index', [
            'user' => $auth->user(),
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'pageTitle' => 'Dashboard'
        ]);
    }
    
    public function getStats(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }
        
        $db = Database::getInstance();
        $companyId = $auth->companyId();
        
        $stats = [
            'invoices_today' => 0,
            'invoices_month' => 0,
            'errors_today' => 0,
            'avg_latency' => 0,
            'consumption_by_day' => []
        ];
        
        if ($companyId) {
            // Invoices today
            $todayData = $db->fetch(
                "SELECT COUNT(*) as count FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ? 
                 AND DATE(us.created_at) = CURDATE()
                 AND us.endpoint LIKE '%invoice%'",
                [$companyId]
            );
            $stats['invoices_today'] = $todayData['count'] ?? 0;
            
            // Invoices month
            $monthData = $db->fetch(
                "SELECT COUNT(*) as count FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ? 
                 AND MONTH(us.created_at) = MONTH(CURDATE())
                 AND YEAR(us.created_at) = YEAR(CURDATE())
                 AND us.endpoint LIKE '%invoice%'",
                [$companyId]
            );
            $stats['invoices_month'] = $monthData['count'] ?? 0;
            
            // Errors today
            $errorsData = $db->fetch(
                "SELECT COUNT(*) as count FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ? 
                 AND DATE(us.created_at) = CURDATE()
                 AND us.status_code >= 400",
                [$companyId]
            );
            $stats['errors_today'] = $errorsData['count'] ?? 0;
            
            // Average latency
            $latencyData = $db->fetch(
                "SELECT AVG(response_time_ms) as avg_latency FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ? 
                 AND DATE(us.created_at) = CURDATE()
                 AND us.response_time_ms IS NOT NULL",
                [$companyId]
            );
            $stats['avg_latency'] = round($latencyData['avg_latency'] ?? 0, 2);
            
            // Consumption by day (last 7 days)
            $consumptionData = $db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count 
                 FROM usage_stats us
                 JOIN api_keys ak ON us.api_key_id = ak.id
                 WHERE ak.company_id = ?
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC",
                [$companyId]
            );
            $stats['consumption_by_day'] = $consumptionData;
        }
        
        return Response::json($stats);
    }
    
    public function adminIndex(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::view('errors/403', [], 'error')->setStatusCode(403);
        }
        
        $db = Database::getInstance();
        
        // Global statistics
        $stats = [
            'total_users' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
            'total_companies' => $db->fetch("SELECT COUNT(*) as count FROM companies")['count'],
            'total_api_keys' => $db->fetch("SELECT COUNT(*) as count FROM api_keys")['count'],
            'total_requests_today' => $db->fetch(
                "SELECT COUNT(*) as count FROM usage_stats WHERE DATE(created_at) = CURDATE()"
            )['count'],
        ];
        
        return Response::view('dashboard/admin', [
            'user' => $auth->user(),
            'stats' => $stats,
            'pageTitle' => 'Administración'
        ]);
    }
    
    public function statistics(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $db = Database::getInstance();
        
        // Get detailed statistics
        $stats = [
            'users_by_role' => $db->fetchAll(
                "SELECT role, COUNT(*) as count FROM users GROUP BY role"
            ),
            'requests_by_day' => $db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count 
                 FROM usage_stats 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY DATE(created_at) 
                 ORDER BY date ASC"
            ),
            'errors_by_type' => $db->fetchAll(
                "SELECT status_code, COUNT(*) as count 
                 FROM usage_stats 
                 WHERE status_code >= 400 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY status_code"
            ),
            'top_companies' => $db->fetchAll(
                "SELECT c.name, COUNT(us.id) as requests 
                 FROM companies c
                 JOIN api_keys ak ON c.id = ak.company_id
                 JOIN usage_stats us ON ak.id = us.api_key_id
                 WHERE us.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY c.id, c.name
                 ORDER BY requests DESC
                 LIMIT 10"
            )
        ];
        
        return Response::json($stats);
    }
    
    public function auditLogs(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::view('errors/403', [], 'error')->setStatusCode(403);
        }
        
        $db = Database::getInstance();
        
        $logs = $db->fetchAll(
            "SELECT al.*, u.name as user_name, u.email as user_email
             FROM audit_logs al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC
             LIMIT 100"
        );
        
        return Response::view('dashboard/audit-logs', [
            'user' => $auth->user(),
            'logs' => $logs,
            'pageTitle' => 'Auditoría'
        ]);
    }
}
