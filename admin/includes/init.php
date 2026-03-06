<?php
session_start();
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

// Redirect to installer if not installed
if (!INSTALLED) {
    header('Location: ' . BASE_URL . '/install/');
    exit;
}

Auth::requireLogin();

if (!headers_sent() && ob_get_level() === 0) {
    ob_start('admin_translate_html');
}

// Lid-rol heeft standaard alleen toegang tot de front end.
// Alleen als een custom rol het overschrijft mag een lid het beheergedeelte in.
if (($_SESSION['user_role'] ?? '') === 'lid' && !Auth::canAccessBackend()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}
