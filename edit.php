<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/nav.php';
require_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT id, item_code, item_name, description, category, unit, price, stock
     FROM products WHERE id = ?'
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    header('Location: products.php');
    exit;
}

$images = product_images($id);

// Item code is the primary identifier of every product: it is assigned once
// when the product is added and can never be changed or duplicated here.
$itemCode = (string) $product['item_code'];

$errors = [];
$old = [
    'item_name'   => (string) $product['item_name'],
    'description' => (string) $product['description'],
    'category'    => (string) $product['category'],
    'unit'        => (string) $product['unit'],
    'price'       => (string) $product['price'],
    'stock'       => (string) $product['stock'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($old) as $field) {
        $old[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    $itemName    = $old['item_name'];
    $description = $old['description'];
    $category    = $old['category'];
    $unit        = $old['unit'];
    $price       = $old['price'];
    $stock       = $old['stock'];

    if ($itemName === '') {
        $errors[] = 'Item name is required.';
    } elseif (mb_strlen($itemName) > 100) {
        $errors[] = 'Item name must be 100 characters or fewer.';
    }

    if ($description === '') {
        $errors[] = 'Description is required.';
    } elseif (mb_strlen($description) > 1000) {
        $errors[] = 'Description must be 1000 characters or fewer.';
    }

    if (!in_array($category, $CATEGORIES, true)) {
        $errors[] = 'Please choose a valid category.';
    }
    if (!in_array($unit, $UNITS, true)) {
        $errors[] = 'Please choose a valid unit.';
    }

    if ($price === '' || !is_numeric($price) || (float) $price < 0) {
        $errors[] = 'Enter a valid price (0 or greater).';
    }
    if ($stock === '' || !ctype_digit($stock)) {
        $errors[] = 'Enter a valid whole-number stock quantity (0 or greater).';
    }

    $removeList = [];
    if (!$errors) {
        foreach ((array) ($_POST['remove_images'] ?? []) as $remove) {
            $remove = (string) $remove;
            if (in_array($remove, $images, true) && !in_array($remove, $removeList, true)) {
                $removeList[] = $remove;
            }
        }
    }

    $newImages = [];
    if (!$errors) {
        $newImages = collect_uploaded_images($_FILES['images'] ?? null, $errors);
        $totalAfter = count($images) - count($removeList) + count($newImages);
        if (!$errors && $totalAfter > MAX_PRODUCT_IMAGES) {
            $errors[] = 'You can keep up to ' . MAX_PRODUCT_IMAGES . ' images per product.';
        }
        if ($errors) {
            discard_uploaded_images($newImages);
        }
    }

    if (!$errors) {
        $stmt = mysqli_prepare(
            $conn,
            'UPDATE products
             SET item_name = ?, description = ?, category = ?, unit = ?, price = ?, stock = ?
             WHERE id = ?'
        );
        $priceVal = (float) $price;
        $stockVal = (int) $stock;
        mysqli_stmt_bind_param($stmt, 'ssssdii', $itemName, $description, $category, $unit, $priceVal, $stockVal, $id);
        mysqli_stmt_execute($stmt);

        foreach ($removeList as $remove) {
            delete_product_image($id, $remove);
        }
        insert_product_images($id, $newImages);

        header('Location: products.php?updated=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit <?= htmlspecialchars($itemCode) ?> — Schoolie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php render_seller_nav('products'); ?>

<?= seller_breadcrumb([['Seller Centre', 'dashboard.php'], ['Products', 'products.php'], ['Edit: ' . $itemCode, '']]) ?>

<div class="page-head">
    <div>
        <h1>Edit Product</h1>
        <p class="page-sub">Updating <strong><?= htmlspecialchars($itemCode) ?></strong>
            &mdash; <?= htmlspecialchars($old['item_name']) ?></p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error" role="alert">
        <strong>Please fix the following:</strong>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="form-wrap">
    <form class="form-card" method="post" action="edit.php" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
        <div class="form-card-head">Product Information</div>

        <div class="form-grid">
            <div class="field">
                <label for="category">Category <span class="req">*</span></label>
                <select id="category" name="category" required>
                    <option value="">&mdash; Select category &mdash;</option>
                    <?php foreach ($CATEGORIES as $cat): ?>
                        <option value="<?= htmlspecialchars($cat, ENT_QUOTES) ?>"
                            <?= $old['category'] === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat, ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field span-2">
                <label for="item_name">Item Name <span class="req">*</span></label>
                <input type="text" id="item_name" name="item_name" maxlength="100"
                       value="<?= htmlspecialchars($old['item_name'], ENT_QUOTES) ?>" required>
            </div>

            <div class="field">
                <label for="unit">Unit <span class="req">*</span></label>
                <select id="unit" name="unit" required>
                    <option value="">&mdash; Select unit &mdash;</option>
                    <?php foreach ($UNITS as $u): ?>
                        <option value="<?= htmlspecialchars($u, ENT_QUOTES) ?>"
                            <?= $old['unit'] === $u ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u, ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="price">Price (&#8369;) <span class="req">*</span></label>
                <input type="number" id="price" name="price" min="0" step="0.01"
                       value="<?= htmlspecialchars($old['price'], ENT_QUOTES) ?>" required>
            </div>

            <div class="field">
                <label for="stock">Stock Quantity <span class="req">*</span></label>
                <input type="number" id="stock" name="stock" min="0" step="1"
                       value="<?= htmlspecialchars($old['stock'], ENT_QUOTES) ?>" required>
            </div>

            <div class="field span-2">
                <label for="description">Description <span class="req">*</span></label>
                <textarea id="description" name="description" rows="4" maxlength="1000" required><?= htmlspecialchars($old['description'], ENT_QUOTES) ?></textarea>
            </div>

            <div class="field span-2">
                <label>Product Images</label>
                <?php if ($images): ?>
                    <div class="edit-images">
                        <?php foreach ($images as $img): ?>
                            <label class="edit-image-item">
                                <img src="<?= PRODUCT_IMG_URL ?>/<?= rawurlencode(basename($img)) ?>" alt="">
                                <span class="edit-image-remove">
                                    <input type="checkbox" name="remove_images[]" value="<?= htmlspecialchars($img, ENT_QUOTES) ?>"> Remove
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="help-text">No images yet.</p>
                <?php endif; ?>
                <input type="file" id="images" name="images[]" accept="image/*" multiple>
                <p class="help-text">Tick Remove to delete an image. You can keep up to 4 images per product.</p>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a class="btn btn-ghost" href="products.php">Cancel</a>
        </div>
    </form>
</div>

<?php render_seller_footer(); ?>

<script>
document.getElementById("images").addEventListener("change", function () {
    if (this.files.length > 4) {
        alert("You can upload up to 4 images.");
        this.value = "";
    }
});
</script>

</body>
</html>
