<?php if (isset($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general'][0]) ?></div>
<?php endif; ?>

<form method="POST" action="/login">
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
               id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required autofocus>
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'][0]) ?></div>
        <?php endif; ?>
    </div>
    
    <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
               id="password" name="password" required>
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password'][0]) ?></div>
        <?php endif; ?>
    </div>
    
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">Recordarme</label>
    </div>
    
    <button type="submit" class="btn btn-primary w-100 mb-3">
        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
    </button>
    
    <div class="text-center">
        <a href="/forgot-password" class="text-decoration-none">¿Olvidaste tu contraseña?</a>
    </div>
</form>

<div class="mt-4 p-3 bg-light rounded">
    <small class="text-muted">
        <strong>Demo:</strong><br>
        Email: admin@portal.com<br>
        Contraseña: admin123
    </small>
</div>
