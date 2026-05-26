<?php
require_once '../config/app.php';
requireAuth();

// Accept POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/bookings/index.php');
}

// CSRF check
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect(BASE_URL . '/bookings/index.php');
}

$id  = (int)($_POST['id'] ?? 0);
$pdo = getDBConnection();

// Delete only if this booking belongs to the logged-in user
$stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);

if ($stmt->rowCount() > 0) {
    setFlash('success', 'Booking cancelled successfully.');
} else {
    setFlash('error', 'Booking not found or you do not have permission to delete it.');
}

redirect(BASE_URL . '/bookings/index.php');
