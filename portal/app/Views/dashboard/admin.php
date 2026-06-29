<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Usuarios Totales</h6>
                    <h2 class="mb-0"><?= number_format($stats['total_users'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Empresas</h6>
                    <h2 class="mb-0"><?= number_format($stats['total_companies'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fas fa-building fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">API Keys Activas</h6>
                    <h2 class="mb-0"><?= number_format($stats['total_api_keys'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="fas fa-key fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-muted mb-1">Peticiones Hoy</h6>
                    <h2 class="mb-0"><?= number_format($stats['total_requests_today'] ?? 0) ?></h2>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-chart-line fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-users-cog"></i> Usuarios por Rol</h5>
            </div>
            <canvas id="usersByRoleChart" height="200"></canvas>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Peticiones (Últimos 30 días)</h5>
            </div>
            <canvas id="requestsByDayChart" height="200"></canvas>
        </div>
    </div>
</div>

<div class="row g-4 mt-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Errores por Tipo (7 días)</h5>
            </div>
            <canvas id="errorsByTypeChart" height="200"></canvas>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 10 Empresas</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th class="text-end">Peticiones</th>
                        </tr>
                    </thead>
                    <tbody id="topCompaniesBody">
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">Cargando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Accesos Rápidos</h5>
            </div>
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="/admin/users" class="btn btn-outline-primary w-100 py-3">
                        <i class="fas fa-users me-2"></i> Gestionar Usuarios
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="/admin/plans" class="btn btn-outline-success w-100 py-3">
                        <i class="fas fa-box me-2"></i> Gestionar Planes
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="/admin/audit-logs" class="btn btn-outline-info w-100 py-3">
                        <i class="fas fa-file-alt me-2"></i> Ver Logs de Auditoría
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="/companies" class="btn btn-outline-warning w-100 py-3">
                        <i class="fas fa-building me-2"></i> Gestionar Empresas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch statistics
    fetch('/admin/statistics')
        .then(response => response.json())
        .then(data => {
            // Users by Role Chart
            if (data.users_by_role && data.users_by_role.length > 0) {
                const roleCtx = document.getElementById('usersByRoleChart');
                new Chart(roleCtx, {
                    type: 'doughnut',
                    data: {
                        labels: data.users_by_role.map(r => r.role.charAt(0).toUpperCase() + r.role.slice(1)),
                        datasets: [{
                            data: data.users_by_role.map(r => r.count),
                            backgroundColor: ['#3498db', '#2ecc71', '#f39c12']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
            
            // Requests by Day Chart
            if (data.requests_by_day && data.requests_by_day.length > 0) {
                const requestsCtx = document.getElementById('requestsByDayChart');
                new Chart(requestsCtx, {
                    type: 'line',
                    data: {
                        labels: data.requests_by_day.map(d => d.date.substring(5)),
                        datasets: [{
                            label: 'Peticiones',
                            data: data.requests_by_day.map(d => d.count),
                            borderColor: '#9b59b6',
                            backgroundColor: 'rgba(155, 89, 182, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Errors by Type Chart
            if (data.errors_by_type && data.errors_by_type.length > 0) {
                const errorsCtx = document.getElementById('errorsByTypeChart');
                new Chart(errorsCtx, {
                    type: 'bar',
                    data: {
                        labels: data.errors_by_type.map(e => 'Error ' + e.status_code),
                        datasets: [{
                            label: 'Cantidad',
                            data: data.errors_by_type.map(e => e.count),
                            backgroundColor: '#e74c3c'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            // Top Companies
            if (data.top_companies && data.top_companies.length > 0) {
                const tbody = document.getElementById('topCompaniesBody');
                tbody.innerHTML = data.top_companies.map((company, index) => `
                    <tr>
                        <td>
                            <span class="badge bg-secondary me-2">#${index + 1}</span>
                            ${company.name}
                        </td>
                        <td class="text-end">${company.requests.toLocaleString()}</td>
                    </tr>
                `).join('');
            }
        })
        .catch(error => {
            console.error('Error fetching statistics:', error);
        });
});
</script>
