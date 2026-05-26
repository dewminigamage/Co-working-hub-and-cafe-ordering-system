<?php
/**
 * cafe/update_cart.php
 * Handles quantity +/- updates from cart.php
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

$action = $_POST['action'] ?? '';
$itemId = (int)($_POST['item_id'] ?? 0);

if (!$itemId || !in_array($action, ['increase', 'decrease'], true)) {
    redirect(BASE_URL . '/cafe/cart.php');
}

if (isLoggedIn()) {
    $pdo = getDBConnection();

    if ($action === 'increase') {
        $stmt = $pdo->prepare(
            'UPDATE cart SET quantity = quantity + 1
             WHERE user_id = ? AND item_id = ?'
        );
        $stmt->execute([$_SESSION['user_id'], $itemId]);
    } else {
        // Decrease: if qty becomes 0, remove the row
        $stmt = $pdo->prepare(
            'SELECT quantity FROM cart WHERE user_id = ? AND item_id = ?'
        );
        $stmt->execute([$_SESSION['user_id'], $itemId]);
        $qty = (int) $stmt->fetchColumn();

        if ($qty <= 1) {
            $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND item_id = ?');
            $stmt->execute([$_SESSION['user_id'], $itemId]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE cart SET quantity = quantity - 1
                 WHERE user_id = ? AND item_id = ?'
            );
            $stmt->execute([$_SESSION['user_id'], $itemId]);
        }
    }

} else {
    // Guest session cart
    if (!isset($_SESSION['guest_cart'])) {
        redirect(BASE_URL . '/cafe/cart.php');
    }

    foreach ($_SESSION['guest_cart'] as $idx => &$entry) {
        if ((int)$entry['item_id'] === $itemId) {
            if ($action === 'increase') {
                $entry['quantity']++;
            } else {
                $entry['quantity']--;
                if ($entry['quantity'] <= 0) {
                    unset($_SESSION['guest_cart'][$idx]);
                    $_SESSION['guest_cart'] = array_values($_SESSION['guest_cart']);
                }
            }
            break;
        }
    }
    unset($entry);
}

redirect(BASE_URL . '/cafe/cart.php');
