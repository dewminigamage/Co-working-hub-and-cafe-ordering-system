<?php
require_once '../config/app.php';

// Already logged in → redirect
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$errors   = [];
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim(strip_tags($_POST['email']    ?? ''));
    $password = $_POST['password'] ?? '';

    // ── Validation ─────────────────────────────────────────────
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    }
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['general'] = 'Invalid email or password. Please try again.';
        } else {
            // ── Successful login ───────────────────────────────
            session_regenerate_id(true);

            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            // Merge any guest session cart into the DB cart
            mergeGuestCart($user['id']);

            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(BASE_URL . '/index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – <?= APP_NAME ?></title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">

        <!-- Logo -->
        <div class="auth-logo mb-1">
            <i class="bi bi-building"></i> Hub<span>Space</span>
        </div>
        <p class="auth-subtitle">Sign in to your account</p>

        <!-- Flash -->
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?>
                    alert-dismissible fade show flash-alert mb-3" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- General error -->
        <?php if (isset($errors['general'])): ?>
        <div class="alert alert-danger flash-alert mb-3">
            <i class="bi bi-exclamation-circle me-2"></i><?= e($errors['general']) ?>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" id="email" name="email"
                           class="form-control border-start-0 <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= e($email) ?>"
                           placeholder="jane@example.com" required autofocus>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" id="password" name="password"
                           class="form-control border-start-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="Your password" required>
                    <button class="btn btn-outline-secondary border-start-0" type="button"
                            id="togglePwd">
                        <i class="bi bi-eye"></i>
                    </button>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-hubspace w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>

        <p class="text-center mt-3 mb-0 small text-muted">
            Don't have an account?
            <a href="<?= BASE_URL ?>/auth/register.php" class="fw-500">Register here</a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('togglePwd').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = this.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
</script>
</body>
</html>
