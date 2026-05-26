<?php
require_once '../config/app.php';

$pageTitle = 'Café Menu';
$pdo       = getDBConnection();

// ── Filters ────────────────────────────────────────────────────
$search   = trim(strip_tags($_GET['q']        ?? ''));
$category = trim(strip_tags($_GET['category'] ?? ''));

$validCats = ['coffee', 'tea', 'snacks', 'meals', 'drinks'];

$where  = ['is_available = 1'];
$params = [];

if ($search !== '') {
    $where[]  = '(name LIKE ? OR description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (in_array($category, $validCats, true)) {
    $where[]  = 'category = ?';
    $params[] = $category;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$stmt     = $pdo->prepare("SELECT * FROM cafe_items $whereSQL ORDER BY category, name");
$stmt->execute($params);
$items    = $stmt->fetchAll();

// Category → label + icon
$catMeta = [
    'coffee' => ['label' => 'Coffee',  'icon' => '☕', 'badge' => 'bg-coffee'],
    'tea'    => ['label' => 'Tea',     'icon' => '🍵', 'badge' => 'bg-tea'],
    'snacks' => ['label' => 'Snacks',  'icon' => '🥐', 'badge' => 'bg-snacks'],
    'meals'  => ['label' => 'Meals',   'icon' => '🥗', 'badge' => 'bg-meals'],
    'drinks' => ['label' => 'Drinks',  'icon' => '🥤', 'badge' => 'bg-drinks'],
];

include ROOT_DIR . '/includes/header.php';
?>

<!-- Hero -->
<div class="page-hero">
    <div class="container">
        <h1><i class="bi bi-cup-hot me-2"></i>Café Menu</h1>
        <p>Order coffee, snacks, and meals delivered straight to your desk</p>
    </div>
</div>

<div class="container pb-5">

    <!-- ── Search bar ──────────────────────────────────────────── -->
    <div class="card-hs p-3 mb-4">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <div class="input-group flex-grow-1">
                <span class="input-group-text bg-light border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" name="q" class="form-control border-start-0"
                       placeholder="Search menu items…"
                       value="<?= e($search) ?>"
                       id="menuSearch" autocomplete="off">
                <?php if ($search): ?>
                    <a href="<?= BASE_URL ?>/cafe/menu.php<?= $category ? '?category=' . e($category) : '' ?>"
                       class="btn btn-outline-secondary border-start-0">
                        <i class="bi bi-x"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php if ($category): ?>
                <input type="hidden" name="category" value="<?= e($category) ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-hubspace">Search</button>
        </form>
    </div>

    <!-- ── Category filter tabs ────────────────────────────────── -->
    <nav class="category-tabs mb-4">
        <ul class="nav flex-wrap">
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/cafe/menu.php<?= $search ? '?q=' . urlencode($search) : '' ?>"
                   class="nav-link <?= $category === '' ? 'active' : '' ?>">
                    All
                </a>
            </li>
            <?php foreach ($catMeta as $key => $meta): ?>
            <li class="nav-item">
                <a href="<?= BASE_URL ?>/cafe/menu.php?category=<?= $key ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
                   class="nav-link <?= $category === $key ? 'active' : '' ?>">
                    <?= $meta['icon'] ?> <?= $meta['label'] ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- ── Results count ──────────────────────────────────────── -->
    <?php if ($search || $category): ?>
    <p class="text-muted small mb-3">
        <i class="bi bi-funnel me-1"></i>
        Showing <strong><?= count($items) ?></strong> item<?= count($items) !== 1 ? 's' : '' ?>
        <?= $search    ? ' matching "<strong>' . e($search) . '</strong>"' : '' ?>
        <?= $category  ? ' in <strong>' . e($catMeta[$category]['label'] ?? $category) . '</strong>' : '' ?>
    </p>
    <?php endif; ?>

    <!-- ── Menu grid ──────────────────────────────────────────── -->
    <?php if ($items): ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4" id="menuGrid">
        <?php foreach ($items as $item): ?>
        <div class="col">
            <div class="menu-card">
                <!-- Item image -->
                <img src="<?= e($item['image'] ?: 'https://via.placeholder.com/400x250?text=No+Image') ?>"
                     alt="<?= e($item['name']) ?>"
                     class="card-img-top"
                     loading="lazy"
                     onerror="this.src='https://via.placeholder.com/400x250?text=No+Image'">

                <div class="card-body d-flex flex-column">
                    <!-- Category badge -->
                    <?php $cm = $catMeta[$item['category']] ?? ['badge' => 'bg-secondary', 'icon' => '']; ?>
                    <div class="mb-2">
                        <span class="badge <?= $cm['badge'] ?> category-badge text-white">
                            <?= $cm['icon'] ?> <?= e(ucfirst($item['category'])) ?>
                        </span>
                    </div>

                    <p class="item-name mb-1"><?= e($item['name']) ?></p>
                    <p class="item-desc"><?= e($item['description'] ?? '') ?></p>

                    <div class="d-flex justify-content-between align-items-center mt-auto pt-1">
                        <span class="item-price">$<?= number_format((float)$item['price'], 2) ?></span>

                        <!-- Add to Cart -->
                        <button type="button"
                                class="btn btn-accent btn-sm add-to-cart-btn"
                                data-item-id="<?= (int)$item['id'] ?>"
                                data-item-name="<?= e($item['name']) ?>">
                            <i class="bi bi-cart-plus me-1"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-search fs-1 d-block mb-3 opacity-50"></i>
        <p class="mb-1 fw-500">No items found</p>
        <p class="small">Try a different search or category.</p>
        <a href="<?= BASE_URL ?>/cafe/menu.php" class="btn btn-sm btn-hubspace mt-2">
            View All Items
        </a>
    </div>
    <?php endif; ?>
</div><!-- /.container -->

<!-- ── Toast notification ──────────────────────────────────── -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="cartToast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg">
                <i class="bi bi-cart-check me-2"></i> Added to cart!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- ── AJAX Add-to-cart ─────────────────────────────────────── -->
<script>
(function () {
    const addUrl   = '<?= BASE_URL ?>/cafe/add_to_cart.php';
    const cartUrl  = '<?= BASE_URL ?>/cafe/cart.php';
    const loginUrl = '<?= BASE_URL ?>/auth/login.php';
    const isLoggedIn = <?= isLoggedIn() ? 'true' : 'false' ?>;

    const toastEl  = document.getElementById('cartToast');
    const toastMsg = document.getElementById('toastMsg');
    const toast    = new bootstrap.Toast(toastEl, { delay: 2800 });

    // Update cart badge in navbar
    function updateCartBadge(count) {
        let badge = document.getElementById('cartCount');
        if (count > 0) {
            if (!badge) {
                const cartLink = document.querySelector('a[href*="cart.php"]');
                if (cartLink) {
                    badge = document.createElement('span');
                    badge.id        = 'cartCount';
                    badge.className = 'cart-badge';
                    cartLink.appendChild(badge);
                }
            }
            if (badge) badge.textContent = count;
        }
    }

    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const itemId   = this.dataset.itemId;
            const itemName = this.dataset.itemName;
            const self     = this;

            self.classList.add('loading');
            self.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adding…';

            fetch(addUrl, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    `item_id=${encodeURIComponent(itemId)}&quantity=1&ajax=1&csrf_token=<?= urlencode($_SESSION['csrf_token']) ?>`,
            })
            .then(r => r.json())
            .then(data => {
                self.classList.remove('loading');
                if (data.success) {
                    self.classList.add('added');
                    self.innerHTML = '<i class="bi bi-check-lg me-1"></i> Added!';
                    setTimeout(() => {
                        self.classList.remove('added');
                        self.innerHTML = '<i class="bi bi-cart-plus me-1"></i> Add';
                    }, 1800);

                    toastMsg.innerHTML =
                        '<i class="bi bi-cart-check me-2"></i>' + itemName + ' added to cart!';
                    toast.show();
                    updateCartBadge(data.cart_count);
                } else {
                    // Not logged in or other error
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        toastEl.classList.replace('text-bg-success', 'text-bg-warning');
                        toastMsg.innerHTML = '<i class="bi bi-exclamation me-2"></i>' + (data.message || 'Could not add item.');
                        toast.show();
                        self.innerHTML = '<i class="bi bi-cart-plus me-1"></i> Add';
                    }
                }
            })
            .catch(() => {
                self.classList.remove('loading');
                self.innerHTML = '<i class="bi bi-cart-plus me-1"></i> Add';
            });
        });
    });
})();
</script>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
