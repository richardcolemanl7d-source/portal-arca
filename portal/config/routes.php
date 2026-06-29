<?php

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

$router = new Router();

// Public routes
$router->get('/', 'App\Controllers\DashboardController@index');
$router->get('/login', 'App\Controllers\AuthController@showLogin');
$router->post('/login', 'App\Controllers\AuthController@login');
$router->get('/logout', 'App\Controllers\AuthController@logout');
$router->get('/forgot-password', 'App\Controllers\AuthController@showForgotPassword');
$router->post('/forgot-password', 'App\Controllers\AuthController@forgotPassword');
$router->get('/reset-password/{token}', 'App\Controllers\AuthController@showResetPassword');
$router->post('/reset-password', 'App\Controllers\AuthController@resetPassword');

// Protected routes
$router->group(['middleware' => [AuthMiddleware::class]], function (Router $router) {
    // Dashboard
    $router->get('/dashboard', 'App\Controllers\DashboardController@index');
    $router->get('/dashboard/stats', 'App\Controllers\DashboardController@getStats');
    
    // Companies
    $router->get('/companies', 'App\Controllers\CompanyController@index');
    $router->get('/companies/create', 'App\Controllers\CompanyController@create');
    $router->post('/companies', 'App\Controllers\CompanyController@store');
    $router->get('/companies/{id}', 'App\Controllers\CompanyController@show');
    $router->get('/companies/{id}/edit', 'App\Controllers\CompanyController@edit');
    $router->put('/companies/{id}', 'App\Controllers\CompanyController@update');
    $router->delete('/companies/{id}', 'App\Controllers\CompanyController@destroy');
    $router->post('/companies/{id}/upload-certificate', 'App\Controllers\CompanyController@uploadCertificate');
    
    // API Keys
    $router->get('/api-keys', 'App\Controllers\ApiKeyController@index');
    $router->post('/api-keys', 'App\Controllers\ApiKeyController@store');
    $router->put('/api-keys/{id}/revoke', 'App\Controllers\ApiKeyController@revoke');
    $router->put('/api-keys/{id}/rotate', 'App\Controllers\ApiKeyController@rotate');
    $router->delete('/api-keys/{id}', 'App\Controllers\ApiKeyController@destroy');
    
    // Invoices
    $router->get('/invoices', 'App\Controllers\InvoiceController@index');
    $router->get('/invoices/{id}', 'App\Controllers\InvoiceController@show');
    $router->post('/invoices', 'App\Controllers\InvoiceController@store');
    
    // Billing
    $router->get('/billing', 'App\Controllers\BillingController@index');
    $router->get('/billing/history', 'App\Controllers\BillingController@history');
    
    // Profile
    $router->get('/profile', 'App\Controllers\AuthController@showProfile');
    $router->put('/profile', 'App\Controllers\AuthController@updateProfile');
    $router->put('/profile/change-password', 'App\Controllers\AuthController@changePassword');
});

// Admin routes
$router->group(['middleware' => [AuthMiddleware::class, AdminMiddleware::class]], function (Router $router) {
    // Admin Dashboard
    $router->get('/admin', 'App\Controllers\DashboardController@adminIndex');
    
    // User Management
    $router->get('/admin/users', 'App\Controllers\AuthController@index');
    $router->post('/admin/users', 'App\Controllers\AuthController@store');
    $router->put('/admin/users/{id}', 'App\Controllers\AuthController@update');
    $router->delete('/admin/users/{id}', 'App\Controllers\AuthController@destroy');
    
    // Plans Management
    $router->get('/admin/plans', 'App\Controllers\BillingController@plansIndex');
    $router->post('/admin/plans', 'App\Controllers\BillingController@storePlan');
    $router->put('/admin/plans/{id}', 'App\Controllers\BillingController@updatePlan');
    $router->delete('/admin/plans/{id}', 'App\Controllers\BillingController@deletePlan');
    
    // Statistics
    $router->get('/admin/statistics', 'App\Controllers\DashboardController@statistics');
    $router->get('/admin/audit-logs', 'App\Controllers\DashboardController@auditLogs');
});

return $router;
