<?php
// includes/header.php
// Expects: $pageTitle (string) – set by the including page
// Optionally expects: $bodyClass (string)
if (!defined('BASE_URL')) {
    exit('Direct access not permitted.');
}

$flash     = getFlash();
$cartCount = getCartCount();
$bodyClass = $bodyClass ?? '';
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
    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="<?= e($bodyClass) ?>">

<!-- ============================================================
     NAVIGATION
     ============================================================ -->
<nav class="navbar navbar-hubspace navbar-expand-lg sticky-top">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-building"></i> Hub<span>Space</span>
        </a>

        <!-- Toggler -->
        <button class="navbar-toggler border-0 text-white" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav">
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Left links -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-grid"></i> Dashboard
                    </a>
                </li>

                <!-- Bookings dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#"
                       data-bs-toggle="dropdown" id="bookingsMenu">
                        <i class="bi bi-calendar-check"></i> Bookings
                    </a>
                    <ul class="dropdown-menu shadow-sm" aria-labelledby="bookingsMenu">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/bookings/index.php">
                                <i class="bi bi-list-ul me-2 text-primary-hs"></i> All Bookings
                            </a>
                        </li>
                        <?php if (isLoggedIn()): ?>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/bookings/create.php">
                                <i class="bi bi-plus-circle me-2 text-success"></i> New Booking
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Café -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/cafe/menu.php">
                        <i class="bi bi-cup-hot"></i> Café Menu
                    </a>
                </li>

                <!-- Cart -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/cafe/cart.php">
                        <i class="bi bi-cart3"></i> Cart
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge" id="cartCount"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <!-- Right: auth links -->
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?= e($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    <?= e($_SESSION['user_email']) ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/bookings/index.php">
                                    <i class="bi bi-calendar me-2"></i> My Bookings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/cafe/cart.php">
                                    <i class="bi bi-cart me-2"></i> My Cart
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-accent btn ms-1 py-1 px-3 text-white"
                           href="<?= BASE_URL ?>/auth/register.php">
                            Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
</nav>

<!-- ============================================================
     FLASH MESSAGES
     ============================================================ -->
<?php if ($flash): ?>
<div class="container mt-3">
    <?php
        $alertMap = [
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            'info'    => 'alert-info',
        ];
        $alertClass = $alertMap[$flash['type']] ?? 'alert-info';
    ?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show flash-alert" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
