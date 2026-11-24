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
$totalAll = $totalRes ? (int) mysqli_fetch_assoc($totalRes)['n'] : 0;

$catClass = [
    'Writing'                => 'cat-writing',
    'Paper'                  => 'cat-paper',
    'Desk Accessories'       => 'cat-desk',
    'Correction & Adhesives' => 'cat-correction',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Products — Schoolie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php render_seller_nav('products'); ?>

<?= seller_breadcrumb([['Seller Centre', 'dashboard.php'], ['Products', ''], ['Manage Products', '']]) ?>

<div class="page-head">
    <div>
        <h1>Manage Products</h1>
        <p class="page-sub">Search, edit or remove items from your inventory.</p>
    </div>
    <a class="btn btn-primary" href="add.php">+ Add New Product</a>
</div>

<?php render_flash(); ?>

<section class="panel">
    <div class="panel-head">
        <h2>Product List</h2>
        <form class="filters" method="get" action="products.php">
            <input type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>"
                   placeholder="Search item name or code&hellip;" aria-label="Search products">
            <select name="category" aria-label="Filter by category">
                <option value="">All Categories</option>
                <?php foreach ($CATEGORIES as $cat): ?>
                    <option value="<?= htmlspecialchars($cat, ENT_QUOTES) ?>"
                        <?= $category === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat, ENT_QUOTES) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a class="btn btn-ghost" href="products.php">Reset</a>
        </form>
    </div>

    <div class="table-wrap">
        <table class="stack-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($products !== false && mysqli_num_rows($products) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td class="cell-img"><?= image_cell($row['first_image'], (string) $row['item_name']) ?></td>
                        <td data-label="Item Code"><span class="code"><?= htmlspecialchars($row['item_code']) ?></span></td>
                        <td class="item-name" data-label="Item Name"><?= htmlspecialchars($row['item_name']) ?></td>
                        <td data-label="Category">
                            <span class="badge <?= $catClass[$row['category']] ?? '' ?>">
                                <?= htmlspecialchars($row['category']) ?>
                            </span>
                        </td>
                        <td data-label="Unit"><?= htmlspecialchars($row['unit']) ?></td>
                        <td class="price" data-label="Price">&#8369;<?= number_format((float) $row['price'], 2) ?></td>
                        <td data-label="Stock">
                            <span class="cell-val">
                            <?php if ((int) $row['stock'] <= LOW_STOCK_LEVEL): ?>
                                <span class="stock-low"><?= (int) $row['stock'] ?></span>
                                <span class="pill-low">Low</span>
                            <?php else: ?>
                                <?= (int) $row['stock'] ?>
                            <?php endif; ?>
                            </span>
                        </td>
                        <td class="actions cell-actions">
                            <a class="btn btn-sm btn-edit"
                               href="edit.php?id=<?= (int) $row['id'] ?>">Edit</a>
                            <a class="btn btn-sm btn-delete"
                               href="delete.php?id=<?= (int) $row['id'] ?>"
                               onclick="return confirm('Delete <?= htmlspecialchars($row['item_code'], ENT_QUOTES) ?> (<?= htmlspecialchars($row['item_name'], ENT_QUOTES) ?>)? Its image files will also be removed.');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="empty">
                        <div class="empty-icon">&#128269;</div>
                        <?php if ($filtered): ?>
                            No products match your search or filter.
                        <?php else: ?>
                            No products yet. <a href="add.php">Add your first product</a>.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-foot">
        <?php if ($products !== false && $totalAll > 0): ?>
            Showing <?= mysqli_num_rows($products) ?> of <?= $totalAll ?> product<?= $totalAll === 1 ? '' : 's' ?><?= $filtered ? ' (filtered)' : '' ?>.
        <?php else: ?>
            Inventory is empty.
        <?php endif; ?>
    </div>
</section>

<?php render_seller_footer(); ?>

</body>
</html>
