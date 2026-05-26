<?php
/**
 * cafe/add_to_cart.php
 *
 * Handles "Add to Cart" for both:
 *  – AJAX   (POST with ajax=1)  → returns JSON
 *  – Regular POST (no ajax=1)   → redirects back to menu
 *
 * Guests  → session cart
 * Users   → database cart  (INSERT … ON DUPLICATE KEY UPDATE)
 */
require_once '../config/app.php';

$isAjax = !empty($_POST['ajax']) && $_POST['ajax'] === '1';

$respond = function (bool $ok, string $msg = '', array $extra = []) use ($isAjax): void {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
        exit;
    }
    if ($ok) {
        setFlash('success', $msg);
    } else {
        setFlash('error', $msg);
    }
    redirect(BASE_URL . '/cafe/menu.php');
};

// ── POST only ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/cafe/menu.php');
}

// ── CSRF check ────────────────────────────────────────────────
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    $respond(false, 'Invalid request token.');
}

$itemId   = (int)($_POST['item_id']  ?? 0);
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

if (!$itemId) {
    $respond(false, 'Invalid item.');
}

// Verify item exists and is available
$pdo  = getDBConnection();
$stmt = $pdo->prepare('SELECT id, name FROM cafe_items WHERE id = ? AND is_available = 1');
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    $respond(false, 'Item not found or unavailable.');
}

// ── Add to cart ───────────────────────────────────────────────
if (isLoggedIn()) {
    // DB cart
    $stmt = $pdo->prepare(
        'INSERT INTO cart (user_id, item_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
    );
    $stmt->execute([$_SESSION['user_id'], $itemId, $quantity]);

    $cartCount = getCartCount();
    $respond(true, $item['name'] . ' added to your cart!', ['cart_count' => $cartCount]);

} else {
    // Guest session cart
    if (!isset($_SESSION['guest_cart'])) {
        $_SESSION['guest_cart'] = [];
    }

    $found = false;
    foreach ($_SESSION['guest_cart'] as &$entry) {
        if ((int)$entry['item_id'] === $itemId) {
            $entry['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    unset($entry);

    if (!$found) {
        $_SESSION['guest_cart'][] = [
            'item_id'  => $itemId,
            'quantity' => $quantity,
        ];
    }

    $cartCount = getCartCount();
    $respond(true, $item['name'] . ' added to your cart!', ['cart_count' => $cartCount]);
}
