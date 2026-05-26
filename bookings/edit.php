<?php
require_once '../config/app.php';
requireAuth();

$pageTitle = 'Edit Booking';
$pdo       = getDBConnection();
$id        = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    setFlash('error', 'Invalid booking.');
    redirect(BASE_URL . '/bookings/index.php');
}

// Load booking – verify ownership
$stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('error', 'Booking not found or you do not have permission to edit it.');
    redirect(BASE_URL . '/bookings/index.php');
}

$spaceTypes = [
    'Hot Desk',
    'Private Office',
    'Meeting Room',
    'Event Space',
    'Phone Booth',
];

$errors = [];
$values = [
    'space_name'   => $booking['space_name'],
    'booking_date' => $booking['booking_date'],
    'start_time'   => $booking['start_time'] ?? '',
    'end_time'     => $booking['end_time']   ?? '',
    'notes'        => $booking['notes']      ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF token mismatch.');
    }

    // Sanitise
    $values['space_name']   = trim(strip_tags($_POST['space_name']   ?? ''));
    $values['booking_date'] = trim($_POST['booking_date']  ?? '');
    $values['start_time']   = trim($_POST['start_time']    ?? '');
    $values['end_time']     = trim($_POST['end_time']      ?? '');
    $values['notes']        = trim(strip_tags($_POST['notes'] ?? ''));

    // Validate
    if (!in_array($values['space_name'], $spaceTypes, true)) {
        $errors['space_name'] = 'Please select a valid space type.';
    }

    if ($values['booking_date'] === '') {
        $errors['booking_date'] = 'Booking date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['booking_date'])) {
        $errors['booking_date'] = 'Invalid date format.';
    }

    if ($values['start_time'] && $values['end_time']) {
        if ($values['end_time'] <= $values['start_time']) {
            $errors['end_time'] = 'End time must be after start time.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE bookings
             SET space_name = ?, booking_date = ?, start_time = ?, end_time = ?, notes = ?
             WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([
            $values['space_name'],
            $values['booking_date'],
            $values['start_time'] ?: null,
            $values['end_time']   ?: null,
            $values['notes']      ?: null,
            $id,
            $_SESSION['user_id'],
        ]);

        setFlash('success', 'Booking updated successfully!');
        redirect(BASE_URL . '/bookings/index.php');
    }
}

include ROOT_DIR . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-pencil-square me-2"></i>Edit Booking</h1>
        <p>Update the details of your space reservation</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card-hs p-4">

                <a href="<?= BASE_URL ?>/bookings/index.php"
                   class="text-muted small text-decoration-none d-inline-flex align-items-center mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to bookings
                </a>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <!-- Space type -->
                    <div class="mb-3">
                        <label for="space_name" class="form-label">Space Type *</label>
                        <select id="space_name" name="space_name"
                                class="form-select <?= isset($errors['space_name']) ? 'is-invalid' : '' ?>"
                                required>
                            <option value="">— Select a space —</option>
                            <?php foreach ($spaceTypes as $st): ?>
                                <option value="<?= e($st) ?>"
                                    <?= $values['space_name'] === $st ? 'selected' : '' ?>>
                                    <?= e($st) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['space_name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['space_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Date -->
                    <div class="mb-3">
                        <label for="booking_date" class="form-label">Booking Date *</label>
                        <input type="date" id="booking_date" name="booking_date"
                               class="form-control <?= isset($errors['booking_date']) ? 'is-invalid' : '' ?>"
                               value="<?= e($values['booking_date']) ?>"
                               required>
                        <?php if (isset($errors['booking_date'])): ?>
                            <div class="invalid-feedback"><?= e($errors['booking_date']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Time slot -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="start_time" class="form-label">
                                Start Time <span class="text-muted fw-400">(optional)</span>
                            </label>
                            <input type="time" id="start_time" name="start_time"
                                   class="form-control"
                                   value="<?= e($values['start_time']) ?>">
                        </div>
                        <div class="col-6">
                            <label for="end_time" class="form-label">
                                End Time <span class="text-muted fw-400">(optional)</span>
                            </label>
                            <input type="time" id="end_time" name="end_time"
                                   class="form-control <?= isset($errors['end_time']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($values['end_time']) ?>">
                            <?php if (isset($errors['end_time'])): ?>
                                <div class="invalid-feedback"><?= e($errors['end_time']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">
                            Notes <span class="text-muted fw-400">(optional)</span>
                        </label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="form-control"><?= e($values['notes']) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-hubspace px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                        <a href="<?= BASE_URL ?>/bookings/index.php"
                           class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
