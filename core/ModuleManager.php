<?php
class ModuleManager {
    private static $db;
    private static $marketplaceUrl = 'https://raw.githubusercontent.com/rorymeijer/roictcms_modules/main/marketplace.json';

    public static function init(): void {
        self::$db = Database::getInstance();
    }

    // Boot alle actieve modules (laad hun init.php)
    public static function bootModules(): void {
        // Fix modules met NULL versie door module.json te lezen
        $nullVersions = self::$db->fetchAll(
            "SELECT slug FROM `" . DB_PREFIX . "modules` WHERE version IS NULL OR version = ''"
        );
        foreach ($nullVersions as $m) {
            $jsonFile = MODULES_PATH . '/' . $m['slug'] . '/module.json';
            if (file_exists($jsonFile)) {
                $info = json_decode(file_get_contents($jsonFile), true) ?? [];
                if (!empty($info['version'])) {
                    self::$db->update(DB_PREFIX . 'modules', ['version' => $info['version']], 'slug = ?', [$m['slug']]);
                }
            }
        }

        $modules = self::$db->fetchAll(
            "SELECT slug FROM `" . DB_PREFIX . "modules` WHERE status = 'active'"
        );
        foreach ($modules as $m) {
            $initFile = MODULES_PATH . '/' . $m['slug'] . '/init.php';
            if (file_exists($initFile)) {
                require_once $initFile;
            }
        }
    }

    public static function getInstalled(): array {
        return self::$db->fetchAll("SELECT * FROM `" . DB_PREFIX . "modules` ORDER BY name");
    }

    public static function isInstalled(string $slug): bool {
        $row = self::$db->fetch("SELECT id FROM `" . DB_PREFIX . "modules` WHERE slug = ?", [$slug]);
        return $row !== null;
    }

    public static function isActive(string $slug): bool {
        $row = self::$db->fetch("SELECT status FROM `" . DB_PREFIX . "modules` WHERE slug = ?", [$slug]);
        return $row && $row['status'] === 'active';
    }

