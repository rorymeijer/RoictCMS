<?php
class ModuleManager {
    private static $db;
    private static $modulesRepoApiUrl  = 'https://api.github.com/repos/rorymeijer/roictcms_modules/contents/';
    private static $modulesRepoRawBase = 'https://github.com/rorymeijer/roictcms_modules/raw/main/';
    private static $zipCacheFile       = null; // set lazily: BASE_PATH . '/api/zip_marketplace_cache.json'
    private static $zipCacheInterval   = 900; // seconden tussen GitHub API checks (15 min)

    public static function init(): void {
        self::$db = Database::getInstance();
    }

    // Boot alle actieve modules (laad hun init.php)
    public static function bootModules(): void {
        // Synchroniseer versies van alle modules met hun module.json
        // (vangt zowel NULL-versies als versieveranderingen na een update op)
        $allModules = self::$db->fetchAll(
            "SELECT slug, version FROM `" . DB_PREFIX . "modules`"
        );
        foreach ($allModules as $m) {
            $jsonFile = MODULES_PATH . '/' . $m['slug'] . '/module.json';
            if (file_exists($jsonFile)) {
                $info = json_decode(file_get_contents($jsonFile), true) ?? [];
                if (!empty($info['version']) && $info['version'] !== ($m['version'] ?? '')) {
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
        self::$zipCacheFile = BASE_PATH . '/api/zip_marketplace_cache.json';

        // Laad bestaande cache
        $cache = [];
        if (file_exists(self::$zipCacheFile)) {
            $cache = json_decode(file_get_contents(self::$zipCacheFile), true) ?? [];
        }

        // Gebruik cache als die nog vers genoeg is (< zipCacheInterval seconden oud)
        if (!empty($cache['checked_at']) && (time() - $cache['checked_at']) < self::$zipCacheInterval) {
            return self::buildMarketplaceFromCache($cache);
        }

        // Haal lijst van zip-bestanden op via GitHub API
        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 10,
                'user_agent' => 'ROICT-CMS/' . CMS_VERSION,
            ],
        ]);
        $listJson = @file_get_contents(self::$modulesRepoApiUrl, false, $ctx);

        if (!$listJson) {
            // GitHub onbereikbaar: gebruik bestaande cache (ook als stale)
            if (!empty($cache['modules'])) {
                return self::buildMarketplaceFromCache($cache);
            }
            return self::getMockMarketplace();
        }

        $files = json_decode($listJson, true) ?? [];
        $zipFiles = array_filter(
            $files,
            fn($f) => isset($f['name'], $f['sha'], $f['type'])
                   && str_ends_with($f['name'], '.zip')
                   && $f['type'] === 'file'
        );

        $modules = $cache['modules'] ?? [];

        foreach ($zipFiles as $file) {
            $slug = basename($file['name'], '.zip');
            $sha  = $file['sha'];

            // Sla over als de SHA niet veranderd is (zip is niet gewijzigd)
            if (!empty($modules[$slug]) && ($modules[$slug]['_sha'] ?? '') === $sha) {
                continue;
            }

            // Download zip en lees module.json eruit
            $info = self::readModuleJsonFromZip(
                self::$modulesRepoRawBase . $file['name'],
                $slug
            );
            if ($info !== null) {
                $info['_sha'] = $sha;
                $modules[$slug] = $info;
            }
        }

        // Verwijder modules waarvan de zip niet meer bestaat in de repo
        $liveSlugs = array_map(fn($f) => basename($f['name'], '.zip'), array_values($zipFiles));
        foreach (array_keys($modules) as $slug) {
            if (!in_array($slug, $liveSlugs, true)) {
                unset($modules[$slug]);
            }
        }

        // Cache opslaan
        $newCache = ['checked_at' => time(), 'modules' => $modules];
        @file_put_contents(self::$zipCacheFile, json_encode($newCache));

        return self::buildMarketplaceFromCache($newCache);
    }

    /**
     * Verwijder marketplace cache en forceer direct een nieuwe ZIP-check.
     */
    public static function refreshMarketplaceCache(): bool {
        self::$zipCacheFile = BASE_PATH . '/api/zip_marketplace_cache.json';

        if (file_exists(self::$zipCacheFile) && !@unlink(self::$zipCacheFile)) {
            return false;
        }

        self::getMarketplace();
        return true;
    }

