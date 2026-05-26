<?php
require_once '../config/app.php';
requireAuth();

$pageTitle = 'New Booking';
$errors    = [];
$values    = [
    'space_name'   => '',
    'booking_date' => '',
    'start_time'   => '',
    'end_time'     => '',
    'notes'        => '',
];

$spaceTypes = [
    'Hot Desk',
    'Private Office',
    'Meeting Room',
    'Event Space',
    'Phone Booth',
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
    } elseif (strtotime($values['booking_date']) < strtotime('today')) {
        $errors['booking_date'] = 'Booking date cannot be in the past.';
    }

    if ($values['start_time'] && $values['end_time']) {
        if ($values['end_time'] <= $values['start_time']) {
            $errors['end_time'] = 'End time must be after start time.';
        }
    }

    if (empty($errors)) {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO bookings (user_id, space_name, booking_date, start_time, end_time, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $_SESSION['user_id'],
            $values['space_name'],
            $values['booking_date'],
            $values['start_time']  ?: null,
            $values['end_time']    ?: null,
            $values['notes']       ?: null,
        ]);

        setFlash('success', 'Booking created successfully!');
        redirect(BASE_URL . '/bookings/index.php');
    }
}

include ROOT_DIR . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-calendar-plus me-2"></i>New Space Booking</h1>
        <p>Reserve your workspace for an upcoming session</p>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card-hs p-4">

                <!-- Back link -->
                <a href="<?= BASE_URL ?>/bookings/index.php"
                   class="text-muted small text-decoration-none d-inline-flex align-items-center mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back to bookings
                </a>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

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
                               min="<?= date('Y-m-d') ?>"
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
                        <textarea id="notes" name="notes" rows="3" class="form-control"
                                  placeholder="e.g. Need projector, whiteboard access…"><?= e($values['notes']) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-hubspace px-4">
                            <i class="bi bi-check-lg me-1"></i> Confirm Booking
                        </button>
                        <a href="<?= BASE_URL ?>/bookings/index.php"
                           class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>

            </div><!-- /.card-hs -->
        </div>
    </div>
</div>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
