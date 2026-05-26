<?php
/**
 * cafe/remove_item.php
 * Removes a single item from the cart (POST only).
 */
require_once '../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/cafe/cart.php');
}

// CSRF
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect(BASE_URL . '/cafe/cart.php');
}

$itemId = (int)($_POST['item_id'] ?? 0);

if (!$itemId) {
    redirect(BASE_URL . '/cafe/cart.php');
}

if (isLoggedIn()) {
    $pdo  = getDBConnection();
    $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND item_id = ?');
    $stmt->execute([$_SESSION['user_id'], $itemId]);

} else {
    // Guest session cart
    if (isset($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $idx => $entry) {
            if ((int)$entry['item_id'] === $itemId) {
                unset($_SESSION['guest_cart'][$idx]);
                $_SESSION['guest_cart'] = array_values($_SESSION['guest_cart']);
                break;
            }
        }
    }
}

setFlash('info', 'Item removed from your cart.');
redirect(BASE_URL . '/cafe/cart.php');
