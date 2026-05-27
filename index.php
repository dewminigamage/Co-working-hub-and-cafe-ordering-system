<?php
require_once 'config/app.php';

$pageTitle = 'Dashboard';
$pdo       = getDBConnection();

// ── Stats ─────────────────────────────────────────────────────
$totalBookings = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE booking_date = CURDATE()');
$stmt->execute();
$todayBookings = (int) $stmt->fetchColumn();

$totalUsers  = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalItems  = (int) $pdo->query('SELECT COUNT(*) FROM cafe_items WHERE is_available = 1')->fetchColumn();
$myCartCount = getCartCount();

// ── Bookings chart – last 7 days ──────────────────────────────
$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(booking_date,'%a') AS day_label,
            booking_date,
            COUNT(*) AS total
     FROM bookings
     WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY booking_date
     ORDER BY booking_date ASC"
);
$stmt->execute();
$chartRaw = $stmt->fetchAll();

// Build a full 7-day scaffold so missing days show 0
$chartLabels = [];
$chartData   = [];
$mapped = [];
foreach ($chartRaw as $row) {
    $mapped[$row['booking_date']] = (int)$row['total'];
    // not used for label here – we build below
}
for ($i = 6; $i >= 0; $i--) {
    $date          = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D d', strtotime($date));
    $chartData[]   = $mapped[$date] ?? 0;
}

// ── Recent bookings (last 6) ──────────────────────────────────
$recentBookings = $pdo->query(
    'SELECT b.*, u.name AS user_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     ORDER BY b.created_at DESC
     LIMIT 6'
)->fetchAll();

// ── My upcoming bookings (logged-in user) ─────────────────────
$myUpcoming = [];
if (isLoggedIn()) {
    $stmt = $pdo->prepare(
        'SELECT * FROM bookings
         WHERE user_id = ? AND booking_date >= CURDATE()
         ORDER BY booking_date ASC, start_time ASC
         LIMIT 4'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $myUpcoming = $stmt->fetchAll();
}

// ── Featured café items (4 items) ────────────────────────────
$featured = $pdo->query(
    'SELECT * FROM cafe_items WHERE is_available = 1 ORDER BY RAND() LIMIT 4'
)->fetchAll();

// ── Time-based greeting ───────────────────────────────────────
$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$greetEmoji = $hour < 12 ? '☀️' : ($hour < 17 ? '⚡' : '🌙');

// Space badge colours
$spaceBadge = [
    'Hot Desk'       => ['bg' => '#3b82f6', 'icon' => 'bi-laptop'],
    'Private Office' => ['bg' => '#10b981', 'icon' => 'bi-building'],
    'Meeting Room'   => ['bg' => '#f59e0b', 'icon' => 'bi-people'],
    'Event Space'    => ['bg' => '#8b5cf6', 'icon' => 'bi-star'],
    'Phone Booth'    => ['bg' => '#64748b', 'icon' => 'bi-telephone'],
];

// Category colours for café
$catColor = [
    'coffee' => '#6f4e37',
    'tea'    => '#3d9970',
    'snacks' => '#e67e22',
    'meals'  => '#16a085',
    'drinks' => '#2980b9',
];

include ROOT_DIR . '/includes/header.php';
?>

<style>
/* ── Dashboard-specific styles ──────────────────────────────── */

/* Welcome hero */
.dash-hero {
    background: linear-gradient(135deg, #0f4c75 0%, #1565c0 40%, #1b6ca8 70%, #2a9d8f 100%);
    background-size: 300% 300%;
    animation: gradientShift 8s ease infinite;
    border-radius: 1.25rem;
    padding: 2.25rem 2.5rem;
    color: #fff;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.75rem;
}
@keyframes gradientShift {
    0%   { background-position: 0%   50%; }
    50%  { background-position: 100% 50%; }
    100% { background-position: 0%   50%; }
}
.dash-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 260px; height: 260px;
    background: rgba(255,255,255,.07);
    border-radius: 50%;
}
.dash-hero::after {
    content: '';
    position: absolute;
    bottom: -80px; left: 30%;
    width: 200px; height: 200px;
    background: rgba(255,255,255,.05);
    border-radius: 50%;
}
.dash-hero-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: .25rem;
    position: relative; z-index: 1;
}
.dash-hero-sub {
    opacity: .82;
    font-size: .92rem;
    position: relative; z-index: 1;
}
.dash-hero-date {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    backdrop-filter: blur(8px);
    padding: .6rem 1.1rem;
    border-radius: .65rem;
    font-size: .85rem;
    color: #fff;
    position: relative; z-index: 1;
    white-space: nowrap;
}

