<?php
// includes/header.php
// Expects: $pageTitle (string) – set by the including page
if (!defined('BASE_URL')) {
    exit('Direct access not permitted.');
}

$flash     = getFlash();
$cartCount = getCartCount();
$bodyClass = $bodyClass ?? '';

// Determine active nav item from script name
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$currentDir    = basename(dirname($_SERVER['SCRIPT_NAME']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?> – <?= APP_NAME ?></title>

    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons 1.11 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom theme -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="<?= e($bodyClass) ?>">

<!-- ============================================================
     NAVBAR
     ============================================================ -->
<nav class="navbar navbar-hubspace navbar-expand-lg sticky-top">
    <div class="container-xl">

        <!-- ── Brand ───────────────────────────────────────────── -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
            <span style="background:rgba(255,255,255,.15);width:34px;height:34px;border-radius:.5rem;
                         display:flex;align-items:center;justify-content:center;font-size:1.1rem">
                <i class="bi bi-building"></i>
            </span>
            Hub<span>Space</span>
        </a>

        <!-- ── Mobile toggler ──────────────────────────────────── -->
        <button class="navbar-toggler border-0 p-1" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                style="color:rgba(255,255,255,.8)">
            <i class="bi bi-list fs-4"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- ── Left nav ─────────────────────────────────────── -->
            <ul class="navbar-nav me-auto gap-1">

                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'index.php' && $currentDir !== 'auth' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-grid-1x2"></i> Dashboard
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $currentDir === 'bookings' ? 'active' : '' ?>"
                       href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-calendar-check"></i> Bookings
                    </a>
                    <ul class="dropdown-menu shadow border-0"
                        style="border-radius:.75rem;min-width:200px">
                        <li>
                            <a class="dropdown-item py-2" href="<?= BASE_URL ?>/bookings/index.php">
                                <span style="width:28px;height:28px;background:#dbeafe;color:#1d4ed8;
                                             border-radius:.4rem;display:inline-flex;align-items:center;
                                             justify-content:center;font-size:.8rem;margin-right:.6rem">
                                    <i class="bi bi-list-ul"></i>
                                </span>
                                All Bookings
                            </a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                        <li>
                            <a class="dropdown-item py-2" href="<?= BASE_URL ?>/bookings/create.php">
                                <span style="width:28px;height:28px;background:#d1fae5;color:#065f46;
                                             border-radius:.4rem;display:inline-flex;align-items:center;
                                             justify-content:center;font-size:.8rem;margin-right:.6rem">
                                    <i class="bi bi-plus-circle"></i>
                                </span>
                                New Booking
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentDir === 'cafe' && $currentScript === 'menu.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/cafe/menu.php">
                        <i class="bi bi-cup-hot"></i> Café
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentDir === 'cafe' && $currentScript === 'cart.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/cafe/cart.php">
                        <i class="bi bi-cart3"></i> Cart
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge" id="cartCount"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

            </ul>

            <!-- ── Right: user / auth ──────────────────────────── -->
            <ul class="navbar-nav align-items-lg-center gap-1 ms-2">
                <?php if (isLoggedIn()): ?>

                    <!-- Avatar + dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                           href="#" data-bs-toggle="dropdown">
                            <span style="width:30px;height:30px;background:rgba(255,255,255,.25);
                                         border-radius:50%;display:inline-flex;align-items:center;
                                         justify-content:center;font-weight:700;font-size:.82rem;color:#fff">
                                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                            </span>
                            <span class="d-none d-lg-inline"><?= e($_SESSION['user_name']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0"
                            style="border-radius:.75rem;min-width:210px;padding:.4rem">
                            <li class="px-3 py-2 mb-1"
                                style="background:#f8fafc;border-radius:.5rem">
                                <p class="mb-0 fw-600 small" style="color:#1e293b">
                                    <?= e($_SESSION['user_name']) ?>
                                </p>
                                <p class="mb-0 text-muted" style="font-size:.75rem">
                                    <?= e($_SESSION['user_email']) ?>
                                </p>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-2 py-2" style="font-size:.875rem"
                                   href="<?= BASE_URL ?>/bookings/index.php">
                                    <i class="bi bi-calendar3 me-2 text-primary-hs"></i> My Bookings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-2 py-2" style="font-size:.875rem"
                                   href="<?= BASE_URL ?>/cafe/cart.php">
                                    <i class="bi bi-cart3 me-2" style="color:#6d28d9"></i> My Cart
                                    <?php if ($cartCount > 0): ?>
                                        <span class="badge rounded-pill ms-1"
                                              style="background:#f4a261;font-size:.65rem">
                                            <?= $cartCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item rounded-2 py-2 text-danger" style="font-size:.875rem"
                                   href="<?= BASE_URL ?>/auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/auth/register.php"
                           class="btn btn-sm fw-600 ms-1"
                           style="background:rgba(255,255,255,.18);color:#fff;border:1.5px solid
                                  rgba(255,255,255,.35);border-radius:.5rem;padding:.35rem .9rem">
                            Register
                        </a>
                    </li>

                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ============================================================
     FLASH MESSAGES
     ============================================================ -->
<?php if ($flash): ?>
<div class="container-xl px-3 px-md-4 mt-3">
    <?php
        $alertMap = [
            'success' => ['cls' => 'alert-success',  'icon' => 'bi-check-circle-fill'],
            'error'   => ['cls' => 'alert-danger',   'icon' => 'bi-x-circle-fill'],
            'warning' => ['cls' => 'alert-warning',  'icon' => 'bi-exclamation-triangle-fill'],
            'info'    => ['cls' => 'alert-info',     'icon' => 'bi-info-circle-fill'],
        ];
        $am = $alertMap[$flash['type']] ?? $alertMap['info'];
    ?>
    <div class="alert <?= $am['cls'] ?> alert-dismissible fade show d-flex align-items-center gap-2
                flash-alert" role="alert" style="border-radius:.75rem;border:none;font-size:.875rem">
        <i class="bi <?= $am['icon'] ?> flex-shrink-0"></i>
        <span><?= e($flash['message']) ?></span>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
