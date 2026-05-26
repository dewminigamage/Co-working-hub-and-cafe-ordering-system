<?php
require_once '../config/app.php';

$pageTitle = 'My Cart';
$pdo       = getDBConnection();

// ── Fetch cart items ──────────────────────────────────────────
$cartItems = [];
$total     = 0.0;

if (isLoggedIn()) {
    // DB cart
    $stmt = $pdo->prepare(
        'SELECT c.id AS cart_id, c.quantity,
                ci.id AS item_id, ci.name, ci.price, ci.image, ci.category
         FROM cart c
         JOIN cafe_items ci ON c.item_id = ci.id
         WHERE c.user_id = ?
         ORDER BY c.created_at ASC'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll();

} else {
    // Guest session cart – enrich with DB data
    $guestCart = $_SESSION['guest_cart'] ?? [];
    if ($guestCart) {
        $ids         = array_unique(array_column($guestCart, 'item_id'));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt        = $pdo->prepare(
            "SELECT id AS item_id, name, price, image, category
             FROM cafe_items
             WHERE id IN ($placeholders) AND is_available = 1"
        );
        $stmt->execute($ids);
        $dbItems = [];
        foreach ($stmt->fetchAll() as $row) {
            $dbItems[$row['item_id']] = $row;
        }

        foreach ($guestCart as $idx => $entry) {
            $iid  = (int)$entry['item_id'];
            if (!isset($dbItems[$iid])) continue;
            $cartItems[] = array_merge($dbItems[$iid], [
                'cart_id'  => 'g_' . $idx,
                'quantity' => (int)$entry['quantity'],
            ]);
        }
    }
}

// Calculate total
foreach ($cartItems as $row) {
    $total += (float)$row['price'] * (int)$row['quantity'];
}

include ROOT_DIR . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1><i class="bi bi-cart3 me-2"></i>My Cart</h1>
            <p><?= count($cartItems) ?> item<?= count($cartItems) !== 1 ? 's' : '' ?> in your order</p>
        </div>
        <a href="<?= BASE_URL ?>/cafe/menu.php" class="btn btn-outline-light">
            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
        </a>
    </div>
</div>

<div class="container pb-5">
    <?php if ($cartItems): ?>
    <div class="row g-4">

        <!-- ── Cart items ─────────────────────────────────────── -->
        <div class="col-lg-8">
            <?php foreach ($cartItems as $row): ?>
            <div class="cart-item-row" id="cart-row-<?= e($row['cart_id']) ?>">

                <!-- Image -->
                <img src="<?= e($row['image'] ?: 'https://via.placeholder.com/80x80') ?>"
                     alt="<?= e($row['name']) ?>"
                     class="cart-item-img"
                     onerror="this.src='https://via.placeholder.com/80x80'">

                <!-- Details -->
                <div class="flex-grow-1">
                    <p class="fw-600 mb-0 lh-1"><?= e($row['name']) ?></p>
                    <p class="text-muted small mb-1"><?= e(ucfirst($row['category'])) ?></p>
                    <p class="fw-700 text-primary-hs mb-0">
                        $<?= number_format((float)$row['price'], 2) ?> each
                    </p>
                </div>

                <!-- Quantity form -->
                <form method="POST" action="<?= BASE_URL ?>/cafe/update_cart.php"
                      class="d-flex align-items-center gap-1">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="cart_id"   value="<?= e($row['cart_id']) ?>">
                    <input type="hidden" name="item_id"   value="<?= (int)$row['item_id'] ?>">

                    <button type="submit" name="action" value="decrease"
                            class="btn btn-sm btn-outline-secondary px-2">
                        <i class="bi bi-dash"></i>
                    </button>
                    <span class="px-2 fw-600"><?= (int)$row['quantity'] ?></span>
                    <button type="submit" name="action" value="increase"
                            class="btn btn-sm btn-outline-secondary px-2">
                        <i class="bi bi-plus"></i>
                    </button>
                </form>

                <!-- Subtotal -->
                <div class="text-end" style="min-width:70px">
                    <p class="fw-700 mb-0 text-primary-hs">
                        $<?= number_format((float)$row['price'] * (int)$row['quantity'], 2) ?>
                    </p>
                </div>

                <!-- Remove -->
                <form method="POST" action="<?= BASE_URL ?>/cafe/remove_item.php">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="cart_id"   value="<?= e($row['cart_id']) ?>">
                    <input type="hidden" name="item_id"   value="<?= (int)$row['item_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger px-2"
                            title="Remove" onclick="return confirm('Remove this item?')">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Order summary ──────────────────────────────────── -->
        <div class="col-lg-4">
            <div class="cart-total-card">
                <h5 class="fw-700 mb-3">Order Summary</h5>

                <?php foreach ($cartItems as $row): ?>
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">
                        <?= e($row['name']) ?> × <?= (int)$row['quantity'] ?>
                    </span>
                    <span>$<?= number_format((float)$row['price'] * (int)$row['quantity'], 2) ?></span>
                </div>
                <?php endforeach; ?>

                <hr>

                <div class="d-flex justify-content-between fw-700 fs-5 mb-4">
                    <span>Total</span>
                    <span class="text-primary-hs">$<?= number_format($total, 2) ?></span>
                </div>

                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/cafe/checkout.php"
                       class="btn btn-accent w-100 py-2">
                        <i class="bi bi-bag-check me-2"></i> Checkout
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/login.php"
                       class="btn btn-hubspace w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Log in to Checkout
                    </a>
                    <p class="text-muted small text-center mt-2 mb-0">
                        Your cart is saved for when you return.
                    </p>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/cafe/menu.php"
                   class="btn btn-outline-secondary w-100 mt-2 btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Menu
                </a>
            </div>
        </div>

    </div><!-- /.row -->
    <?php else: ?>

    <!-- Empty cart -->
    <div class="text-center py-5">
        <i class="bi bi-cart-x" style="font-size:4rem;color:#cbd5e1"></i>
        <h4 class="mt-3 fw-700">Your cart is empty</h4>
        <p class="text-muted">Browse our café menu and add your favourites.</p>
        <a href="<?= BASE_URL ?>/cafe/menu.php" class="btn btn-accent mt-1">
            <i class="bi bi-cup-hot me-1"></i> View Menu
        </a>
    </div>

    <?php endif; ?>
</div><!-- /.container -->

<?php include ROOT_DIR . '/includes/footer.php'; ?>