    public static function getMarketplace(): array {
        // Primaire bron: GitHub (altijd actueel)
        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 8,
                'user_agent' => 'ROICT-CMS/' . CMS_VERSION,
            ],
        ]);
        $data = @file_get_contents(self::$marketplaceUrl, false, $ctx);
        if ($data) {
            $parsed = json_decode($data, true);
            if (!empty($parsed['modules']) || !empty($parsed['themes'])) {
                return $parsed;
            }
        }

        // Fallback: lokale marketplace.json (offline / GitHub onbereikbaar)
        $localFile = BASE_PATH . '/api/marketplace.json';
        if (file_exists($localFile)) {
            $parsed = json_decode(file_get_contents($localFile), true) ?? [];
            if (!empty($parsed['modules']) || !empty($parsed['themes'])) {
                return $parsed;
            }
        }

        // Laatste redmiddel: hardcoded mock
        return self::getMockMarketplace();
    }

    private static function getMockMarketplace(): array {
        return [
            'modules' => [
                [
                    'slug' => 'contact-form',
                    'name' => 'Contact Form',
                    'description' => 'Volledig contactformulier met spambeveiliging, e-mailnotificaties en inbox in het admin paneel.',
                    'version' => '1.0.0',
                    'author' => 'ROICT',
                    'category' => 'Forms',
                    'icon' => 'envelope',
                    'tags' => ['contact', 'form', 'email'],
                    'downloads' => 1240,
                    'rating' => 4.8,
                    'price' => 'free',
                    'download_url' => BASE_URL . '/api/download.php?module=contact-form',
                ],
                [
                    'slug' => 'gallery',
                    'name' => 'Image Gallery',
                    'description' => 'Responsive image gallery with lightbox support and drag-and-drop upload.',
                    'version' => '2.0.1',
                    'author' => 'ROICT',
                    'category' => 'Media',
                    'icon' => 'images',
                    'tags' => ['gallery', 'images', 'media'],
                    'downloads' => 3560,
                    'rating' => 4.9,
                    'price' => 'free',
                    'download_url' => BASE_URL . '/api/download.php?module=gallery',
                ],
                [
                    'slug' => 'seo-tools',
                    'name' => 'SEO Tools',
                    'description' => 'Meta tags, sitemaps, robots.txt management and page analytics.',
                    'version' => '1.5.0',
                    'author' => 'ROICT',
                    'category' => 'SEO',
                    'icon' => 'search',
                    'tags' => ['seo', 'meta', 'sitemap'],
                    'downloads' => 2100,
                    'rating' => 4.7,
                    'price' => 'free',
                    'download_url' => BASE_URL . '/api/download.php?module=seo-tools',
                ],
                [
                    'slug' => 'newsletter',
                    'name' => 'Newsletter',
                    'description' => 'Email newsletter subscription and campaign management.',
                    'version' => '1.0.5',
                    'author' => 'ROICT',
                    'category' => 'Marketing',
                    'icon' => 'newspaper',
                    'tags' => ['newsletter', 'email', 'subscribers'],
                    'downloads' => 890,
                    'rating' => 4.5,
                    'price' => 'free',
                    'download_url' => BASE_URL . '/api/download.php?module=newsletter',
                ],
                [
                    'slug' => 'ecommerce',
                    'name' => 'E-Commerce',
                    'description' => 'Full featured webshop with products, cart and payment integrations.',
                    'version' => '3.1.0',
                    'author' => 'ROICT',
                    'category' => 'Commerce',
                    'icon' => 'bag',
                    'tags' => ['shop', 'products', 'payments'],
                    'downloads' => 4200,
                    'rating' => 4.6,
                    'price' => '€29',
                    'download_url' => BASE_URL . '/api/download.php?module=ecommerce',
                ],
                [
                    'slug' => 'analytics',
                    'name' => 'Analytics Dashboard',
                    'description' => 'Privacy-friendly analytics without external tracking.',
                    'version' => '1.1.0',
                    'author' => 'ROICT',
                    'category' => 'Analytics',
                    'icon' => 'bar-chart',
                    'tags' => ['analytics', 'stats', 'tracking'],
                    'downloads' => 1780,
                    'rating' => 4.4,
                    'price' => 'free',
                    'download_url' => BASE_URL . '/api/download.php?module=analytics',
                ],
            ],
            'themes' => [
                [
                    'slug' => 'roict-basic',
                    'name' => 'ROICT Basic',
                    'description' => 'Clean and modern default theme. Already included.',
                    'version' => '1.0.0',
                    'author' => 'ROICT',
                    'preview' => BASE_URL . '/themes/roict-basic/screenshot.png',
                    'downloads' => 9999,
                    'rating' => 4.9,
                    'price' => 'free',
                    'included' => true,
                ],
                [
                    'slug' => 'roict-dark',
                    'name' => 'ROICT Dark',
                    'description' => 'Sleek dark mode theme with neon accents.',
                    'version' => '1.0.0',
                    'author' => 'ROICT',
                    'preview' => '',
                    'downloads' => 3400,
                    'rating' => 4.8,
                    'price' => 'free',
                    'download_url' => BASE_URL . '/api/download.php?theme=roict-dark',
                ],
            ]
        ];
    }

    public static function install(string $slug, string $downloadUrl = ''): array {
        if (self::isInstalled($slug)) {
            return ['success' => false, 'message' => 'Module is al geïnstalleerd.'];
        }

        $moduleDir = MODULES_PATH . '/' . $slug;

        // Als de map nog niet bestaat, probeer te downloaden
        if (!is_dir($moduleDir)) {
            if (empty($downloadUrl)) {
                return ['success' => false, 'message' => 'Geen download URL opgegeven.'];
            }
            $result = self::downloadAndExtract($downloadUrl, MODULES_PATH, $slug);
            if (!$result['success']) {
                return $result;
            }
        }

        // Lees module.json voor naam/versie
        $info = [];
        $jsonFile = $moduleDir . '/module.json';
        if (file_exists($jsonFile)) {
            $info = json_decode(file_get_contents($jsonFile), true) ?? [];
        }

        // Registreer in database
        self::$db->insert(DB_PREFIX . 'modules', [
            'slug'         => $slug,
            'name'         => $info['name'] ?? ucwords(str_replace('-', ' ', $slug)),
            'version'      => $info['version'] ?? '1.0.0',
            'status'       => 'active',
            'installed_at' => date('Y-m-d H:i:s'),
        ]);

        // Voer install.php uit als die bestaat
        $installScript = $moduleDir . '/install.php';
        if (file_exists($installScript)) {
            require_once $installScript;
        }

        return ['success' => true, 'message' => ($info['name'] ?? $slug) . ' succesvol geïnstalleerd.'];
    }

    public static function installTheme(string $slug, string $downloadUrl): array {
        $themeDir = THEMES_PATH . '/' . $slug;
        if (is_dir($themeDir)) {
            return ['success' => false, 'message' => 'Thema is al geïnstalleerd.'];
        }
        $result = self::downloadAndExtract($downloadUrl, THEMES_PATH, $slug);
        if (!$result['success']) return $result;
        return ['success' => true, 'message' => 'Thema succesvol geïnstalleerd. U kunt het nu activeren.'];
    }

    /**
     * Download een ZIP van een URL en extraheer naar $targetDir/$slug
     * Ondersteunt GitHub releases én raw ZIP bestanden.
     */
    private static function downloadAndExtract(string $url, string $targetDir, string $slug): array {
        // Controleer of ZipArchive beschikbaar is
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'PHP ZipArchive extensie is niet beschikbaar op deze server.'];
        }

        // Controleer schrijfrechten
        if (!is_writable($targetDir)) {
            return ['success' => false, 'message' => "Map {$targetDir} is niet schrijfbaar."];
        }

        // Download ZIP naar tijdelijk bestand
        $tmpFile = tempnam(sys_get_temp_dir(), 'roict_mod_') . '.zip';
        $ctx = stream_context_create([
            'http' => [
                'timeout'     => 30,
                'user_agent'  => 'ROICT-CMS/' . CMS_VERSION,
                'follow_location' => 1,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $zipData = @file_get_contents($url, false, $ctx);
        if ($zipData === false) {
            return ['success' => false, 'message' => "Download mislukt. Controleer de URL: {$url}"];
        }

        file_put_contents($tmpFile, $zipData);

        // Open ZIP
        $zip = new ZipArchive();
        $opened = $zip->open($tmpFile);
        if ($opened !== true) {
            @unlink($tmpFile);
            return ['success' => false, 'message' => "Ongeldig ZIP bestand (code: {$opened})."];
        }

        // Bepaal de root map binnen de ZIP
        // GitHub ZIPs hebben een submap zoals "contact-form-main/" of "contact-form/"
        $zipRoot = '';
        $firstName = $zip->getNameIndex(0);
        if ($firstName && substr_count(rtrim($firstName, '/'), '/') === 0 && substr($firstName, -1) === '/') {
            $zipRoot = $firstName; // bijv. "contact-form/" of "contact-form-main/"
        }

        // Extraheer naar tijdelijke map
        $tmpExtract = sys_get_temp_dir() . '/roict_extract_' . $slug . '_' . time();
        $zip->extractTo($tmpExtract);
        $zip->close();
        @unlink($tmpFile);

        // Bepaal bronmap (met of zonder GitHub submap)
        $sourceDir = $tmpExtract;
        if ($zipRoot) {
            $possible = $tmpExtract . '/' . rtrim($zipRoot, '/');
            if (is_dir($possible)) {
                $sourceDir = $possible;
            }
        }

        // Verplaats naar definitieve locatie
        $destDir = $targetDir . '/' . $slug;
        if (is_dir($destDir)) {
            self::rrmdir($destDir); // overschrijf bij update
        }

        // Gebruik altijd rcopy (rename() faalt bij cross-device, bijv. /tmp -> public_html)
        self::rcopy($sourceDir, $destDir);
        self::rrmdir($tmpExtract);

        if (!is_dir($destDir)) {
            return ['success' => false, 'message' => 'Extractie mislukt. Controleer de serverrechten.'];
        }

        return ['success' => true];
    }

    /** Recursief map verwijderen */
    private static function rrmdir(string $dir): void {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? self::rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /** Recursief kopiëren (cross-device fallback) */
    private static function rcopy(string $src, string $dst): void {
        mkdir($dst, 0755, true);
        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') continue;
            $s = $src . '/' . $item;
            $d = $dst . '/' . $item;
            is_dir($s) ? self::rcopy($s, $d) : copy($s, $d);
        }
    }



    public static function update(string $slug, string $downloadUrl): array {
        if (!self::isInstalled($slug)) {
            return ['success' => false, 'message' => 'Module is niet geïnstalleerd.'];
        }

        // Bewaar huidige status (active/inactive)
        $current = self::$db->fetch("SELECT status FROM `" . DB_PREFIX . "modules` WHERE slug = ?", [$slug]);
        $status = $current['status'] ?? 'active';

        // Download en overschrijf de module map
        $result = self::downloadAndExtract($downloadUrl, MODULES_PATH, $slug);
        if (!$result['success']) return $result;

        // Lees nieuwe versie uit module.json
        $jsonFile = MODULES_PATH . '/' . $slug . '/module.json';
        $info = file_exists($jsonFile) ? (json_decode(file_get_contents($jsonFile), true) ?? []) : [];
        $newVersion = $info['version'] ?? null;

        // Update versie in database, behoud status
        self::$db->update(DB_PREFIX . 'modules', [
            'version' => $newVersion,
            'status'  => $status,
        ], 'slug = ?', [$slug]);

        return ['success' => true, 'message' => ($info['name'] ?? $slug) . ' bijgewerkt naar v' . $newVersion . '.'];
    }

    public static function uninstall(string $slug): array {
        // Voer uninstall.php uit als die bestaat
        $uninstallScript = MODULES_PATH . '/' . $slug . '/uninstall.php';
        if (file_exists($uninstallScript)) {
            require_once $uninstallScript;
        }
        self::$db->delete(DB_PREFIX . 'modules', 'slug = ?', [$slug]);
        return ['success' => true, 'message' => 'Module verwijderd.'];
    }

    public static function toggle(string $slug): array {
        $module = self::$db->fetch("SELECT * FROM `" . DB_PREFIX . "modules` WHERE slug = ?", [$slug]);
        if (!$module) return ['success' => false, 'message' => 'Module not found.'];
        $newStatus = $module['status'] === 'active' ? 'inactive' : 'active';
        self::$db->update(DB_PREFIX . 'modules', ['status' => $newStatus], 'slug = ?', [$slug]);
        return ['success' => true, 'status' => $newStatus];
    }
}
