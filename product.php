<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/nav.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT id, item_code, item_name, description, category, unit, price, stock FROM products WHERE id = ?'
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    header('Location: index.php');
    exit;
}

$images = product_images($id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($product['item_name']) ?> — Schoolie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php render_product_nav(); ?>

<?= seller_breadcrumb([
    ['Market', 'index.php'],
    [(string) $product['category'], 'index.php?category=' . rawurlencode((string) $product['category'])],
    [(string) $product['item_name'], ''],
]) ?>

<div class="product-wrap">
    <div class="product-gallery">
        <div class="p-main">
            <?php if ($images && upload_path($images[0]) !== null): ?>
                <img id="main-img" src="<?= PRODUCT_IMG_URL ?>/<?= rawurlencode(basename($images[0])) ?>"
                     alt="<?= htmlspecialchars($product['item_name'], ENT_QUOTES) ?>">
            <?php else: ?>
                <div class="p-img-empty big">
                    <svg viewBox="0 0 24 24" width="46" height="46" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M21 16l-4.5-4.5L9 19"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
            <div class="p-thumbs">
                <?php foreach ($images as $i => $img): ?>
                    <button type="button" class="p-thumb<?= $i === 0 ? ' active' : '' ?>"
                            data-src="<?= PRODUCT_IMG_URL ?>/<?= rawurlencode(basename($img)) ?>">
                        <img src="<?= PRODUCT_IMG_URL ?>/<?= rawurlencode(basename($img)) ?>" alt="">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="product-side">
        <div class="product-info">
            <h1 class="product-title"><?= htmlspecialchars($product['item_name']) ?></h1>
            <div class="product-tags">
                <span class="badge"><?= htmlspecialchars($product['category']) ?></span>
                <span class="p-stock"><?= (int) $product['stock'] ?> in stock</span>
            </div>
            <div class="product-price-box">
                <span class="product-price"><span class="cur">&#8369;</span><?= number_format((float) $product['price'], 2) ?></span>
            </div>
            <div class="product-specs">
                <div class="spec-row"><span class="spec-label">Category</span><span><?= htmlspecialchars($product['category']) ?></span></div>
                <div class="spec-row"><span class="spec-label">Unit</span><span><?= htmlspecialchars($product['unit']) ?></span></div>
                <div class="spec-row"><span class="spec-label">Stock</span><span><?= (int) $product['stock'] ?> available</span></div>
            </div>
        </div>

        <div class="product-actions">
            <a class="action-card" href="products.php">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3.3 8.3L12 13l8.7-4.7"/><path d="M12 13v9"/></svg>
                <span>Manage Products</span>
            </a>
            <a class="action-card action-edit" href="edit.php?id=<?= (int) $product['id'] ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                <span>Edit Product</span>
            </a>
        </div>
    </div>
</div>

<section class="panel desc-panel">
    <div class="panel-head"><h2>Product Description</h2></div>
    <div class="product-desc"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
</section>

<?php render_product_footer(); ?>

<script>
var mainImg = document.getElementById("main-img");
var thumbs = document.querySelectorAll(".p-thumb");
for (var i = 0; i < thumbs.length; i++) {
    thumbs[i].addEventListener("click", function () {
        if (!mainImg) { return; }
        mainImg.src = this.getAttribute("data-src");
        for (var j = 0; j < thumbs.length; j++) { thumbs[j].classList.remove("active"); }
        this.classList.add("active");
    });
}
</script>

</body>
</html>
