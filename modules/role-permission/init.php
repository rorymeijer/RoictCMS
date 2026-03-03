<?php
/**
 * Role & Permission Module — Init
 * Geladen bij elke pagina-aanvraag zolang de module actief is.
 */

require_once __DIR__ . '/functions.php';

// Voeg navigatielink toe aan het beheerpaneel
add_action('admin_sidebar_nav', function ($activePage) {
    $isActive = ($activePage ?? '') === 'role-permission' ? 'active' : '';
    echo '<a href="' . BASE_URL . '/modules/role-permission/admin/" class="nav-link ' . $isActive . '">'
       . '<i class="bi bi-shield-check"></i> Rollen & Rechten</a>';
});
