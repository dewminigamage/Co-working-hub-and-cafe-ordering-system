<?php
require_once '../config/app.php';

$pageTitle = 'All Bookings';
$pdo       = getDBConnection();

// ── Optional date filter ───────────────────────────────────────
$filterDate = trim($_GET['date'] ?? '');
$search     = trim($_GET['q']    ?? '');

$where  = [];
$params = [];

if ($filterDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
    $where[]  = 'b.booking_date = ?';
    $params[] = $filterDate;
}

if ($search !== '') {
    $where[]  = '(u.name LIKE ? OR b.space_name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$bookings = $pdo->prepare(
    "SELECT b.*, u.name AS user_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     $whereSQL
     ORDER BY b.booking_date DESC, b.start_time ASC"
);
$bookings->execute($params);
$bookings = $bookings->fetchAll();

// Space → badge
$spaceBadge = [
    'Hot Desk'       => 'bg-primary',
    'Private Office' => 'bg-success',
    'Meeting Room'   => 'bg-warning text-dark',
    'Event Space'    => 'bg-info text-dark',
    'Phone Booth'    => 'bg-secondary',
];

include ROOT_DIR . '/includes/header.php';
?>

<!-- Hero -->
<div class="page-hero">
    <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1><i class="bi bi-calendar-check me-2"></i>Space Bookings</h1>
            <p>All scheduled bookings across the hub</p>
        </div>
        <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/bookings/create.php" class="btn btn-accent">
            <i class="bi bi-plus-lg me-1"></i> New Booking
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="container pb-5">

    <!-- Filters -->
    <div class="card-hs p-3 mb-4">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <label class="form-label small fw-500 mb-1">Search member / space</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="e.g. Jane or Meeting Room"
                       value="<?= e($search) ?>">
            </div>
            <div class="col-sm-4">
                <label class="form-label small fw-500 mb-1">Filter by date</label>
                <input type="date" name="date" class="form-control form-control-sm"
                       value="<?= e($filterDate) ?>">
            </div>
            <div class="col-sm-3 d-flex gap-2">
                <button type="submit" class="btn btn-hubspace btn-sm flex-grow-1">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="<?= BASE_URL ?>/bookings/index.php" class="btn btn-outline-secondary btn-sm">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card-hs">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-list-ul me-2"></i>
                <?= count($bookings) ?> booking<?= count($bookings) !== 1 ? 's' : '' ?>
                <?= $filterDate ? ' on ' . e(date('d M Y', strtotime($filterDate))) : '' ?>
            </span>
        </div>
        <div class="table-responsive">
            <?php if ($bookings): ?>
            <table class="table-hs w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th>Space</th>
                        <th>Date</th>
                        <th>Time Slot</th>
                        <th>Notes</th>
                        <th>Booked At</th>
                        <?php if (isLoggedIn()): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $i => $b): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <span class="fw-500"><?= e($b['user_name']) ?></span>
                            <?php if (isLoggedIn() && (int)$b['user_id'] === (int)$_SESSION['user_id']): ?>
                                <span class="badge bg-light text-primary-hs border ms-1" style="font-size:.65rem">You</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $bc = $spaceBadge[$b['space_name']] ?? 'bg-secondary'; ?>
                            <span class="badge <?= $bc ?> space-badge"><?= e($b['space_name']) ?></span>
                        </td>
                        <td><?= e(date('D, d M Y', strtotime($b['booking_date']))) ?></td>
                        <td>
                            <?php if ($b['start_time'] && $b['end_time']): ?>
                                <span class="small">
                                    <?= e(date('H:i', strtotime($b['start_time']))) ?>
                                    &ndash;
                                    <?= e(date('H:i', strtotime($b['end_time']))) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">–</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small">
                            <?= $b['notes'] ? e(mb_strimwidth($b['notes'], 0, 40, '…')) : '–' ?>
                        </td>
                        <td class="text-muted small">
                            <?= e(date('d M Y H:i', strtotime($b['created_at']))) ?>
                        </td>
                        <?php if (isLoggedIn()): ?>
                        <td>
                            <?php if ((int)$b['user_id'] === (int)$_SESSION['user_id']): ?>
                                <a href="<?= BASE_URL ?>/bookings/edit.php?id=<?= (int)$b['id'] ?>"
                                   class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <!-- Delete with confirmation modal -->
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal"
                                        data-id="<?= (int)$b['id'] ?>"
                                        data-space="<?= e($b['space_name']) ?>"
                                        data-date="<?= e(date('d M Y', strtotime($b['booking_date']))) ?>"
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">–</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
                <p class="mb-0">No bookings found.</p>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/bookings/create.php" class="btn btn-sm btn-hubspace mt-3">
                        <i class="bi bi-plus-lg me-1"></i> Create a booking
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<?php if (isLoggedIn()): ?>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Cancel Booking
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Are you sure you want to cancel this booking?</p>
                <p class="text-muted small mb-0">
                    <strong id="modalSpace"></strong> on <strong id="modalDate"></strong>
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">Keep it</button>
                <form id="deleteForm" method="POST"
                      action="<?= BASE_URL ?>/bookings/delete.php">
                    <input type="hidden" name="csrf_token"
                           value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Yes, Cancel It
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        document.getElementById('deleteId').value    = btn.dataset.id;
        document.getElementById('modalSpace').textContent = btn.dataset.space;
        document.getElementById('modalDate').textContent  = btn.dataset.date;
    });
</script>
<?php endif; ?>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
