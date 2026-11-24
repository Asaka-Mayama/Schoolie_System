<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/nav.php';
require_login();

$totalsRes = mysqli_query(
    $conn,
    'SELECT COUNT(*) AS total_products,
            COALESCE(SUM(CASE WHEN stock <= ' . LOW_STOCK_LEVEL . ' THEN 1 ELSE 0 END), 0) AS low_stock,
            COALESCE(SUM(price * stock), 0) AS inventory_value
     FROM products'
);
$totals        = $totalsRes ? mysqli_fetch_assoc($totalsRes) : null;
$totalProducts = (int) ($totals['total_products'] ?? 0);
$lowStock      = (int) ($totals['low_stock'] ?? 0);
$totalValue    = (float) ($totals['inventory_value'] ?? 0);

$watch = mysqli_query(
    $conn,
    'SELECT id, item_code, item_name, category, stock
     FROM products
     WHERE stock <= ' . LOW_STOCK_LEVEL . '
     ORDER BY stock ASC, item_code ASC
     LIMIT 8'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seller Dashboard — Schoolie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php render_seller_nav('dashboard'); ?>

<?= seller_breadcrumb([['Seller Centre', 'dashboard.php'], ['Dashboard', '']]) ?>

<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p class="page-sub">Overview of your school &amp; office supplies inventory.</p>
    </div>
    <a class="btn btn-primary" href="add.php">+ Add New Product</a>
</div>

<?php render_flash(); ?>

<section class="cards">
    <div class="card">
        <span class="card-icon">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3.3 8.3L12 13l8.7-4.7"/><path d="M12 13v9"/></svg>
        </span>
        <div>
            <div class="card-label">Total Products</div>
            <div class="card-value"><?= $totalProducts ?></div>
        </div>
    </div>
    <div class="card">
        <span class="card-icon warn">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l10 18H2z"/><path d="M12 10v5"/><path d="M12 18h.01"/></svg>
        </span>
        <div>
            <div class="card-label">Low Stock (&le; <?= LOW_STOCK_LEVEL ?>)</div>
            <div class="card-value <?= $lowStock > 0 ? 'warn' : '' ?>"><?= $lowStock ?></div>
        </div>
    </div>
    <div class="card">
        <span class="card-icon money">&#8369;</span>
        <div>
            <div class="card-label">Total Inventory Value</div>
            <div class="card-value">&#8369;<?= number_format($totalValue, 2) ?></div>
        </div>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Quick Actions</h2></div>
    <div class="quick-actions">
        <a class="btn btn-primary" href="add.php">+ Add New Product</a>
        <a class="btn btn-ghost" href="products.php">Manage Products</a>
        <a class="btn btn-ghost" href="index.php">View Market</a>
    </div>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Low Stock Watchlist (&le; <?= LOW_STOCK_LEVEL ?>)</h2>
        <a class="btn btn-sm btn-ghost" href="products.php">Open Manage Products</a>
    </div>
    <div class="table-wrap">
        <table class="stack-table">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($watch && mysqli_num_rows($watch) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($watch)): ?>
                    <tr>
                        <td data-label="Item Code"><span class="code"><?= htmlspecialchars($row['item_code']) ?></span></td>
                        <td class="item-name" data-label="Item Name"><?= htmlspecialchars($row['item_name']) ?></td>
                        <td data-label="Category"><?= htmlspecialchars($row['category']) ?></td>
                        <td data-label="Stock">
                            <span class="cell-val">
                                <span class="stock-low"><?= (int) $row['stock'] ?></span>
                                <span class="pill-low">Low</span>
                            </span>
                        </td>
                        <td class="actions cell-actions">
                            <a class="btn btn-sm btn-edit" href="edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                            <a class="btn btn-sm btn-delete"
                               href="delete.php?id=<?= (int) $row['id'] ?>"
                               onclick="return confirm('Delete <?= htmlspecialchars($row['item_code'], ENT_QUOTES) ?> (<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>)? Its image file will also be removed.');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="empty">
                        <div class="empty-icon">&#127881;</div>
                        All stocked up &mdash; no low-stock items right now.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php render_seller_footer(); ?>

</body>
</html>
