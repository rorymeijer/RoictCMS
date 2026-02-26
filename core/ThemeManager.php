<?php
class ThemeManager {
    // Marketplace voor thema's staat op GitHub
    private static $marketplaceUrl = 'https://raw.githubusercontent.com/rorymeijer/roictcms_themes/main/marketplace.json';

    public static function getAvailable(): array {
        $themes = [];
        $dirs = glob(THEMES_PATH . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $jsonFile = $dir . '/theme.json';
            if (file_exists($jsonFile)) {
                $info = json_decode(file_get_contents($jsonFile), true) ?? [];
                $info['slug'] = basename($dir);
                $info['path'] = $dir;
                $themes[] = $info;
            }
        }
        return $themes;
    }

    public static function getActive(): string {
        return Settings::get('active_theme', 'roict-basic');
    }

    public static function activate(string $slug): bool {
        if (is_dir(THEMES_PATH . '/' . $slug)) {
            Settings::set('active_theme', $slug);
            return true;
        }
        return false;
    }

    public static function getThemePath(): string {
        return THEMES_PATH . '/' . self::getActive();
    }

    public static function getThemeUrl(): string {
        return BASE_URL . '/themes/' . self::getActive();
    }

    // ── Marketplace ophalen van GitHub ────────────────────────────────────
    public static function getMarketplace(): array {
        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 8,
                'user_agent' => 'ROICT-CMS/' . CMS_VERSION,
            ],
        ]);
        $data = @file_get_contents(self::$marketplaceUrl, false, $ctx);
        if ($data) {
            $parsed = json_decode($data, true);
            if (!empty($parsed['themes'])) {
                return $parsed['themes'];
            }
        }
        return [];
    }

    // ── Download en installeer thema van URL ──────────────────────────────
    public static function install(string $slug, string $downloadUrl): array {
        $themeDir = THEMES_PATH . '/' . $slug;
        if (is_dir($themeDir)) {
            return ['success' => false, 'message' => 'Thema is al geïnstalleerd.'];
        }
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'PHP ZipArchive extensie is niet beschikbaar.'];
        }
        if (!is_writable(THEMES_PATH)) {
            return ['success' => false, 'message' => 'De themes/ map is niet schrijfbaar.'];
        }

        // Download
        $ctx = stream_context_create([
            'http' => [
                'timeout'         => 30,
                'user_agent'      => 'ROICT-CMS/' . CMS_VERSION,
                'follow_location' => 1,
            ],
        ]);
        $zipData = @file_get_contents($downloadUrl, false, $ctx);
        if (!$zipData) {
            return ['success' => false, 'message' => 'Download mislukt: ' . $downloadUrl];
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'roict_theme_') . '.zip';
        file_put_contents($tmpZip, $zipData);

        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            return ['success' => false, 'message' => 'Ongeldig ZIP bestand.'];
        }

        // Extraheer naar tijdelijke map
        $tmpExtract = sys_get_temp_dir() . '/roict_theme_' . $slug . '_' . time();
        $zip->extractTo($tmpExtract);
        $zip->close();
        @unlink($tmpZip);

        // Bepaal bronmap (GitHub submap zoals "mytheme-main/")
        $sourceDir = $tmpExtract;
        $entries = array_filter(scandir($tmpExtract), fn($e) => $e !== '.' && $e !== '..');
        if (count($entries) === 1) {
            $sub = $tmpExtract . '/' . reset($entries);
            if (is_dir($sub)) $sourceDir = $sub;
        }

        // Verplaats naar themes/
        if (!rename($sourceDir, $themeDir)) {
            self::rcopy($sourceDir, $themeDir);
            self::rrmdir($tmpExtract);
        } elseif (is_dir($tmpExtract)) {
            self::rrmdir($tmpExtract);
        }

        if (!is_dir($themeDir)) {
            return ['success' => false, 'message' => 'Installatie mislukt.'];
        }

        $info = [];
        if (file_exists($themeDir . '/theme.json')) {
            $info = json_decode(file_get_contents($themeDir . '/theme.json'), true) ?? [];
        }

        return ['success' => true, 'message' => ($info['name'] ?? $slug) . ' succesvol geïnstalleerd.'];
    }

    private static function rcopy(string $src, string $dst): void {
        mkdir($dst, 0755, true);
        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') continue;
            $s = $src . '/' . $item;
            $d = $dst . '/' . $item;
            is_dir($s) ? self::rcopy($s, $d) : copy($s, $d);
        }
    }

    private static function rrmdir(string $dir): void {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? self::rrmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
