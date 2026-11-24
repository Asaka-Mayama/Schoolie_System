<?php

require_once __DIR__ . '/config.php';

function current_admin(): string
{
    return $_SESSION['admin_username'] ?? 'admin';
}

function user_menu(bool $showViewMarket = false): void
{
    $user = htmlspecialchars(current_admin(), ENT_QUOTES);
    echo '<div class="user-menu">';
    echo '<button type="button" class="user-menu-btn" aria-haspopup="true" aria-expanded="false">';
    echo  '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>';
    echo  '<span>' . $user . '</span>';
    echo  '<svg class="caret" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
    echo '</button>';
    echo '<nav class="dropdown-menu" aria-label="Account menu">';
    echo  '<a href="dashboard.php"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>My Dashboard</a>';
    echo  '<a href="products.php"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3.3 8.3L12 13l8.7-4.7"/><path d="M12 13v9"/></svg>Manage Products</a>';
    echo  '<a href="add.php"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/><path d="M8 11h6M11 8v6"/></svg>Add New Product</a>';
    if ($showViewMarket) {
        echo '<a href="index.php"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 7h12l1.5 13.5a1 1 0 0 1-1 1.1H5.5a1 1 0 0 1-1-1.1z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/></svg>View Market</a>';
    }
    echo  '<a class="dd-logout" href="logout.php"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>Logout</a>';
    echo '</nav>';
    echo '</div>';
}

function user_menu_script(): void
{
    echo '<script>
    document.querySelectorAll(".user-menu-btn").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.stopPropagation();
            var open = btn.parentElement.classList.toggle("open");
            btn.setAttribute("aria-expanded", open ? "true" : "false");
        });
    });
    document.addEventListener("click", function () {
        document.querySelectorAll(".user-menu.open").forEach(function (m) {
            m.classList.remove("open");
            var b = m.querySelector(".user-menu-btn");
            if (b) { b.setAttribute("aria-expanded", "false"); }
        });
    });
    </script>';
}

function seller_menu_items(): array
{
    return [
        'dashboard' => ['Dashboard', 'dashboard.php',
            '<path d="M3 3h8v8H3z"/><path d="M13 3h8v5h-8z"/><path d="M13 12h8v9h-8z"/><path d="M3 15h8v6H3z"/>'],
        'products'  => ['Manage Products', 'products.php',
            '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3.3 8.3L12 13l8.7-4.7"/><path d="M12 13v9"/>'],
        'add'       => ['Add New Product', 'add.php',
            '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/><path d="M8 11h6M11 8v6"/>'],
    ];
}

function render_seller_nav(string $active = ''): void
{
    echo '<header class="topbar topbar-seller">';
    echo '<div class="topbar-inner">';
    echo   '<a class="brand" href="index.php">';
    echo     '<img class="brand-logo" src="uploads/logo/logo.png" alt="Schoolie logo">';
    echo     '<span class="brand-name">Schoolie</span>';
    echo     '<span class="brand-divider"></span><span class="brand-sub">Seller Centre</span>';
    echo   '</a>';
    echo   '<nav class="topbar-actions">';
    echo     '<a class="topbar-link" href="index.php">&larr; Back to Market</a>';
    user_menu(true);
    echo   '</nav>';
    echo '</div>';
    echo '</header>';

    echo '<div class="layout">';
    echo '<aside class="sidebar"><nav class="side-nav">';
    foreach (seller_menu_items() as $key => [$label, $href, $path]) {
        $cls = 'side-item' . ($active === $key ? ' active' : '');
        echo '<a class="' . $cls . '" href="' . $href . '">'
           . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>'
           . '<span>' . $label . '</span></a>';
    }
    echo '</nav></aside>';

    echo '<main class="content">';
    user_menu_script();
}

function render_seller_footer(): void
{
    echo '</main></div>';
}

function render_product_nav(): void
{
    echo '<header class="topbar topbar-seller">';
    echo '<div class="topbar-inner">';
    echo   '<a class="brand" href="index.php">';
    echo     '<img class="brand-logo" src="uploads/logo/logo.png" alt="Schoolie logo">';
    echo     '<span class="brand-name">Schoolie</span>';
    echo   '</a>';
    echo   '<nav class="topbar-actions">';
    echo     '<a class="topbar-link" href="index.php">&larr; Back to Market</a>';
    user_menu(true);
    echo   '</nav>';
    echo '</div>';
    echo '</header>';

    echo '<main class="product-page">';
    user_menu_script();
}

function render_product_footer(): void
{
    echo '</main>';
}

function seller_breadcrumb(array $trail): void
{
    echo '<nav class="crumbs">';
    $last = count($trail) - 1;
    foreach ($trail as $i => [$label, $href]) {
        if ($i === $last) {
            echo '<span class="crumb-here">' . htmlspecialchars($label) . '</span>';
        } else {
            echo '<a class="crumb-link" href="' . $href . '">' . htmlspecialchars($label) . '</a><span class="crumb-sep">&rsaquo;</span>';
        }
    }
    echo '</nav>';
}

function flash_message(): string
{
    if (isset($_GET['added'])) {
        return 'Product added successfully.';
    }
    if (isset($_GET['updated'])) {
        return 'Product updated successfully.';
    }
    if (isset($_GET['deleted'])) {
        return 'Product deleted.';
    }
    return '';
}

function render_flash(): void
{
    $msg = flash_message();
    if ($msg !== '') {
        echo '<div class="alert alert-success" role="status">' . htmlspecialchars($msg) . '</div>';
    }
}
