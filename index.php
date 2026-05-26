<?php
require_once 'config/app.php';

$pageTitle = 'Dashboard';
$pdo       = getDBConnection();

// ── Stats ─────────────────────────────────────────────────────
$totalBookings = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()');
$stmt->execute();
$todayBookings = (int) $stmt->fetchColumn();

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalItems = (int) $pdo->query('SELECT COUNT(*) FROM cafe_items WHERE is_available = 1')->fetchColumn();

// My cart count (already computed in getCartCount)
$myCartCount = getCartCount();

// Recent bookings (latest 8)
$recentBookings = $pdo->query(
    'SELECT b.*, u.name AS user_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     ORDER BY b.created_at DESC
     LIMIT 8'
)->fetchAll();

// Space type → badge colour map
$spaceBadge = [
    'Hot Desk'        => 'bg-primary',
    'Private Office'  => 'bg-success',
    'Meeting Room'    => 'bg-warning text-dark',
    'Event Space'     => 'bg-info text-dark',
    'Phone Booth'     => 'bg-secondary',
];

include ROOT_DIR . '/includes/header.php';
?>

<!-- ── Hero ─────────────────────────────────────────────────── -->
<div class="page-hero">
    <div class="container">
        <?php if (isLoggedIn()): ?>
            <h1>Welcome back, <?= e($_SESSION['user_name']) ?> 👋</h1>
            <p>Manage your workspace bookings and order from the café – all in one place.</p>
        <?php else: ?>
            <h1>Welcome to <span style="color:#f4a261">HubSpace</span></h1>
            <p>Book your perfect workspace and order café favourites while you work.</p>
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-accent">
                    <i class="bi bi-person-plus me-1"></i> Get Started
                </a>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-light">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container pb-5">

    <!-- ── Stat cards ────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <a class="stat-card" href="<?= BASE_URL ?>/bookings/index.php">
                <div class="stat-icon blue"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <h3><?= $totalBookings ?></h3>
                    <p>Total Bookings</p>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="stat-card" href="<?= BASE_URL ?>/bookings/index.php">
                <div class="stat-icon green"><i class="bi bi-calendar-day"></i></div>
                <div>
                    <h3><?= $todayBookings ?></h3>
                    <p>Today's Bookings</p>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="stat-card" href="<?= BASE_URL ?>/cafe/menu.php">
                <div class="stat-icon orange"><i class="bi bi-cup-hot"></i></div>
                <div>
                    <h3><?= $totalItems ?></h3>
                    <p>Menu Items</p>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a class="stat-card" href="<?= BASE_URL ?>/cafe/cart.php">
                <div class="stat-icon purple"><i class="bi bi-cart3"></i></div>
                <div>
                    <h3><?= $myCartCount ?></h3>
                    <p>Items in My Cart</p>
                </div>
            </a>
        </div>
    </div>

    <!-- ── Quick actions ─────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card-hs p-4 h-100 d-flex flex-column">
                <h5 class="fw-600 mb-1">
                    <i class="bi bi-calendar-plus text-primary-hs me-2"></i>Book a Space
                </h5>
                <p class="text-muted small flex-grow-1">
                    Reserve a hot desk, meeting room, private office or event space.
                </p>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/bookings/create.php" class="btn btn-hubspace align-self-start">
                        <i class="bi bi-plus-lg me-1"></i> New Booking
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-hubspace align-self-start">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Log in to book
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-hs p-4 h-100 d-flex flex-column">
                <h5 class="fw-600 mb-1">
                    <i class="bi bi-cup-hot text-accent me-2"></i>Order from the Café
                </h5>
                <p class="text-muted small flex-grow-1">
                    Browse our menu of coffees, teas, snacks, and meals – delivered to your desk.
                </p>
                <a href="<?= BASE_URL ?>/cafe/menu.php" class="btn btn-accent align-self-start">
                    <i class="bi bi-arrow-right me-1"></i> View Menu
                </a>
            </div>
        </div>
    </div>

    <!-- ── Recent bookings ───────────────────────────────────── -->
    <div class="card-hs">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-clock-history me-2 text-primary-hs"></i>Recent Bookings</span>
            <a href="<?= BASE_URL ?>/bookings/index.php" class="btn btn-sm btn-hubspace">
                View All
            </a>
        </div>
        <div class="table-responsive">
            <?php if ($recentBookings): ?>
            <table class="table-hs w-100">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Space</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Booked At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBookings as $b): ?>
                    <tr>
                        <td>
                            <span class="fw-500"><?= e($b['user_name']) ?></span>
                        </td>
                        <td>
                            <?php
                                $badgeClass = $spaceBadge[$b['space_name']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $badgeClass ?> space-badge">
                                <?= e($b['space_name']) ?>
                            </span>
                        </td>
                        <td><?= e(date('D, d M Y', strtotime($b['booking_date']))) ?></td>
                        <td>
                            <?php if ($b['start_time'] && $b['end_time']): ?>
                                <?= e(date('H:i', strtotime($b['start_time']))) ?>
                                &ndash;
                                <?= e(date('H:i', strtotime($b['end_time']))) ?>
                            <?php else: ?>
                                <span class="text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= e(date('d M Y H:i', strtotime($b['created_at']))) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                No bookings yet.
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/bookings/create.php">Create the first one!</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.container -->

<?php include ROOT_DIR . '/includes/footer.php'; ?>
