<div class="row g-4">
    <!-- Stats Cards -->
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= number_format($stats['total_invoices'] ?? 0) ?></h3>
                    <small class="text-muted">Facturas Emitidas</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= number_format($stats['total_cae'] ?? 0) ?></h3>
                    <small class="text-muted">CAE Emitidos</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= number_format($stats['monthly_consumption'] ?? 0) ?></h3>
                    <small class="text-muted">Consumo Mensual</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                    <i class="fas fa-key"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?= number_format($stats['active_api_keys'] ?? 0) ?></h3>
                    <small class="text-muted">API Keys Activas</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Certificate Status & Charts Row -->
<div class="row g-4 mt-2">
    <div class="col-md-4">
        <div class="stat-card h-100">
            <h5 class="mb-3"><i class="fas fa-certificate"></i> Estado del Certificado</h5>
            <div class="text-center py-4">
                <?php
                $statusClass = 'secondary';
                if (($stats['certificate_status'] ?? '') === 'Activo') $statusClass = 'success';
                elseif (($stats['certificate_status'] ?? '') === 'Por vencer') $statusClass = 'warning';
                elseif (($stats['certificate_status'] ?? '') === 'Vencido') $statusClass = 'danger';
                ?>
                <div class="display-4 mb-2">
                    <i class="fas fa-<?= ($stats['certificate_status'] ?? '') === 'Activo' ? 'check-circle text-success' : 'exclamation-circle text-' . $statusClass ?>"></i>
                </div>
                <h4 class="text-<?= $statusClass ?>"><?= htmlspecialchars($stats['certificate_status'] ?? 'N/A') ?></h4>
                <?php if ($stats['certificate_expires']): ?>
                    <p class="text-muted mb-0">
                        Vence: <?= date('d/m/Y', strtotime($stats['certificate_expires'])) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="stat-card h-100">
            <h5 class="mb-3"><i class="fas fa-chart-bar"></i> Consumo por Día</h5>
            <canvas id="consumptionChart" height="150"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row mt-4">
    <div class="col-12">
        <div class="stat-card">
            <h5 class="mb-3"><i class="fas fa-history"></i> Actividad Reciente</h5>
            <?php if (empty($recentActivity)): ?>
                <p class="text-muted text-center py-4">No hay actividad reciente</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Endpoint</th>
                                <th>Método</th>
                                <th>Estado</th>
                                <th>Latencia</th>
                                <th>API Key</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?></td>
                                <td><code><?= htmlspecialchars($activity['endpoint']) ?></code></td>
                                <td>
                                    <span class="badge bg-<?= $activity['method'] === 'GET' ? 'info' : 'primary' ?>">
                                        <?= htmlspecialchars($activity['method']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $activity['status_code'] >= 400 ? 'danger' : 'success' ?>">
                                        <?= $activity['status_code'] ?? 'N/A' ?>
                                    </span>
                                </td>
                                <td><?= $activity['response_time_ms'] ?? '-' ?> ms</td>
                                <td><code><?= htmlspecialchars($activity['key_prefix']) ?>...</code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Consumption Chart
<?php if (!empty($stats['consumption_by_day'])): ?>
const ctx = document.getElementById('consumptionChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php foreach ($stats['consumption_by_day'] as $day): ?>'<?= date('d/m', strtotime($day['date'])) ?>'<?php endforeach; ?>],
        datasets: [{
            label: 'Peticiones',
            data: [<?php foreach ($stats['consumption_by_day'] as $day): ?><?= $day['count'] ?><?php endforeach; ?>],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
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
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
<?php endif; ?>
</script>
