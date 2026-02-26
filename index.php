<?php
session_start();
require_once __DIR__ . '/core/bootstrap.php';

// Redirect to installer
if (!INSTALLED) {
    header('Location: ' . BASE_URL . '/install/');
    exit;
}

// Maintenance mode
if (Settings::get('maintenance_mode') && !Auth::isAdmin()) {
    http_response_code(503);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Onderhoud</title><style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;font-family:sans-serif;background:#0f172a;color:white;}div{text-align:center;}.icon{font-size:4rem;margin-bottom:1rem;}h1{font-size:2rem;}p{color:#94a3b8;}</style></head><body><div><div class="icon">🔧</div><h1>Onderhoud</h1><p>We zijn even bezig. Kom later terug!</p></div></body></html>';
    exit;
}

// Simple routing
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Strip base path if CMS is in subfolder
$basePath = trim(parse_url(BASE_URL, PHP_URL_PATH), '/');
if ($basePath && str_starts_with($uri, $basePath)) {
    $uri = trim(substr($uri, strlen($basePath)), '/');
}

// Load theme
$themePath = ThemeManager::getThemePath();
if (file_exists($themePath . '/functions.php')) {
    require_once $themePath . '/functions.php';
}

$db = Database::getInstance();

// Route matching
$parts = explode('/', $uri);
$route = $parts[0] ?? '';
$slug = $parts[1] ?? '';

function renderTemplate(string $template, array $vars = []): void {
    global $themePath, $db;
    extract($vars);
    $tpl = ThemeManager::getThemePath() . '/' . $template;
    if (file_exists($tpl)) {
        include $tpl;
    } else {
        http_response_code(404);
        echo '<h1>Template niet gevonden: ' . htmlspecialchars($template) . '</h1>';
    }
}

function themeFile(string $file): string {
    return ThemeManager::getThemePath() . '/' . $file;
}

// ===== ROUTES =====

// News listing: /news
if ($route === 'news' && empty($slug)) {
    $perPage = (int)Settings::get('posts_per_page', 10);
    $page = max(1, (int)($_GET['p'] ?? 1));
    $total = $db->fetch("SELECT COUNT(*) as c FROM `" . DB_PREFIX . "news` WHERE status = 'published'")['c'];
    $pagination = paginate($total, $perPage, $page);
    $posts = $db->fetchAll("SELECT n.*, u.username, c.name as cat_name FROM `" . DB_PREFIX . "news` n LEFT JOIN `" . DB_PREFIX . "users` u ON n.author_id = u.id LEFT JOIN `" . DB_PREFIX . "categories` c ON n.category_id = c.id WHERE n.status = 'published' ORDER BY n.published_at DESC LIMIT {$perPage} OFFSET {$pagination['offset']}");

    $metaTitle = 'Nieuws';
    $currentPage = 'news';
    include themeFile('header.php');
    include themeFile('archive.php');
    include themeFile('footer.php');
    exit;
}

// Single news post: /news/[slug]
if ($route === 'news' && $slug) {
    $post = $db->fetch("SELECT n.*, u.username FROM `" . DB_PREFIX . "news` n LEFT JOIN `" . DB_PREFIX . "users` u ON n.author_id = u.id WHERE n.slug = ? AND n.status = 'published'", [$slug]);
    if (!$post) { http_response_code(404); }
    $metaTitle = $post ? $post['meta_title'] ?: $post['title'] : 'Niet gevonden';
    $metaDesc = $post['meta_desc'] ?? '';
    $currentPage = 'news';
    include themeFile('header.php');
    include themeFile('single.php');
    include themeFile('footer.php');
    exit;
}

// Static page: /[slug] or /
if (empty($route)) {
    // Homepage — show latest news as hero
    $perPage = (int)Settings::get('posts_per_page', 10);
    $total = $db->fetch("SELECT COUNT(*) as c FROM `" . DB_PREFIX . "news` WHERE status = 'published'")['c'];
    $posts = $db->fetchAll("SELECT n.*, u.username, c.name as cat_name FROM `" . DB_PREFIX . "news` n LEFT JOIN `" . DB_PREFIX . "users` u ON n.author_id = u.id LEFT JOIN `" . DB_PREFIX . "categories` c ON n.category_id = c.id WHERE n.status = 'published' ORDER BY n.published_at DESC LIMIT 6");
    $metaTitle = Settings::get('site_tagline', '');
    $currentPage = 'home';
    include themeFile('header.php');
    include themeFile('home.php');
    include themeFile('footer.php');
    exit;
}

// Static page
$page = $db->fetch("SELECT * FROM `" . DB_PREFIX . "pages` WHERE slug = ? AND status = 'published'", [$uri]);
if ($page) {
    $metaTitle = $page['meta_title'] ?: $page['title'];
    $metaDesc = $page['meta_desc'] ?? '';
    $currentSlug = $page['slug'];
    include themeFile('header.php');
    include themeFile('page.php');
    include themeFile('footer.php');
    exit;
}

// 404
http_response_code(404);
include themeFile('header.php');
include themeFile('404.php');
include themeFile('footer.php');
