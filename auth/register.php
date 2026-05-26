<?php
require_once '../config/app.php';

// Already logged in → redirect
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$errors = [];
$values = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Input sanitisation ─────────────────────────────────────
    $name     = trim(strip_tags($_POST['name']     ?? ''));
    $email    = trim(strip_tags($_POST['email']    ?? ''));
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $values = ['name' => $name, 'email' => $email];

    // ── Validation ─────────────────────────────────────────────
    if ($name === '') {
        $errors['name'] = 'Full name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Name must be between 2 and 100 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if ($confirm !== $password) {
        $errors['confirm'] = 'Passwords do not match.';
    }

    // Check email uniqueness
    if (!isset($errors['email'])) {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    // ── Create user ────────────────────────────────────────────
    if (empty($errors)) {
        $pdo  = getDBConnection();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hash]);

        setFlash('success', 'Account created successfully! Please log in.');
        redirect(BASE_URL . '/auth/login.php');
    }
}

$pageTitle  = 'Register';
$bodyClass  = 'auth-page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – <?= APP_NAME ?></title>
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
        <p class="auth-subtitle">Create your free account</p>

        <!-- Flash -->
        <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?>
                    alert-dismissible fade show flash-alert mb-3" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <!-- Name -->
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" id="name" name="name"
                           class="form-control border-start-0 <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                           value="<?= e($values['name']) ?>"
                           placeholder="Jane Smith" required>
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" id="email" name="email"
                           class="form-control border-start-0 <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= e($values['email']) ?>"
                           placeholder="jane@example.com" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" id="password" name="password"
                           class="form-control border-start-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                           placeholder="At least 8 characters" required>
                    <button class="btn btn-outline-secondary border-start-0" type="button"
                            id="togglePwd">
                        <i class="bi bi-eye"></i>
                    </button>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" id="confirm_password" name="confirm_password"
                           class="form-control border-start-0 <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                           placeholder="Repeat password" required>
                    <?php if (isset($errors['confirm'])): ?>
                        <div class="invalid-feedback"><?= e($errors['confirm']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-hubspace w-100 py-2">
                <i class="bi bi-person-check me-1"></i> Create Account
            </button>
        </form>

        <p class="text-center mt-3 mb-0 small text-muted">
            Already have an account?
            <a href="<?= BASE_URL ?>/auth/login.php" class="fw-500">Sign in</a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle password visibility
    document.getElementById('togglePwd').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });
</script>
</body>
</html>
