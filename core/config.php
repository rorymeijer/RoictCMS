<?php
// CMS Core Configuration
define('CMS_VERSION', '1.0.0');
define('CMS_NAME', 'ROICT CMS');
define('BASE_PATH', dirname(__DIR__));

// Calculate BASE_URL from document root, not from current script path.
// This ensures BASE_URL is always the CMS root regardless of which script is running.
(function() {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $cmsRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/');
    $relativePath = '';
    if ($docRoot !== '' && str_starts_with($cmsRoot, $docRoot)) {
        $relativePath = substr($cmsRoot, strlen($docRoot));
    }
    $relativePath = rtrim(str_replace('\\', '/', $relativePath), '/');
    define('BASE_URL', $scheme . '://' . $host . $relativePath);
})();

// Config file path
define('CONFIG_FILE', BASE_PATH . '/config.php');
define('INSTALLED', file_exists(CONFIG_FILE));

// Load config if installed
if (INSTALLED) {
    require_once CONFIG_FILE;
} else {
    define('DB_HOST', '');
    define('DB_NAME', '');
    define('DB_USER', '');
    define('DB_PASS', '');
    define('DB_PREFIX', 'cms_');
}

define('THEMES_PATH', BASE_PATH . '/themes');
define('MODULES_PATH', BASE_PATH . '/modules');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('ADMIN_PATH', BASE_PATH . '/admin');

// Timezone
date_default_timezone_set('Europe/Amsterdam');
