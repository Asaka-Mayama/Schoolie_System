<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Schoolie_db');

try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (Throwable $e) {
    $conn = false;
}

if (!$conn) {
    http_response_code(500);
    die(
        '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Database Error — Schoolie</title>'
        . '<style>body{font-family:system-ui,-apple-system,"Segoe UI",Arial,sans-serif;background:#f1f5f9;'
        . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;color:#0f172a}'
        . '.box{background:#fff;border-radius:14px;padding:2rem 2.25rem;box-shadow:0 10px 30px rgba(2,6,23,.08);'
        . 'max-width:540px;margin:1rem}h1{font-size:1.15rem;color:#dc2626;margin:0 0 .75rem}'
        . 'p{color:#475569;line-height:1.6;margin:.3rem 0}code{background:#f1f5f9;padding:.15rem .45rem;'
        . 'border-radius:6px;font-size:.9em}</style></head><body>'
        . '<div class="box"><h1>&#9888; Database connection failed</h1>'
        . '<p>Could not connect to MySQL database <code>' . DB_NAME . '</code>.</p>'
        . '<p>Checklist: start <strong>MySQL</strong> in the XAMPP Control Panel, then import '
        . '<code>database.sql</code> via phpMyAdmin (<code>http://localhost/phpmyadmin</code> &rarr; Import).</p>'
        . '</div></body></html>'
    );
}

mysqli_set_charset($conn, 'utf8mb4');

$CATEGORIES = ['Writing', 'Paper', 'Desk Accessories', 'Correction & Adhesives'];
$UNITS      = ['piece', 'box', 'ream', 'pad', 'pack'];

$CATEGORY_UNITS = [
    'Writing'                => ['piece', 'box', 'pack'],
    'Paper'                  => ['ream', 'pad', 'piece', 'pack'],
    'Desk Accessories'       => ['piece', 'box', 'pack'],
    'Correction & Adhesives' => ['piece', 'box', 'pack'],
];

define('LOW_STOCK_LEVEL', 20);

// Product images are stored in their own folder inside uploads/.
define('PRODUCT_IMG_DIR', __DIR__ . '/uploads/All Uploads');
define('PRODUCT_IMG_URL', 'uploads/All%20Uploads');

function is_logged_in(): bool
{
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: admin.php');
        exit;
    }
}

function handle_image_upload(?array $file, ?string &$error = null): ?string
{
    $error = null;

    if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Image upload failed (error code ' . (int) $file['error'] . '). Please try again.';
        return null;
    }
    if ($file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
        $error = 'Image must be between 1 byte and 5 MB.';
        return null;
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !isset($allowed[$info['mime']])) {
        $error = 'Only JPG, PNG, GIF or WebP image files are allowed.';
        return null;
    }

    $dir = PRODUCT_IMG_DIR;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $base = pathinfo($file['name'], PATHINFO_FILENAME);
    $base = trim(preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $base), '_');
    if ($base === '') {
        $base = 'product';
    }
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $base . '.' . $allowed[$info['mime']];

    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        $error = 'Could not save the uploaded image. Check the permissions of the uploads/All Uploads/ folder.';
        return null;
    }
    return $filename;
}

function upload_path(?string $filename): ?string
{
    if ($filename === null || $filename === '') {
        return null;
    }
    $path = PRODUCT_IMG_DIR . '/' . basename($filename);
    return is_file($path) ? $path : null;
}

function image_cell(?string $filename, string $itemName): string
{
    if (upload_path($filename) !== null) {
        $src = PRODUCT_IMG_URL . '/' . rawurlencode(basename($filename));
        return '<img class="thumb" src="' . $src . '" alt="'
            . htmlspecialchars($itemName, ENT_QUOTES) . '">';
    }
    return '<div class="thumb thumb-empty" title="No image">'
        . '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor"'
        . ' stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/>'
        . '<path d="M21 16l-4.5-4.5L9 19"/></svg></div>';
}

define('MAX_PRODUCT_IMAGES', 4);

function product_images(int $productId): array
{
    global $conn;
    $res = mysqli_query($conn, 'SELECT filename FROM product_images WHERE product_id = ' . $productId . ' ORDER BY id ASC');
    $out = [];
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        $out[] = (string) $row['filename'];
    }
    return $out;
}

function insert_product_images(int $productId, array $filenames): void
{
    global $conn;
    $stmt = mysqli_prepare($conn, 'INSERT INTO product_images (product_id, filename) VALUES (?, ?)');
    mysqli_stmt_bind_param($stmt, 'is', $productId, $filename);
    foreach ($filenames as $filename) {
        mysqli_stmt_execute($stmt);
    }
}

function delete_product_image(int $productId, string $filename): void
{
    global $conn;
    $stmt = mysqli_prepare($conn, 'DELETE FROM product_images WHERE product_id = ? AND filename = ?');
    mysqli_stmt_bind_param($stmt, 'is', $productId, $filename);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $path = upload_path($filename);
        if ($path !== null) {
            @unlink($path);
        }
    }
}

function collect_uploaded_images(?array $input, array &$errors): array
{
    $saved = [];
    if ($input === null || !isset($input['name']) || !is_array($input['name'])) {
        return $saved;
    }
    $picked = [];
    for ($i = 0; $i < count($input['name']); $i++) {
        if ((string) ($input['name'][$i] ?? '') === '' && (int) ($input['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $picked[] = [
            'name'     => $input['name'][$i],
            'type'     => $input['type'][$i] ?? '',
            'tmp_name' => $input['tmp_name'][$i] ?? '',
            'error'    => $input['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $input['size'][$i] ?? 0,
        ];
    }
    if (!$picked) {
        return $saved;
    }
    if (count($picked) > MAX_PRODUCT_IMAGES) {
        $errors[] = 'You can upload up to ' . MAX_PRODUCT_IMAGES . ' images per product.';
        return $saved;
    }
    foreach ($picked as $file) {
        $imgError = null;
        $filename = handle_image_upload($file, $imgError);
        if ($imgError !== null) {
            $errors[] = $imgError;
        } elseif ($filename !== null) {
            $saved[] = $filename;
        }
    }
    return $saved;
}

function discard_uploaded_images(array $filenames): void
{
    foreach ($filenames as $filename) {
        $path = upload_path($filename);
        if ($path !== null) {
            @unlink($path);
        }
    }
}
