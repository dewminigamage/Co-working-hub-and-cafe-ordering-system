<?php
// ================================================================
//  config/app.php  –  Central application bootstrap
//  Include this at the very top of every page.
// ================================================================

declare(strict_types=1);

// ── App identity ──────────────────────────────────────────────
define('APP_NAME',    'HubSpace');
define('APP_TAGLINE', 'Co-Working Hub & Café');

// ── Absolute project root ─────────────────────────────────────
define('ROOT_DIR', realpath(__DIR__ . '/..'));

// ── Auto-detect BASE_URL ──────────────────────────────────────
(function () {
    $docRoot    = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'])), '/');
    $projectDir = rtrim(str_replace('\\', '/', ROOT_DIR), '/');
    $relative   = substr($projectDir, strlen($docRoot));   // e.g. /Co-working-hub...
    $scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('BASE_URL', rtrim($scheme . '://' . $_SERVER['HTTP_HOST'] . $relative, '/'));
})();

// ── Session ───────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 7,  // 7 days
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── CSRF token ────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Database ──────────────────────────────────────────────────
require_once ROOT_DIR . '/config/database.php';

// ================================================================
//  Helper functions
// ================================================================

/**
 * Escape a value for safe HTML output (prevents XSS).
 * @param mixed $value
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Store a one-time flash message in the session. */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Retrieve and clear the flash message; returns null if none exists. */
function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/** Issue an HTTP redirect and exit. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Return true if a user is currently logged in. */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/** Redirect to login if the visitor is not authenticated. */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to access that page.');
        redirect(BASE_URL . '/auth/login.php');
    }
}

/**
 * Return the total number of items in the active cart.
 * – Logged-in users  → database cart
 * – Guests           → session cart
 */
function getCartCount(): int
{
    if (isLoggedIn()) {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?'
        );
        $stmt->execute([$_SESSION['user_id']]);
        return (int) $stmt->fetchColumn();
    }

    // Guest session cart: array of ['item_id' => ..., 'quantity' => ...]
    $guestCart = $_SESSION['guest_cart'] ?? [];
    return (int) array_sum(array_column($guestCart, 'quantity'));
}

/**
 * Merge the guest session cart into the DB cart after login.
 * Called once in auth/login.php right after the session is established.
 */
function mergeGuestCart(int $userId): void
{
    if (empty($_SESSION['guest_cart'])) {
        return;
    }

    $pdo = getDBConnection();
    foreach ($_SESSION['guest_cart'] as $entry) {
        $itemId  = (int) $entry['item_id'];
        $qty     = (int) $entry['quantity'];

        // If already in cart, add the quantities; otherwise insert.
        $stmt = $pdo->prepare(
            'INSERT INTO cart (user_id, item_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
        );
        $stmt->execute([$userId, $itemId, $qty]);
    }

    unset($_SESSION['guest_cart']);
}
