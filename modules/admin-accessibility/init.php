<?php
/**
 * Admin Toegankelijkheid – init.php
 * Laadt de accessibility toolbar op elke adminpagina.
 */

// Sidebar navigatielink
add_action('admin_sidebar_nav', function ($activePage) {
    $isActive = ($activePage ?? '') === 'admin-accessibility' ? 'active' : '';
    echo '<a href="' . BASE_URL . '/admin/admin-accessibility/" class="nav-link ' . $isActive . '">'
       . '<i class="bi bi-universal-access"></i> Toegankelijkheid</a>';
});

// Stylesheet injecteren in <head>
add_action('admin_head', function () {
    echo '<link rel="stylesheet" href="' . assetUrl('css/accessibility.css') . '">';
});

// JavaScript + widget injecteren voor </body>
add_action('admin_footer', function () {
    echo '<script src="' . assetUrl('js/accessibility.js') . '"></script>';
});
