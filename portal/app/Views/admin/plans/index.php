<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-box"></i> Gestión de Planes</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#planModal">
        <i class="fas fa-plus"></i> Nuevo Plan
    </button>
</div>

<div class="row g-4">
    <?php foreach ($plans as $plan): ?>
    <div class="col-md-4">
        <div class="stat-card h-100 position-relative">
            <?php if (!$plan['is_active']): ?>
            <span class="badge bg-secondary position-absolute top-0 end-0 m-3">Inactivo</span>
            <?php endif; ?>
            <div class="text-center mb-3">
                <i class="fas fa-box fa-3x text-primary mb-3"></i>
                <h4><?= htmlspecialchars($plan['name']) ?></h4>
            </div>
            <p class="text-muted"><?= htmlspecialchars($plan['description'] ?? '') ?></p>
            <hr>
            <ul class="list-unstyled mb-3">
                <li class="mb-2">
                    <i class="fas fa-file-invoice text-success me-2"></i>
                    <?= number_format($plan['monthly_invoices']) ?> facturas/mes
                </li>
                <li class="mb-2">
                    <i class="fas fa-dollar-sign text-warning me-2"></i>
                    $<?= number_format($plan['price'], 2, ',', '.') ?> ARS/mes
                </li>
            </ul>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-primary edit-plan" 
                        data-id="<?= $plan['id'] ?>"
                        data-name="<?= htmlspecialchars($plan['name']) ?>"
                        data-description="<?= htmlspecialchars($plan['description'] ?? '') ?>"
                        data-monthly_invoices="<?= $plan['monthly_invoices'] ?>"
                        data-price="<?= $plan['price'] ?>">
                    <i class="fas fa-edit"></i> Editar
                </button>
                <button class="btn btn-outline-danger delete-plan" data-id="<?= $plan['id'] ?>">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="planForm">
                <input type="hidden" id="planId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="planName" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="planName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="planDescription" class="form-label">Descripción</label>
                        <textarea class="form-control" id="planDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="planInvoices" class="form-label">Facturas Mensuales *</label>
                        <input type="number" class="form-control" id="planInvoices" name="monthly_invoices" required min="0">
                    </div>
                    <div class="mb-3">
                        <label for="planPrice" class="form-label">Precio Mensual (ARS) *</label>
                        <input type="number" step="0.01" class="form-control" id="planPrice" name="price" required min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('planModal'));
    const form = document.getElementById('planForm');
    const modalTitle = document.getElementById('modalTitle');
    
    // New plan button
    document.querySelector('[data-bs-target="#planModal"]').addEventListener('click', function() {
        resetForm();
        modalTitle.textContent = 'Nuevo Plan';
    });
    
    // Edit plan buttons
    document.querySelectorAll('.edit-plan').forEach(button => {
        button.addEventListener('click', function() {
            modalTitle.textContent = 'Editar Plan';
            
            document.getElementById('planId').value = this.dataset.id;
            document.getElementById('planName').value = this.dataset.name;
            document.getElementById('planDescription').value = this.dataset.description;
            document.getElementById('planInvoices').value = this.dataset.monthly_invoices;
            document.getElementById('planPrice').value = this.dataset.price;
            
            modal.show();
        });
    });
    
    // Delete plan buttons
    document.querySelectorAll('.delete-plan').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            if (confirm('¿Está seguro de eliminar este plan? Esta acción no se puede deshacer.')) {
                fetch(`/admin/plans/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Error al eliminar');
                    }
                })
                .catch(error => {
                    alert('Error al eliminar: ' + error);
                });
            }
        });
    });
    
    // Form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const planId = document.getElementById('planId').value;
        const url = planId ? `/admin/plans/${planId}` : '/admin/plans';
        const method = planId ? 'PUT' : 'POST';
        
        fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                if (data.errors) {
                    let errorMsg = '';
                    for (const [field, messages] of Object.entries(data.errors)) {
                        errorMsg += messages.join('\n') + '\n';
                    }
                    alert(errorMsg);
                } else {
                    alert(data.error || 'Error al guardar');
                }
            }
        })
        .catch(error => {
            alert('Error al guardar: ' + error);
        });
    });
});

function resetForm() {
    document.getElementById('planForm').reset();
    document.getElementById('planId').value = '';
}
</script>
