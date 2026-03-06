<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/AdminI18n.php';
require_once __DIR__ . '/ModuleManager.php';
require_once __DIR__ . '/ThemeManager.php';
require_once __DIR__ . '/Updater.php';

// ── Hook systeem (altijd beschikbaar, ook in admin en modules) ────────────
$GLOBALS['_cms_actions'] = [];

function add_action(string $hook, callable $callback, int $priority = 10): void {
    $GLOBALS['_cms_actions'][$hook][$priority][] = $callback;
}

function do_action(string $hook, ...$args): void {
    $hooks = $GLOBALS['_cms_actions'][$hook] ?? [];
    ksort($hooks);
    foreach ($hooks as $callbacks) {
        foreach ($callbacks as $cb) {
            $cb(...$args);
        }
    }
}

// ── Filter systeem ────────────────────────────────────────────────────────
$GLOBALS['_cms_filters'] = [];

function add_filter(string $hook, callable $callback, int $priority = 10): void {
    $GLOBALS['_cms_filters'][$hook][$priority][] = $callback;
}

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
    $hooks = $GLOBALS['_cms_filters'][$hook] ?? [];
    ksort($hooks);
    foreach ($hooks as $callbacks) {
        foreach ($callbacks as $cb) {
            $value = $cb($value, ...$args);
        }
    }
    return $value;
}

// ── Shortcode systeem ─────────────────────────────────────────────────────
$GLOBALS['_cms_shortcodes'] = [];

function add_shortcode(string $tag, callable $callback): void {
    $GLOBALS['_cms_shortcodes'][$tag] = $callback;
}

function shortcode_parse_atts(string $text): array {
    $atts = [];
    $text = trim($text);
    if ($text === '') return $atts;
    $pattern = '/(\w+)=["\']([^"\']*)["\']|(\w+)=(\S+)|(\S+)/';
    preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);
    $i = 0;
    foreach ($matches as $match) {
        if (!empty($match[1])) {
            $atts[$match[1]] = $match[2];   // key="value" of key='value'
        } elseif (!empty($match[3])) {
            $atts[$match[3]] = $match[4];   // key=value
        } elseif (!empty($match[5])) {
            $atts[$i++] = $match[5];        // losse waarde → positional
        }
    }
    return $atts;
}

function do_shortcode(string $content): string {
    if (empty($GLOBALS['_cms_shortcodes'])) {
        return $content;
    }
    return preg_replace_callback(
        '/\[([a-zA-Z_][a-zA-Z0-9_-]*)([^\]]*)\]/',
        function (array $matches): string {
            $tag = $matches[1];
            if (isset($GLOBALS['_cms_shortcodes'][$tag])) {
                $args = shortcode_parse_atts($matches[2]);
                return (string) call_user_func($GLOBALS['_cms_shortcodes'][$tag], $args);
            }
            return $matches[0];
        },
        $content
    ) ?? $content;
}








// Init services that need DB
if (INSTALLED) {
    Auth::init();
    Settings::init();
    apply_site_locale();
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
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    return $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Sanitize helpers
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
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