    /**
     * Download een ZIP van GitHub en lees de module.json eruit zonder te installeren.
     */
    private static function readModuleJsonFromZip(string $zipUrl, string $slug): ?array {
        if (!class_exists('ZipArchive')) {
            return null;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout'         => 20,
                'user_agent'      => 'ROICT-CMS/' . CMS_VERSION,
                'follow_location' => 1,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $zipData = @file_get_contents($zipUrl, false, $ctx);
        if ($zipData === false) {
            return null;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'roict_mkt_') . '.zip';
        file_put_contents($tmpFile, $zipData);

        $info = null;
        $zip  = new ZipArchive();
        if ($zip->open($tmpFile) === true) {
            // Probeer {slug}/module.json, dan module.json aan de root
            $json = $zip->getFromName($slug . '/module.json')
                 ?: $zip->getFromName('module.json');
            if ($json !== false) {
                $info = json_decode($json, true);
            }
            $zip->close();
        }
        @unlink($tmpFile);

        return $info;
    }

    /**
     * Bouw de marketplace-array op uit de gecachede ZIP-metadata.
     */
    private static function buildMarketplaceFromCache(array $cache): array {
        $modules = [];
        foreach ($cache['modules'] ?? [] as $slug => $info) {
            // Verwijder interne cachevelden
            $entry = array_diff_key($info, ['_sha' => true]);

            $entry['slug']        = $slug;
            $entry['download_url'] = $entry['download_url']
                ?? (self::$modulesRepoRawBase . $slug . '.zip');
            $entry['price']    = $entry['price']    ?? 'free';
            $entry['tags']     = $entry['tags']     ?? [];
            $entry['downloads'] = $entry['downloads'] ?? 0;
            $entry['rating']   = $entry['rating']   ?? 5.0;

            // Leid status af uit versienummer als die ontbreekt
            if (empty($entry['status'])) {
                $parts = explode('.', $entry['version'] ?? '1.0.0');
                $major = (int)($parts[0] ?? 0);
                $minor = (int)($parts[1] ?? 0);
                if ($major === 0 && $minor === 0) {
                    $entry['status'] = 'alpha';
                } elseif ($major === 0) {
                    $entry['status'] = 'beta';
                }
            }

            $modules[] = $entry;
        }

        // Filter op zichtbare statussen op basis van instellingen
        $showReleased = Settings::get('marketplace_show_released', '1') !== '0';
        $showBeta     = Settings::get('marketplace_show_beta',     '0') === '1';
        $showAlpha    = Settings::get('marketplace_show_alpha',    '0') === '1';

        $modules = array_values(array_filter($modules, function($m) use ($showReleased, $showBeta, $showAlpha) {
            $status = $m['status'] ?? '';
            if ($status === 'alpha') return $showAlpha;
            if ($status === 'beta')  return $showBeta;
            return $showReleased; // stabiel/released
        }));

        usort($modules, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        // Thema's: haal op via ThemeManager's eigen marketplace (ongewijzigd)
        $themes = self::getThemeMarketplace();

        return ['modules' => $modules, 'themes' => $themes];
    }

    /**
     * Haal thema-marketplace op (marketplace.json van de themes-repo).
     */
    private static function getThemeMarketplace(): array {
        $themesUrl = 'https://raw.githubusercontent.com/rorymeijer/roictcms_themes/main/marketplace.json';
        $ctx = stream_context_create([
            'http' => ['timeout' => 8, 'user_agent' => 'ROICT-CMS/' . CMS_VERSION],
        ]);
        $data = @file_get_contents($themesUrl, false, $ctx);
        if ($data) {
            $parsed = json_decode($data, true);
            if (!empty($parsed['themes'])) {
                return $parsed['themes'];
            }
        }

        // Fallback: lokale api/marketplace.json
        $localFile = BASE_PATH . '/api/marketplace.json';
        if (file_exists($localFile)) {
            $parsed = json_decode(file_get_contents($localFile), true) ?? [];
            if (!empty($parsed['themes'])) {
                return $parsed['themes'];
            }
        }

        return self::getMockMarketplace()['themes'];
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

    public static function installFromUpload(array $file): array {
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'PHP ZipArchive extensie is niet beschikbaar op deze server.'];
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload mislukt.'];
        }

        $originalName = (string)($file['name'] ?? '');
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'zip') {
            return ['success' => false, 'message' => 'Alleen ZIP-bestanden zijn toegestaan.'];
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'message' => 'Ongeldig uploadbestand ontvangen.'];
        }

        $zip = new ZipArchive();
        $opened = $zip->open($tmpName);
        if ($opened !== true) {
            return ['success' => false, 'message' => 'Ongeldig ZIP-bestand.'];
        }