/* Stat cards */
.stat-card-v2 {
    background: #fff;
    border: none;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
    position: relative;
    overflow: hidden;
    transition: transform .22s, box-shadow .22s;
    text-decoration: none;
    color: inherit;
    display: block;
}
.stat-card-v2:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 32px rgba(0,0,0,.12);
    color: inherit;
}
.stat-card-v2 .stat-bg-icon {
    position: absolute;
    right: -12px; bottom: -12px;
    font-size: 5.5rem;
    opacity: .06;
    line-height: 1;
}
.stat-card-v2 .s-icon {
    width: 48px; height: 48px;
    border-radius: .75rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem;
    margin-bottom: 1rem;
}
.stat-card-v2 .s-num {
    font-size: 2.1rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: .2rem;
    letter-spacing: -1px;
}
.stat-card-v2 .s-label {
    font-size: .8rem;
    color: #94a3b8;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.stat-card-v2 .s-trend {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    font-size: .75rem;
    font-weight: 600;
    margin-top: .5rem;
    padding: .2rem .55rem;
    border-radius: 999px;
}

/* Chart card */
.chart-card {
    background: #fff;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}
.chart-card .card-title {
    font-size: .95rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 1.25rem;
}

/* Section title */
.section-title {
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: #94a3b8;
    margin-bottom: .85rem;
}

/* Recent booking row */
.booking-row {
    display: flex;
    align-items: center;
    gap: .85rem;
    padding: .7rem 0;
    border-bottom: 1px solid #f1f5f9;
}
.booking-row:last-child { border-bottom: none; }
.booking-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    background: linear-gradient(135deg, #0f4c75, #2a9d8f);
}
.booking-row .meta-main { font-size: .875rem; font-weight: 600; color: #1e293b; }
.booking-row .meta-sub  { font-size: .75rem; color: #94a3b8; }

/* Upcoming booking mini card */
.upcoming-card {
    background: #f8fafc;
    border-radius: .75rem;
    padding: .85rem 1rem;
    margin-bottom: .6rem;
    border-left: 3px solid;
    transition: background .15s;
}
.upcoming-card:hover { background: #f1f5f9; }
.upcoming-card .uc-space { font-size: .875rem; font-weight: 600; color: #1e293b; }
.upcoming-card .uc-date  { font-size: .75rem; color: #64748b; margin-top: .15rem; }

/* Quick actions */
.qa-btn {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .85rem 1rem;
    border-radius: .75rem;
    font-size: .875rem;
    font-weight: 600;
    color: #1e293b;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    text-decoration: none;
    transition: all .18s;
    width: 100%;
    margin-bottom: .55rem;
}
.qa-btn:hover {
    background: #0f4c75;
    color: #fff;
    border-color: #0f4c75;
    transform: translateX(4px);
}
.qa-btn:hover .qa-icon { background: rgba(255,255,255,.2) !important; color: #fff !important; }
.qa-icon {
    width: 34px; height: 34px;
    border-radius: .5rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

/* Café featured items */
.cafe-feat-card {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
    overflow: hidden;
    transition: transform .22s, box-shadow .22s;
    height: 100%;
}
.cafe-feat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 32px rgba(0,0,0,.12);
}
.cafe-feat-card img {
    width: 100%;
    height: 170px;
    object-fit: cover;
}
.cafe-feat-card .feat-body    { padding: 1rem; }
.cafe-feat-card .feat-name    { font-size: .9rem; font-weight: 700; color: #1e293b; margin-bottom: .2rem; }
.cafe-feat-card .feat-price   { font-size: 1.05rem; font-weight: 800; color: #0f4c75; }
.cafe-feat-card .feat-cat     {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; padding: .2rem .55rem; border-radius: 999px;
    color: #fff;
}

/* Right sidebar card */
.side-card {
    background: #fff;
    border-radius: 1rem;
    padding: 1.25rem;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
    margin-bottom: 1.25rem;
}

/* Animated counter */
.counter { display: inline-block; }
</style>

<div class="container-xl py-4 px-3 px-md-4">

    <!-- ── Welcome Hero ──────────────────────────────────────── -->
    <div class="dash-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="dash-hero-title">
                    <?= $greetEmoji ?> <?= $greeting ?><?= isLoggedIn() ? ', ' . e($_SESSION['user_name']) : '' ?>!
                </div>
                <p class="dash-hero-sub mb-3">
                    <?php if (isLoggedIn()): ?>
                        Here's your workspace overview for today.
                    <?php else: ?>
                        Book your perfect workspace and order café favourites.
                    <?php endif; ?>
                </p>
                <?php if (!isLoggedIn()): ?>
                    <div class="d-flex gap-2 flex-wrap" style="position:relative;z-index:1">
                        <a href="<?= BASE_URL ?>/auth/register.php"
                           class="btn btn-light fw-600 px-4"
                           style="color:#0f4c75;border-radius:.6rem">
                            <i class="bi bi-person-plus me-1"></i> Get Started
                        </a>
                        <a href="<?= BASE_URL ?>/auth/login.php"
                           class="btn px-4 fw-600"
                           style="background:rgba(255,255,255,.15);color:#fff;border:1.5px solid rgba(255,255,255,.4);border-radius:.6rem">
                            Sign In
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="dash-hero-date">
                <i class="bi bi-calendar3 me-2"></i>
                <?= date('l, d F Y') ?>
                <span class="ms-2 opacity-75"><?= date('H:i') ?></span>
            </div>
        </div>
    </div>

    <!-- ── Stat Cards ─────────────────────────────────────────── -->
    <div class="row g-3 mb-4">

        <!-- Total Bookings -->
        <div class="col-6 col-lg-3">
            <a href="<?= BASE_URL ?>/bookings/index.php" class="stat-card-v2">
                <div class="s-icon" style="background:#dbeafe;color:#1d4ed8">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="s-num text-dark counter" data-target="<?= $totalBookings ?>">0</div>
                <div class="s-label">Total Bookings</div>
                <div class="stat-bg-icon"><i class="bi bi-calendar-check"></i></div>
            </a>
        </div>

        <!-- Today's Bookings -->
        <div class="col-6 col-lg-3">
            <a href="<?= BASE_URL ?>/bookings/index.php?date=<?= date('Y-m-d') ?>" class="stat-card-v2">
                <div class="s-icon" style="background:#d1fae5;color:#065f46">
                    <i class="bi bi-calendar-day"></i>
                </div>
                <div class="s-num text-dark counter" data-target="<?= $todayBookings ?>">0</div>
                <div class="s-label">Today's Bookings</div>
                <div class="stat-bg-icon"><i class="bi bi-calendar-day"></i></div>
            </a>
        </div>

        <!-- Menu Items -->
        <div class="col-6 col-lg-3">
            <a href="<?= BASE_URL ?>/cafe/menu.php" class="stat-card-v2">
                <div class="s-icon" style="background:#ffedd5;color:#c2410c">
                    <i class="bi bi-cup-hot"></i>
                </div>
                <div class="s-num text-dark counter" data-target="<?= $totalItems ?>">0</div>
                <div class="s-label">Menu Items</div>
                <div class="stat-bg-icon"><i class="bi bi-cup-hot"></i></div>
            </a>
        </div>

        <!-- Cart -->
        <div class="col-6 col-lg-3">
            <a href="<?= BASE_URL ?>/cafe/cart.php" class="stat-card-v2">
                <div class="s-icon" style="background:#ede9fe;color:#6d28d9">
                    <i class="bi bi-cart3"></i>
                </div>
                <div class="s-num text-dark counter" data-target="<?= $myCartCount ?>">0</div>
                <div class="s-label">Cart Items</div>
                <div class="stat-bg-icon"><i class="bi bi-cart3"></i></div>
            </a>
        </div>

    </div><!-- /.row stats -->

    <!-- ── Main Content ──────────────────────────────────────── -->
    <div class="row g-4 mb-4">

        <!-- LEFT – Chart + Recent Bookings -->
        <div class="col-lg-8">

            <!-- Bookings Activity Chart -->
            <div class="chart-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="section-title mb-0">Booking Activity</p>
                        <h6 class="fw-700 mb-0" style="color:#1e293b">Last 7 Days</h6>
                    </div>
                    <span class="badge rounded-pill"
                          style="background:#f0f4f8;color:#64748b;font-size:.75rem;padding:.4rem .9rem">
                        <i class="bi bi-bar-chart me-1"></i> Weekly View
                    </span>
                </div>
                <canvas id="bookingsChart" height="110"></canvas>
            </div>

            <!-- Recent Bookings -->
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="section-title mb-0">Recent Activity</p>
                        <h6 class="fw-700 mb-0" style="color:#1e293b">Latest Bookings</h6>
                    </div>
                    <a href="<?= BASE_URL ?>/bookings/index.php"
                       class="btn btn-sm fw-600"
                       style="background:#f0f4f8;color:#0f4c75;border-radius:.55rem;font-size:.78rem">
                        View All <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <?php if ($recentBookings): ?>
                    <?php foreach ($recentBookings as $b):
                        $sc   = $spaceBadge[$b['space_name']] ?? ['bg' => '#64748b', 'icon' => 'bi-building'];
                        $initials = strtoupper(substr($b['user_name'], 0, 1));
                    ?>
                    <div class="booking-row">
                        <div class="booking-avatar"><?= $initials ?></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="meta-main"><?= e($b['user_name']) ?></div>
                            <div class="meta-sub">
                                <span class="badge text-white me-1"
                                      style="background:<?= $sc['bg'] ?>;font-size:.68rem;border-radius:.35rem">
                                    <i class="bi <?= $sc['icon'] ?> me-1"></i><?= e($b['space_name']) ?>
                                </span>
                                <?= e(date('D, d M', strtotime($b['booking_date']))) ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <div style="font-size:.75rem;color:#94a3b8">
                                <?= e(date('d M', strtotime($b['created_at']))) ?>
                            </div>
                            <?php if (isLoggedIn() && (int)$b['user_id'] === (int)$_SESSION['user_id']): ?>
                            <div style="font-size:.65rem" class="mt-1">
                                <a href="<?= BASE_URL ?>/bookings/edit.php?id=<?= (int)$b['id'] ?>"
                                   style="color:#0f4c75;text-decoration:none;font-weight:600">Edit</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-40"></i>
                        No bookings yet.
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /col-lg-8 -->

        <!-- RIGHT – Quick Actions + Upcoming -->
        <div class="col-lg-4">

            <!-- Quick Actions -->
            <div class="side-card">
                <p class="section-title">Quick Actions</p>

                <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/bookings/create.php" class="qa-btn">
                    <span class="qa-icon" style="background:#dbeafe;color:#1d4ed8">
                        <i class="bi bi-calendar-plus"></i>
                    </span>
                    New Booking
                    <i class="bi bi-chevron-right ms-auto opacity-40"></i>
                </a>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/cafe/menu.php" class="qa-btn">
                    <span class="qa-icon" style="background:#ffedd5;color:#c2410c">
                        <i class="bi bi-cup-hot"></i>
                    </span>
                    Browse Café Menu
                    <i class="bi bi-chevron-right ms-auto opacity-40"></i>
                </a>

                <a href="<?= BASE_URL ?>/cafe/cart.php" class="qa-btn">
                    <span class="qa-icon" style="background:#ede9fe;color:#6d28d9">
                        <i class="bi bi-cart3"></i>
                    </span>
                    View My Cart
                    <?php if ($myCartCount > 0): ?>
                        <span class="ms-auto badge"
                              style="background:#f4a261;color:#fff;border-radius:999px;
                                     font-size:.7rem;padding:.25rem .6rem">
                            <?= $myCartCount ?>
                        </span>
                    <?php else: ?>
                        <i class="bi bi-chevron-right ms-auto opacity-40"></i>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>/bookings/index.php" class="qa-btn">
                    <span class="qa-icon" style="background:#d1fae5;color:#065f46">
                        <i class="bi bi-list-ul"></i>
                    </span>
                    All Bookings
                    <i class="bi bi-chevron-right ms-auto opacity-40"></i>
                </a>

                <?php if (!isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="qa-btn">
                    <span class="qa-icon" style="background:#fef9c3;color:#854d0e">
                        <i class="bi bi-box-arrow-in-right"></i>
                    </span>
                    Sign In
                    <i class="bi bi-chevron-right ms-auto opacity-40"></i>
                </a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="qa-btn">
                    <span class="qa-icon" style="background:#fce7f3;color:#9d174d">
                        <i class="bi bi-person-plus"></i>
                    </span>
                    Register
                    <i class="bi bi-chevron-right ms-auto opacity-40"></i>
                </a>
                <?php endif; ?>
            </div>

            <!-- My Upcoming Bookings -->
            <?php if (isLoggedIn()): ?>
            <div class="side-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <p class="section-title mb-0">Upcoming</p>
                    <a href="<?= BASE_URL ?>/bookings/create.php"
                       style="font-size:.75rem;color:#0f4c75;text-decoration:none;font-weight:600">
                        + New
                    </a>
                </div>
                <h6 class="fw-700 mb-3" style="color:#1e293b">My Bookings</h6>

                <?php if ($myUpcoming): ?>
                    <?php foreach ($myUpcoming as $u):
                        $sc  = $spaceBadge[$u['space_name']] ?? ['bg' => '#64748b', 'icon' => 'bi-building'];
                        $isToday = $u['booking_date'] === date('Y-m-d');
                    ?>
                    <div class="upcoming-card" style="border-left-color:<?= $sc['bg'] ?>">
                        <div class="uc-space">
                            <i class="bi <?= $sc['icon'] ?> me-1" style="color:<?= $sc['bg'] ?>"></i>
                            <?= e($u['space_name']) ?>
                            <?php if ($isToday): ?>
                                <span class="badge ms-1"
                                      style="background:#10b981;color:#fff;font-size:.65rem;
                                             border-radius:999px;padding:.2rem .5rem">
                                    Today
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="uc-date">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= e(date('D, d M Y', strtotime($u['booking_date']))) ?>
                            <?php if ($u['start_time']): ?>
                                &nbsp;·&nbsp;
                                <i class="bi bi-clock me-1"></i>
                                <?= e(date('H:i', strtotime($u['start_time']))) ?>
                                <?php if ($u['end_time']): ?>
                                    – <?= e(date('H:i', strtotime($u['end_time']))) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-calendar-x d-block fs-2 mb-2 opacity-40"></i>
                        <p class="small mb-2">No upcoming bookings</p>
                        <a href="<?= BASE_URL ?>/bookings/create.php"
                           class="btn btn-sm fw-600"
                           style="background:#0f4c75;color:#fff;border-radius:.55rem;font-size:.78rem">
                            <i class="bi bi-plus me-1"></i> Book a space
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Hub Stats mini -->
            <div class="side-card" style="background:linear-gradient(135deg,#0f4c75,#2a9d8f);color:#fff">
                <p class="section-title" style="color:rgba(255,255,255,.6)">Hub Overview</p>
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div style="font-size:1.6rem;font-weight:800"><?= $totalUsers ?></div>
                        <div style="font-size:.72rem;opacity:.7">Members</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:1.6rem;font-weight:800"><?= $totalBookings ?></div>
                        <div style="font-size:.72rem;opacity:.7">Bookings</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:1.6rem;font-weight:800"><?= $totalItems ?></div>
                        <div style="font-size:.72rem;opacity:.7">Menu Items</div>
                    </div>
                    <div class="col-6">
                        <div style="font-size:1.6rem;font-weight:800">5</div>
                        <div style="font-size:.72rem;opacity:.7">Space Types</div>
                    </div>
                </div>
            </div>

        </div><!-- /col-lg-4 -->
    </div><!-- /.row main -->

    <!-- ── Featured Café Items ───────────────────────────────── -->
    <?php if ($featured): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <p class="section-title mb-0">Café</p>
                <h6 class="fw-700 mb-0" style="color:#1e293b">Today's Picks ☕</h6>
            </div>
            <a href="<?= BASE_URL ?>/cafe/menu.php"
               class="btn btn-sm fw-600"
               style="background:#f0f4f8;color:#0f4c75;border-radius:.55rem;font-size:.78rem">
                Full Menu <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach ($featured as $item):
                $cc = $catColor[$item['category']] ?? '#64748b';
            ?>
            <div class="col">
                <div class="cafe-feat-card">
                    <img src="<?= e($item['image'] ?: 'https://via.placeholder.com/400x200') ?>"
                         alt="<?= e($item['name']) ?>"
                         loading="lazy"
                         onerror="this.src='https://via.placeholder.com/400x200'">
                    <div class="feat-body">
                        <span class="feat-cat mb-2 d-inline-block"
                              style="background:<?= $cc ?>">
                            <?= e(ucfirst($item['category'])) ?>
                        </span>
                        <div class="feat-name"><?= e($item['name']) ?></div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="feat-price">
                                $<?= number_format((float)$item['price'], 2) ?>
                            </span>
                            <a href="<?= BASE_URL ?>/cafe/menu.php"
                               class="btn btn-sm fw-600"
                               style="background:#0f4c75;color:#fff;border-radius:.5rem;
                                      font-size:.75rem;padding:.25rem .7rem">
                                Order
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.container-xl -->

<!-- ── Chart.js + Counter Animation ────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Animated counters ────────────────────────────────────────
document.querySelectorAll('.counter').forEach(el => {
    const target  = parseInt(el.dataset.target, 10) || 0;
    const duration = 900;
    const step     = Math.max(1, Math.ceil(target / (duration / 16)));
    let current    = 0;
    const timer = setInterval(() => {
        current += step;
        if (current >= target) { current = target; clearInterval(timer); }
        el.textContent = current.toLocaleString();
    }, 16);
});

// ── Bookings Chart ────────────────────────────────────────────
(function () {
    const labels = <?= json_encode($chartLabels) ?>;
    const data   = <?= json_encode($chartData)   ?>;

    const ctx    = document.getElementById('bookingsChart').getContext('2d');

    // Gradient fill
    const grad = ctx.createLinearGradient(0, 0, 0, 260);
    grad.addColorStop(0,   'rgba(15, 76, 117, .25)');
    grad.addColorStop(1,   'rgba(15, 76, 117, .00)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Bookings',
                data,
                backgroundColor: data.map((v, i) =>
                    i === data.length - 1
                        ? 'rgba(244, 162, 97, .85)'    // today = accent
                        : 'rgba(15, 76, 117, .75)'
                ),
                borderRadius: 7,
                borderSkipped: false,
                barThickness: 28,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#f1f5f9',
                    bodyColor:  '#94a3b8',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: ctx =>
                            ` ${ctx.parsed.y} booking${ctx.parsed.y !== 1 ? 's' : ''}`
                    }
                }
            },
            scales: {
                x: {
                    grid:  { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11, weight: '500' } },
                    border:{ display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#94a3b8',
                        font:  { size: 11 },
                        stepSize: 1,
                        precision: 0
                    },
                    grid:  { color: '#f1f5f9' },
                    border:{ display: false }
                }
            },
            animation: { duration: 900, easing: 'easeOutQuart' }
        }
    });
})();
</script>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
