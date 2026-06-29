<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-file-alt"></i> Logs de Auditoría</h2>
    <button class="btn btn-outline-secondary" onclick="location.reload()">
        <i class="fas fa-sync"></i> Actualizar
    </button>
</div>

<div class="stat-card">
    <div class="table-responsive">
        <table id="auditLogsTable" class="table table-hover dataTable" style="width: 100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Entidad</th>
                    <th>IP</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                    <td>
                        <?php if ($log['user_name']): ?>
                            <strong><?= htmlspecialchars($log['user_name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($log['user_email']) ?></small>
                        <?php else: ?>
                            <span class="text-muted">Sistema</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= strpos($log['action'], 'delete') !== false ? 'danger' : (strpos($log['action'], 'create') !== false ? 'success' : 'info') ?>">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($log['entity_type']): ?>
                            <?= htmlspecialchars($log['entity_type']) ?>
                            <?php if ($log['entity_id']): ?>
                                <small class="text-muted">#<?= $log['entity_id'] ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?= htmlspecialchars($log['ip_address'] ?? '-') ?></code></td>
                    <td>
                        <small class="text-muted">
                            <?php 
                            $oldValues = json_decode($log['old_values'] ?? '[]', true);
                            $newValues = json_decode($log['new_values'] ?? '[]', true);
                            
                            if (isset($oldValues['description'])) {
                                echo htmlspecialchars($oldValues['description']);
                            } elseif (!empty($oldValues) || !empty($newValues)) {
                                echo 'Cambios registrados';
                            } else {
                                echo '-';
                            }
                            ?>
                        </small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    new DataTable('#auditLogsTable', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });
});
</script>
