<?php
session_start();
require_once dirname(__DIR__, 2) . '/core/bootstrap.php';

// Redirect to installer if not installed
if (!INSTALLED) {
    header('Location: ' . BASE_URL . '/install/');
    exit;
}

Auth::requireLogin();