        $moduleJsonPath = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (!is_string($entry)) {
                continue;
            }
            if (str_ends_with($entry, '/module.json') || $entry === 'module.json') {
                $moduleJsonPath = $entry;
                break;
            }
        }

        if ($moduleJsonPath === null) {
            $zip->close();
            return ['success' => false, 'message' => 'ZIP bevat geen geldige module (module.json ontbreekt).'];
        }

        $moduleJsonRaw = $zip->getFromName($moduleJsonPath);
        $moduleInfo = is_string($moduleJsonRaw) ? json_decode($moduleJsonRaw, true) : null;
        if (!is_array($moduleInfo) || empty($moduleInfo['name']) || empty($moduleInfo['version'])) {
            $zip->close();
            return ['success' => false, 'message' => 'module.json is ongeldig of onvolledig.'];
        }

        $slugFromJson = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)($moduleInfo['slug'] ?? '')));
        $slugFromFolder = '';
        if ($moduleJsonPath !== 'module.json') {
            $firstPart = explode('/', $moduleJsonPath)[0] ?? '';
            $slugFromFolder = preg_replace('/[^a-z0-9\-]/', '', strtolower((string)$firstPart));
        }
        $slugFromName = trim((string)($moduleInfo['name'] ?? ''));
        $slugFromName = preg_replace('/[^a-z0-9\-]/', '-', strtolower($slugFromName));
        $slugFromName = preg_replace('/-+/', '-', $slugFromName ?? '');
        $slugFromName = trim((string)$slugFromName, '-');

        $slug = $slugFromJson ?: ($slugFromFolder ?: $slugFromName);
        if ($slug === '') {
            $zip->close();
            return ['success' => false, 'message' => 'Kon geen geldige moduleslug bepalen.'];
        }

        if (self::isInstalled($slug)) {
            $zip->close();
            return ['success' => false, 'message' => 'Module is al geïnstalleerd.'];
        }

        $tmpExtract = sys_get_temp_dir() . '/roict_upload_' . $slug . '_' . time();
        $zip->extractTo($tmpExtract);
        $zip->close();

        $sourceDir = $tmpExtract;
        if ($moduleJsonPath !== 'module.json') {
            $moduleRoot = dirname($moduleJsonPath);
            if ($moduleRoot !== '.') {
                $candidate = $tmpExtract . '/' . $moduleRoot;
                if (is_dir($candidate)) {
                    $sourceDir = $candidate;
                }
            }
        }

        if (!file_exists($sourceDir . '/module.json')) {
            self::rrmdir($tmpExtract);
            return ['success' => false, 'message' => 'Upload bevat geen bruikbare module-structuur.'];
        }

        $destDir = MODULES_PATH . '/' . $slug;
        if (is_dir($destDir)) {
            self::rrmdir($tmpExtract);
            return ['success' => false, 'message' => 'Modulemap bestaat al.'];
        }

        self::rcopy($sourceDir, $destDir);
        self::rrmdir($tmpExtract);

        return self::install($slug);
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

        // Extraheer naar tijdelijke map
        $tmpExtract = sys_get_temp_dir() . '/roict_extract_' . $slug . '_' . time();
        $zip->extractTo($tmpExtract);
        $zip->close();
        @unlink($tmpFile);

        // Bepaal bronmap: als de ZIP precies één submap bevat (GitHub-stijl zoals
        // "contact-form-main/"), gebruik die als bron — ongeacht de volgorde van ZIP-entries.
        $sourceDir = $tmpExtract;
        $entries = array_values(array_filter(
            scandir($tmpExtract),
            fn($e) => $e !== '.' && $e !== '..'
        ));
        if (count($entries) === 1) {
            $sub = $tmpExtract . '/' . $entries[0];
            if (is_dir($sub)) {
                $sourceDir = $sub;
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

        // Bewaar huidige status en versie (als fallback)
        $current = self::$db->fetch("SELECT status, version FROM `" . DB_PREFIX . "modules` WHERE slug = ?", [$slug]);
        $status = $current['status'] ?? 'active';
        $existingVersion = $current['version'] ?? null;

        // Download en overschrijf de module map
        $result = self::downloadAndExtract($downloadUrl, MODULES_PATH, $slug);
        if (!$result['success']) return $result;

        // Lees nieuwe versie uit module.json; val terug op bestaande versie als er geen is
        $jsonFile = MODULES_PATH . '/' . $slug . '/module.json';
        $info = file_exists($jsonFile) ? (json_decode(file_get_contents($jsonFile), true) ?? []) : [];
        $newVersion = $info['version'] ?? $existingVersion;

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
