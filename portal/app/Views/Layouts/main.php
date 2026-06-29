<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Portal ARCA' ?> - Portal ARCA</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, #1a252f 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            font-size: 1.25rem;
            font-weight: bold;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li a {
            display: block;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--secondary-color);
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .top-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            margin: -2rem -2rem 2rem -2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .btn-sidebar-toggle {
            display: none;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .btn-sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-file-invoice"></i> Portal ARCA
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="/dashboard" class="<?= ($pageTitle ?? '') == 'Dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="/companies" class="<?= ($pageTitle ?? '') == 'Empresas' ? 'active' : '' ?>">
                    <i class="fas fa-building"></i> Empresas
                </a>
            </li>
            <li>
                <a href="/api-keys" class="<?= ($pageTitle ?? '') == 'API Keys' ? 'active' : '' ?>">
                    <i class="fas fa-key"></i> API Keys
                </a>
            </li>
            <li>
                <a href="/invoices" class="<?= ($pageTitle ?? '') == 'Facturas' ? 'active' : '' ?>">
                    <i class="fas fa-file-invoice-dollar"></i> Facturas
                </a>
            </li>
            <li>
                <a href="/billing" class="<?= ($pageTitle ?? '') == 'Facturación' ? 'active' : '' ?>">
                    <i class="fas fa-credit-card"></i> Facturación
                </a>
            </li>
            <?php if (($user['role'] ?? '') === 'admin'): ?>
            <li class="sidebar-header text-uppercase text-muted small fw-bold px-3 mt-3 mb-2">Administración</li>
            <li>
                <a href="/admin" class="<?= strpos($pageTitle ?? '', 'Administración') !== false ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> Dashboard Admin
                </a>
            </li>
            <li>
                <a href="/admin/users" class="<?= ($pageTitle ?? '') == 'Gestión de Usuarios' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i> Usuarios
                </a>
            </li>
            <li>
                <a href="/admin/plans" class="<?= ($pageTitle ?? '') == 'Planes' ? 'active' : '' ?>">
                    <i class="fas fa-box"></i> Planes
                </a>
            </li>
            <li>
                <a href="/admin/audit-logs" class="<?= ($pageTitle ?? '') == 'Auditoría' ? 'active' : '' ?>">
                    <i class="fas fa-shield-alt"></i> Auditoría
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="/profile">
                    <i class="fas fa-user"></i> Mi Perfil
                </a>
            </li>
            <li>
                <a href="/logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="btn btn-outline-secondary btn-sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="d-flex align-items-center">
                <span class="me-3"><?= htmlspecialchars($user['name'] ?? 'Usuario') ?></span>
                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : 'primary' ?>">
                    <?= ucfirst($user['role'] ?? 'cliente') ?>
                </span>
            </div>
        </div>

        <!-- Page Content -->
        <?= $content ?? '' ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        
        // Initialize DataTables
        $(document).ready(function() {
            $('.data-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                responsive: true
            });
        });
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>
