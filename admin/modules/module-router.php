<?php
/**
 * Module Admin Router
 *
 * Handles requests to /admin/modules/{slug}/ by including the module's
 * own admin/index.php file. Called via the .htaccess rewrite rule.
 */

$slug = preg_replace('/[^a-z0-9\-]/', '', $_GET['slug'] ?? '');

if (empty($slug)) {
    header('Location: ' . dirname($_SERVER['SCRIPT_NAME']));
    exit;
}

$modulePath = dirname(__DIR__, 2) . '/modules/' . $slug . '/admin/index.php';

if (file_exists($modulePath)) {
    require $modulePath;
} else {
    require_once dirname(__DIR__) . '/includes/init.php';
    Auth::requireAdmin();
    $pageTitle = 'Niet gevonden';
    $activePage = 'modules';
    require_once dirname(__DIR__) . '/includes/header.php';
    echo '<div class="cms-card"><div class="cms-card-body text-center py-5">'
       . '<i class="bi bi-puzzle" style="font-size:3rem;display:block;opacity:.3;margin-bottom:1rem;"></i>'
       . '<h5>Admin pagina niet gevonden</h5>'
       . '<p class="text-muted">De module <code>' . htmlspecialchars($slug) . '</code> heeft geen admin pagina.</p>'
       . '<a href="' . BASE_URL . '/admin/modules/" class="btn btn-secondary">Terug naar modules</a>'
       . '</div></div>';
    require_once dirname(__DIR__) . '/includes/footer.php';
}
