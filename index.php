<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/nav.php';
require_login();

$search   = trim((string) ($_GET['search'] ?? ''));
$category = (string) ($_GET['category'] ?? '');
$filtered = ($search !== '' || $category !== '');

$sql    = 'SELECT p.id, p.item_code, p.item_name, p.category, p.unit, p.price, p.stock,
                  (SELECT pi.filename FROM product_images pi
                   WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS first_image
           FROM products p WHERE 1=1';
$types  = '';
$params = [];

if ($search !== '') {
    $sql .= ' AND (p.item_name LIKE ? OR p.item_code LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($category !== '' && in_array($category, $CATEGORIES, true)) {
    $sql     .= ' AND p.category = ?';
    $params[] = $category;
    $types   .= 's';
}
$sql .= ' ORDER BY p.item_code ASC';

$products = false;
$stmt = mysqli_prepare($conn, $sql);
if ($stmt !== false) {
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $products = mysqli_stmt_get_result($stmt);
}

$totalRes = mysqli_query($conn, 'SELECT COUNT(*) AS n FROM products');
$totalProducts = $totalRes ? (int) mysqli_fetch_assoc($totalRes)['n'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Schoolie — School &amp; Office Supplies</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner market-head">
        <a class="brand" href="index.php">
            <img class="brand-logo" src="uploads/logo/logo.png" alt="Schoolie logo">
            <span class="brand-name">Schoolie</span>
        </a>

        <form class="market-search" method="get" action="index.php">
            <div class="search-box">
                <input type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>"
                       placeholder="Search for products" aria-label="Search products">
                <button type="submit" aria-label="Search">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php user_menu(false); ?>
    </div>

    <nav class="trending" aria-label="Category shortcuts">
        <span class="trending-label">Categories:</span>
        <?php foreach ($CATEGORIES as $cat): ?>
            <a href="index.php?category=<?= rawurlencode($cat) ?>"><?= htmlspecialchars($cat) ?></a>
        <?php endforeach; ?>
    </nav>
</header>

<main class="market-main">
    <div class="section-band">
        <?php if ($filtered): ?>
            <h1>Search Results</h1>
            <p class="band-sub">
                &ldquo;<?= htmlspecialchars($search !== '' ? $search : $category, ENT_QUOTES) ?>&rdquo;
                &mdash; <a href="index.php">clear filter</a>
            </p>
        <?php else: ?>
            <h1>Daily Discover</h1>
            <p class="band-sub"><?= $totalProducts ?> school &amp; office essentials available</p>
        <?php endif; ?>
    </div>

    <?php if ($products !== false && mysqli_num_rows($products) > 0): ?>
        <div class="market-grid">
            <?php while ($row = mysqli_fetch_assoc($products)): ?>
                <a class="p-card" href="product.php?id=<?= (int) $row['id'] ?>">
                    <div class="p-img-wrap">
                        <?php if (upload_path($row['first_image']) !== null): ?>
                            <img class="p-img" src="<?= PRODUCT_IMG_URL ?>/<?= rawurlencode(basename($row['first_image'])) ?>"
                                 alt="<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>">
                        <?php else: ?>
                            <div class="p-img-empty">
                                <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M21 16l-4.5-4.5L9 19"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-body">
                        <p class="p-name"><?= htmlspecialchars($row['item_name']) ?></p>
                        <div class="p-meta">
                            <span class="p-price"><span class="cur">&#8369;</span><?= number_format((float) $row['price'], 2) ?></span>
                            <span class="p-stock"><?= (int) $row['stock'] ?> in stock</span>
                        </div>
                        <span class="p-tag"><?= htmlspecialchars($row['category']) ?> &middot; <?= htmlspecialchars($row['unit']) ?></span>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        <p class="market-foot">
            Showing <?= mysqli_num_rows($products) ?> of <?= $totalProducts ?> product<?= $totalProducts === 1 ? '' : 's' ?>.
        </p>
    <?php else: ?>
        <div class="market-empty">
            <div class="empty-icon">&#128269;</div>
            <?php if ($filtered): ?>
                No products match your search. <a href="index.php">Browse everything instead</a>.
            <?php else: ?>
                The market is empty. <a href="add.php">Add your first product</a>.
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php user_menu_script(); ?>

</body>
</html>
