<?php

require_once __DIR__ . '/config.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$images = product_images($id);

$del = mysqli_prepare($conn, 'DELETE FROM products WHERE id = ?');
mysqli_stmt_bind_param($del, 'i', $id);
mysqli_stmt_execute($del);

foreach ($images as $filename) {
    $path = upload_path($filename);
    if ($path !== null) {
        @unlink($path);
    }
}

header('Location: products.php?deleted=1');
exit;
