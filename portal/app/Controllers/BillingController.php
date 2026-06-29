<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Database;

class BillingController
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
        
        // Get current subscription
        $subscription = $db->fetch(
            "SELECT s.*, p.name as plan_name, p.price, p.monthly_invoices 
             FROM subscriptions s
             JOIN plans p ON s.plan_id = p.id
             WHERE s.user_id = ? AND s.status = 'active'
             ORDER BY s.created_at DESC
             LIMIT 1",
            [$userId]
        );
        
        // Get payment history
        $payments = $db->fetchAll(
            "SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
            [$userId]
        );
        
        return Response::view('billing/index', [
            'user' => $auth->user(),
            'subscription' => $subscription,
            'payments' => $payments,
            'pageTitle' => 'Facturación'
        ]);
    }
    
    public function history(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isLoggedIn()) {
            return Response::redirect('/login');
        }
        
        $db = Database::getInstance();
        $userId = $auth->userId();
        
        $payments = $db->fetchAll(
            "SELECT p.*, s.plan_id 
             FROM payments p
             LEFT JOIN subscriptions s ON p.subscription_id = s.id
             WHERE p.user_id = ?
             ORDER BY p.created_at DESC",
            [$userId]
        );
        
        return Response::view('billing/history', [
            'user' => $auth->user(),
            'payments' => $payments,
            'pageTitle' => 'Historial de Pagos'
        ]);
    }
    
    // Admin methods
    public function plansIndex(): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::view('errors/403', [], 'error')->setStatusCode(403);
        }
        
        $db = Database::getInstance();
        $plans = $db->fetchAll("SELECT * FROM plans ORDER BY price");
        
        return Response::view('admin/plans/index', [
            'user' => $auth->user(),
            'plans' => $plans,
            'pageTitle' => 'Planes'
        ]);
    }
    
    public function storePlan(Request $request): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $db = Database::getInstance();
        
        $planId = $db->insert('plans', [
            'name' => $request->post('name'),
            'description' => $request->post('description'),
            'monthly_invoices' => (int) $request->post('monthly_invoices'),
            'price' => (float) $request->post('price'),
            'is_active' => 1
        ]);
        
        return Response::json(['success' => true, 'plan_id' => $planId]);
    }
    
    public function updatePlan(Request $request, int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $db = Database::getInstance();
        
        $db->update('plans', [
            'name' => $request->post('name'),
            'description' => $request->post('description'),
            'monthly_invoices' => (int) $request->post('monthly_invoices'),
            'price' => (float) $request->post('price')
        ], ['id' => $id]);
        
        return Response::json(['success' => true]);
    }
    
    public function deletePlan(int $id): Response
    {
        $auth = Auth::getInstance();
        $auth->initialize();
        
        if (!$auth->isAdmin()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }
        
        $db = Database::getInstance();
        $db->delete('plans', ['id' => $id]);
        
        return Response::json(['success' => true]);
    }
}
