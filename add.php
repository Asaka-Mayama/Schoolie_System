<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/nav.php';
require_login();

$errors = [];
$old = ['item_name' => '', 'description' => '', 'category' => '', 'unit' => '', 'price' => '', 'stock' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($old) as $field) {
        $old[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    $itemName = $old['item_name'];
    $description = $old['description'];
    $category = $old['category'];
    $unit     = $old['unit'];
    $price    = $old['price'];
    $stock    = $old['stock'];

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
    } elseif (!in_array($unit, $CATEGORY_UNITS[$category] ?? [], true)) {
        $errors[] = 'Please choose a valid unit for ' . $category . '.';
    }

    if ($price === '' || !is_numeric($price) || (float) $price < 0) {
        $errors[] = 'Enter a valid price (0 or greater).';
    }
    if ($stock === '' || !ctype_digit($stock)) {
        $errors[] = 'Enter a valid whole-number stock quantity (0 or greater).';
    }

    $imageFiles = [];
    if (!$errors) {
        $imageFiles = collect_uploaded_images($_FILES['images'] ?? null, $errors);
        if ($errors) {
            discard_uploaded_images($imageFiles);
        }
    }

    if (!$errors) {
        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO products (item_code, item_name, description, category, unit, price, stock)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $priceVal  = (float) $price;
        $stockVal  = (int) $stock;
        $itemCode  = '';
        mysqli_stmt_bind_param($stmt, 'sssssdi', $itemCode, $itemName, $description, $category, $unit, $priceVal, $stockVal);
        mysqli_stmt_execute($stmt);

        $newId = mysqli_insert_id($conn);
        $itemCode = 'SUP-' . str_pad((string) $newId, 3, '0', STR_PAD_LEFT);
        $upd = mysqli_prepare($conn, 'UPDATE products SET item_code = ? WHERE id = ?');
        mysqli_stmt_bind_param($upd, 'si', $itemCode, $newId);
        mysqli_stmt_execute($upd);

        insert_product_images($newId, $imageFiles);

        header('Location: products.php?added=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add New Product — Schoolie</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php render_seller_nav('add'); ?>

<?= seller_breadcrumb([['Seller Centre', 'dashboard.php'], ['Products', 'products.php'], ['Add New Product', '']]) ?>

<div class="page-head">
    <div>
        <h1>Add New Product</h1>
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
    <form class="form-card" method="post" action="add.php" enctype="multipart/form-data" novalidate>
        <div class="form-card-head">Product Information</div>

        <div class="form-grid">
            <div class="field">
                <label for="item_name">Item Name <span class="req">*</span></label>
                <input type="text" id="item_name" name="item_name" maxlength="100"
                       value="<?= htmlspecialchars($old['item_name'], ENT_QUOTES) ?>" required>
            </div>

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

            <div class="field">
                <label for="unit">Unit <span class="req">*</span></label>
                <?php $units = $CATEGORY_UNITS[$old['category']] ?? []; ?>
                <select id="unit" name="unit" required <?= $units ? '' : 'disabled' ?>>
                    <?php if (!$units): ?>
                        <option value="">&mdash; Select category first &mdash;</option>
                    <?php else: ?>
                        <option value="">&mdash; Select unit &mdash;</option>
                        <?php foreach ($units as $u): ?>
                            <option value="<?= htmlspecialchars($u, ENT_QUOTES) ?>"
                                <?= $old['unit'] === $u ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u, ENT_QUOTES) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <p class="help-text">Choose a category first &mdash; the unit list depends on it.</p>
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
                <label for="images">Product Images</label>
                <input type="file" id="images" name="images[]" accept="image/*" multiple>
                <p class="help-text">Up to 4 images per product.</p>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Product</button>
            <a class="btn btn-ghost" href="products.php">Cancel</a>
        </div>
    </form>
</div>

<?php render_seller_footer(); ?>

<script>
var categoryUnits = <?= json_encode($CATEGORY_UNITS) ?>;
var categorySelect = document.getElementById("category");
var unitSelect = document.getElementById("unit");

function updateUnits() {
    var units = categoryUnits[categorySelect.value] || [];
    unitSelect.innerHTML = "";
    if (units.length === 0) {
        unitSelect.disabled = true;
        unitSelect.add(new Option("— Select category first —", ""));
    } else {
        unitSelect.disabled = false;
        unitSelect.add(new Option("— Select unit —", ""));
        for (var i = 0; i < units.length; i++) {
            unitSelect.add(new Option(units[i], units[i]));
        }
    }
}

categorySelect.addEventListener("change", updateUnits);

document.getElementById("images").addEventListener("change", function () {
    if (this.files.length > 4) {
        alert("You can upload up to 4 images.");
        this.value = "";
    }
});
</script>

</body>
</html>
