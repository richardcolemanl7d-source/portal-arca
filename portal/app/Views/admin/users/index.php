<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="fas fa-plus"></i> Nuevo Usuario
    </button>
</div>

<div class="stat-card">
    <div class="table-responsive">
        <table id="usersTable" class="table table-hover dataTable" style="width: 100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Empresa</th>
                    <th>Estado</th>
                    <th>Último Acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'cliente' ? 'success' : 'info') ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td><?= $user['company_name'] ? htmlspecialchars($user['company_name']) : '<span class="text-muted">-</span>' ?></td>
                    <td>
                        <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $user['is_active'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '<span class="text-muted">Nunca</span>' ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary edit-user" 
                                    data-id="<?= $user['id'] ?>"
                                    data-name="<?= htmlspecialchars($user['name']) ?>"
                                    data-email="<?= htmlspecialchars($user['email']) ?>"
                                    data-role="<?= $user['role'] ?>"
                                    data-company_id="<?= $user['company_id'] ?? '' ?>"
                                    data-is_active="<?= $user['is_active'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger delete-user" data-id="<?= $user['id'] ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="userName" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="userName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="userEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="userEmail" name="email" required>
                    </div>
                    <div class="mb-3" id="passwordField">
                        <label for="userPassword" class="form-label">Contraseña *</label>
                        <input type="password" class="form-control" id="userPassword" name="password">
                        <div class="form-text">Mínimo 8 caracteres</div>
                    </div>
                    <div class="mb-3">
                        <label for="userRole" class="form-label">Rol *</label>
                        <select class="form-select" id="userRole" name="role" required>
                            <option value="admin">Administrador</option>
                            <option value="cliente">Cliente</option>
                            <option value="operador">Operador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="userCompany" class="form-label">Empresa</label>
                        <select class="form-select" id="userCompany" name="company_id">
                            <option value="">Sin empresa</option>
                            <!-- Will be populated by JS -->
                        </select>
                    </div>
                    <div class="mb-3 form-check" id="activeCheck">
                        <input type="checkbox" class="form-check-input" id="userActive" name="is_active" value="1">
                        <label class="form-check-label" for="userActive">Usuario activo</label>
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
let companies = [];

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    const table = new DataTable('#usersTable', {
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
    });
    
    // Load companies for dropdown
    fetch('/companies')
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            // Extract companies from the page or make a separate API call
            // For now, we'll load them when opening the modal
        });
    
    // Fetch companies for dropdown
    fetchCompanies();
    
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    const form = document.getElementById('userForm');
    const modalTitle = document.getElementById('modalTitle');
    
    // New user button
    document.querySelector('[data-bs-target="#userModal"]').addEventListener('click', function() {
        resetForm();
        modalTitle.textContent = 'Nuevo Usuario';
        document.getElementById('passwordField').style.display = 'block';
        document.getElementById('userPassword').required = true;
    });
    
    // Edit user buttons
    document.querySelectorAll('.edit-user').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            modalTitle.textContent = 'Editar Usuario';
            
            document.getElementById('userId').value = id;
            document.getElementById('userName').value = this.dataset.name;
            document.getElementById('userEmail').value = this.dataset.email;
            document.getElementById('userRole').value = this.dataset.role;
            document.getElementById('userCompany').value = this.dataset.company_id || '';
            document.getElementById('userActive').checked = this.dataset.is_active == 1;
            
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('userPassword').required = false;
            
            modal.show();
        });
    });
    
    // Delete user buttons
    document.querySelectorAll('.delete-user').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            if (confirm('¿Está seguro de eliminar este usuario?')) {
                fetch(`/admin/users/${id}`, {
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
        const userId = document.getElementById('userId').value;
        const url = userId ? `/admin/users/${userId}` : '/admin/users';
        const method = userId ? 'PUT' : 'POST';
        
        // Remove password if editing and field is empty
        if (userId && !document.getElementById('userPassword').value) {
            formData.delete('password');
        }
        
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
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
}

function fetchCompanies() {
    // This would ideally be an API endpoint
    // For now, we'll just show a message
    console.log('Companies dropdown should be populated');
}
</script>
