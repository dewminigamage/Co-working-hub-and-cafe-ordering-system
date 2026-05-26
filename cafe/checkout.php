<?php
require_once '../config/app.php';
requireAuth();

$pageTitle = 'Checkout';
$pdo       = getDBConnection();

// ── Fetch current cart ────────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT c.item_id, c.quantity,
            ci.name, ci.price, ci.image, ci.category
     FROM cart c
     JOIN cafe_items ci ON c.item_id = ci.id
     WHERE c.user_id = ?
     ORDER BY c.created_at ASC'
);
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    setFlash('warning', 'Your cart is empty. Add some items before checking out.');
    redirect(BASE_URL . '/cafe/menu.php');
}

// Calculate total
$total = 0.0;
foreach ($cartItems as $row) {
    $total += (float)$row['price'] * (int)$row['quantity'];
}

$order = null;  // populated after POST

// ── Handle "Place Order" ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch.');
    }

    $notes = trim(strip_tags($_POST['notes'] ?? ''));

    // Generate unique order number
    $orderNumber = 'HS-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Ymd');

    // Insert order
    $stmt = $pdo->prepare(
        'INSERT INTO orders (user_id, order_number, total_amount, notes)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$_SESSION['user_id'], $orderNumber, $total, $notes ?: null]);
    $orderId = (int)$pdo->lastInsertId();

    // Insert order items
    $insertItem = $pdo->prepare(
        'INSERT INTO order_items (order_id, item_id, item_name, quantity, unit_price, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($cartItems as $row) {
        $subtotal = (float)$row['price'] * (int)$row['quantity'];
        $insertItem->execute([
            $orderId,
            $row['item_id'],
            $row['name'],
            $row['quantity'],
            $row['price'],
            $subtotal,
        ]);
    }

    // Clear the cart
    $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);

    // Fetch the order back for the receipt
    $stmt = $pdo->prepare(
        'SELECT o.*, u.name AS user_name, u.email AS user_email
         FROM orders o JOIN users u ON o.user_id = u.id
         WHERE o.id = ?'
    );
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    $stmt = $pdo->prepare(
        'SELECT * FROM order_items WHERE order_id = ?'
    );
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll();

    $pageTitle = 'Order Confirmed';
}

include ROOT_DIR . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?php if ($order): ?>
            <h1><i class="bi bi-check-circle me-2"></i>Order Confirmed!</h1>
            <p>Your order has been placed and is being prepared.</p>
        <?php else: ?>
            <h1><i class="bi bi-bag-check me-2"></i>Checkout</h1>
            <p>Review your order before confirming</p>
        <?php endif; ?>
    </div>
</div>

<div class="container pb-5">

    <?php if ($order): ?>
    <!-- ── RECEIPT ─────────────────────────────────────────── -->
    <div class="receipt-card">
        <div class="receipt-header">
            <div style="font-size:2.5rem">✅</div>
            <h3 class="mt-2 mb-1 fw-700">Thank You!</h3>
            <p class="mb-0 opacity-85">Your order has been placed successfully.</p>
            <p class="small opacity-70 mt-1">Order #<?= e($order['order_number']) ?></p>
        </div>

        <div class="receipt-body">
            <!-- Customer info -->
            <div class="row mb-3 small">
                <div class="col-6">
                    <p class="text-muted mb-0">Customer</p>
                    <p class="fw-600 mb-0"><?= e($order['user_name']) ?></p>
                </div>
                <div class="col-6 text-end">
                    <p class="text-muted mb-0">Date & Time</p>
                    <p class="fw-600 mb-0">
                        <?= e(date('d M Y, H:i', strtotime($order['created_at']))) ?>
                    </p>
                </div>
            </div>

            <hr>

            <!-- Order items -->
            <table class="table-hs w-100 mb-3">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Unit</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $oi): ?>
                    <tr>
                        <td><?= e($oi['item_name']) ?></td>
                        <td class="text-center"><?= (int)$oi['quantity'] ?></td>
                        <td class="text-end">$<?= number_format((float)$oi['unit_price'], 2) ?></td>
                        <td class="text-end fw-600">
                            $<?= number_format((float)$oi['subtotal'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <hr>

            <!-- Total -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-700 fs-5">Total Paid</span>
                <span class="fw-700 fs-4 text-primary-hs">
                    $<?= number_format((float)$order['total_amount'], 2) ?>
                </span>
            </div>

            <!-- Status badge -->
            <div class="text-center mb-4">
                <span class="badge bg-success px-3 py-2 fs-6">
                    <i class="bi bi-clock me-1"></i>
                    Status: <?= e(ucfirst($order['status'])) ?>
                </span>
            </div>

            <?php if ($order['notes']): ?>
            <div class="alert alert-light border small">
                <strong>Notes:</strong> <?= e($order['notes']) ?>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2 flex-wrap justify-content-center">
                <a href="<?= BASE_URL ?>/index.php"
                   class="btn btn-hubspace">
                    <i class="bi bi-grid me-1"></i> Back to Dashboard
                </a>
                <a href="<?= BASE_URL ?>/cafe/menu.php"
                   class="btn btn-accent">
                    <i class="bi bi-cup-hot me-1"></i> Order Again
                </a>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ── CHECKOUT FORM ───────────────────────────────────── -->
    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card-hs p-4">
                <h5 class="fw-700 mb-3">
                    <i class="bi bi-receipt me-2 text-primary-hs"></i>Order Review
                </h5>

                <table class="table-hs w-100 mb-3">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $row): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= e($row['image'] ?: '') ?>"
                                         width="36" height="36"
                                         class="rounded-2 object-fit-cover"
                                         onerror="this.style.display='none'"
                                         alt="">
                                    <span class="fw-500"><?= e($row['name']) ?></span>
                                </div>
                            </td>
                            <td class="text-center"><?= (int)$row['quantity'] ?></td>
                            <td class="text-end">$<?= number_format((float)$row['price'], 2) ?></td>
                            <td class="text-end fw-600">
                                $<?= number_format((float)$row['price'] * (int)$row['quantity'], 2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-700 pt-3 border-top">Total</td>
                            <td class="text-end fw-700 pt-3 border-top text-primary-hs fs-5">
                                $<?= number_format($total, 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <form method="POST">
                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="mb-4">
                        <label for="notes" class="form-label">
                            Special instructions
                            <span class="text-muted fw-400">(optional)</span>
                        </label>
                        <textarea id="notes" name="notes" rows="2" class="form-control"
                                  placeholder="e.g. No sugar, extra hot…"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-accent px-5 py-2 fw-600">
                            <i class="bi bi-bag-check me-2"></i> Place Order – $<?= number_format($total, 2) ?>
                        </button>
                        <a href="<?= BASE_URL ?>/cafe/cart.php"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Edit Cart
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .navbar-hubspace, footer, .btn, .page-hero { display: none !important; }
    body { background: #fff !important; }
    .receipt-card { box-shadow: none !important; max-width: 100% !important; }
}
</style>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
