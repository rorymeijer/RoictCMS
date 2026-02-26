<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/ModuleManager.php';
require_once __DIR__ . '/ThemeManager.php';
require_once __DIR__ . '/Updater.php';

// Init services that need DB
if (INSTALLED) {
    Auth::init();
    Settings::init();
    ModuleManager::init();
    ModuleManager::bootModules();
}

// CSRF helpers
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_verify(): bool {
    return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token']);
}

// Sanitize helpers
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function slug(string $str): string {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $str), '-'));
}

// Flash messages
function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $icons = ['success' => 'check-circle', 'error' => 'x-circle', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
    $icon = $icons[$flash['type']] ?? 'info-circle';
    return '<sl-alert variant="' . $flash['type'] . '" open closable class="mb-4"><sl-icon name="' . $icon . '" slot="icon"></sl-icon>' . e($flash['message']) . '</sl-alert>';
}

// Pagination helper
function paginate(int $total, int $perPage, int $page): array {
    $totalPages = max(1, ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    return ['total' => $total, 'per_page' => $perPage, 'current_page' => $page, 'total_pages' => $totalPages, 'offset' => $offset];
}

// Redirect
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function assetUrl(string $path): string {
    return BASE_URL . '/admin/assets/' . ltrim($path, '/');
}

function themeAssetUrl(string $path): string {
    return BASE_URL . '/themes/' . ThemeManager::getActive() . '/assets/' . ltrim($path, '/');
}
