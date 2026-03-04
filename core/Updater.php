<?php
class Updater {
    // GitHub Releases API – geeft altijd de laatste release terug
    private static $releasesApiUrl = 'https://api.github.com/repos/rorymeijer/RoictCMS/releases/latest';

    // Huidige versie komt altijd uit CMS_VERSION in config.php
    public static function currentVersion(): string {
        return CMS_VERSION;
    }

    // Cache-levensduur in seconden (10 minuten)
    private static int $cacheTtl = 600;

    public static function checkForUpdates(): array {
        $current = self::currentVersion();

        // Serveer gecachte data als die nog vers genoeg is
        if (INSTALLED && class_exists('Settings')) {
            $cacheTime = (int) Settings::get('updater_cache_time', '0');
            $cacheData = Settings::get('updater_cache_data', '');
            if ($cacheData && (time() - $cacheTime) < self::$cacheTtl) {
                $cached = json_decode($cacheData, true);
                if (is_array($cached)) {
                    return $cached;
                }
            }
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 8,
                'user_agent' => 'ROICT-CMS/' . $current,
            ],
        ]);

        $data = @file_get_contents(self::$releasesApiUrl, false, $ctx);
        if ($data) {
            $release = json_decode($data, true);
            if ($release && isset($release['tag_name'])) {
                // tag_name kan "v1.0.38" of "1.0.38" zijn — strip de "v" prefix
                $version = ltrim($release['tag_name'], 'v');

                // Kies download URL: eerst een geüpload asset (.zip), anders zipball
                $downloadUrl = $release['zipball_url'] ?? null;
                if (!empty($release['assets'])) {
                    foreach ($release['assets'] as $asset) {
                        if (isset($asset['browser_download_url']) && str_ends_with($asset['name'], '.zip')) {
                            $downloadUrl = $asset['browser_download_url'];
                            break;
                        }
                    }
                }

                // Release body (markdown) splitsen in regels voor de changelog
                $changelog = [];
                if (!empty($release['body'])) {
                    foreach (explode("\n", $release['body']) as $line) {
                        $line = trim($line, "\r\n- *# ");
                        if ($line !== '') {
                            $changelog[] = $line;
                        }
                    }
                }

                // Datum: "2026-03-04T00:00:00Z" → "2026-03-04"
                $releaseDate = isset($release['published_at'])
                    ? substr($release['published_at'], 0, 10)
                    : null;

                $result = [
                    'current'          => $current,
                    'latest'           => $version,
                    'changelog'        => $changelog,
                    'download_url'     => $downloadUrl,
                    'update_available' => version_compare($version, $current, '>'),
                    'release_date'     => $releaseDate,
                ];
                self::storeCache($result);
                return $result;
            }
        }

        $result = [
            'current'          => $current,
            'latest'           => $current,
            'changelog'        => [],
            'download_url'     => null,
            'update_available' => false,
            'release_date'     => null,
        ];
        self::storeCache($result);
        return $result;
    }

    private static function storeCache(array $result): void {
        if (INSTALLED && class_exists('Settings')) {
            Settings::set('updater_cache_time', (string) time());
            Settings::set('updater_cache_data', json_encode($result));
        }
    }

    // ── Voer de update uit ────────────────────────────────────────────────
    public static function performUpdate(string $downloadUrl): array {
        $steps = [];

        // 1. Download ZIP
        $steps[] = self::step('Updatepakket downloaden...');
        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 60,
                'user_agent' => 'ROICT-CMS/' . CMS_VERSION,
                'follow_location' => 1,
            ],
        ]);
        $zipData = @file_get_contents($downloadUrl, false, $ctx);
        if (!$zipData) {
            return ['success' => false, 'message' => 'Download mislukt. Controleer de internetverbinding van de server.', 'steps' => $steps];
        }
        $steps[] = self::step('Updatepakket gedownload (' . round(strlen($zipData) / 1024) . ' KB)', true);

        // 2. Backup
        $steps[] = self::step('Backup aanmaken...');
        $backupFile = self::createBackup();
        $steps[] = self::step('Backup aangemaakt: ' . basename($backupFile), true);

        // 3. ZIP opslaan en uitpakken
        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'message' => 'PHP ZipArchive extensie is niet beschikbaar.', 'steps' => $steps];
        }
        $tmpZip = tempnam(sys_get_temp_dir(), 'roict_update_') . '.zip';
        file_put_contents($tmpZip, $zipData);

        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            @unlink($tmpZip);
            return ['success' => false, 'message' => 'Ongeldig ZIP bestand.', 'steps' => $steps];
        }

        // 4. Extraheer naar tijdelijke map
        $steps[] = self::step('Bestanden uitpakken...');
        $tmpDir = sys_get_temp_dir() . '/roict_update_' . time();
        $zip->extractTo($tmpDir);
        $zip->close();
        @unlink($tmpZip);

        // Bepaal de root in de ZIP (GitHub voegt submap toe zoals "RoictCMS-main/")
        $sourceDir = $tmpDir;
        $entries = array_filter(scandir($tmpDir), fn($e) => $e !== '.' && $e !== '..');
        if (count($entries) === 1) {
            $sub = $tmpDir . '/' . reset($entries);
            if (is_dir($sub)) $sourceDir = $sub;
        }
        $steps[] = self::step('Bestanden uitgepakt', true);

        // 5. Kopieer bestanden over — sla config.php en uploads/ over
        $steps[] = self::step('Bestanden installeren...');
        $skip = ['config.php', 'uploads', 'backups'];

        // Lees het nieuwe versienummer VOOR we bestanden overschrijven
        $newVersion = self::readVersionFromPackage($sourceDir);

        self::copyUpdate($sourceDir, BASE_PATH, $skip);
        self::rrmdir($tmpDir);
        $steps[] = self::step('Bestanden geïnstalleerd', true);

        // Sla nieuwe versie op in database zodat het direct zichtbaar is
        if ($newVersion && INSTALLED && class_exists('Settings')) {
            Settings::set('cms_version', $newVersion);
            $steps[] = self::step('Versie bijgewerkt naar ' . $newVersion, true);
        }

        // Wis update-cache zodat de nieuwe versie direct zichtbaar is
        if (INSTALLED && class_exists('Settings')) {
            Settings::set('updater_cache_time', '0');
            Settings::set('updater_cache_data', '');
        }

        // 6. Database migraties uitvoeren indien aanwezig
        $migrationFile = BASE_PATH . '/core/migrations.php';
        if (file_exists($migrationFile)) {
            $steps[] = self::step('Database migraties uitvoeren...');
            require_once $migrationFile;
            $steps[] = self::step('Migraties uitgevoerd', true);
        }

        // 7. Cache legen (OPcache)
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        $steps[] = self::step('Cache geleegd', true);

        return [
            'success' => true,
            'message' => 'CMS succesvol bijgewerkt!',
            'steps'   => $steps,
        ];
    }

    // ── Lees versie uit gedownload pakket (via core/config.php) ─────────
    private static function readVersionFromPackage(string $sourceDir): ?string {
        $configFile = $sourceDir . '/core/config.php';
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            if (preg_match("/define\s*\(\s*['\"]CMS_VERSION['\"]\s*,\s*['\"]([^\'\"]+)['\"]\s*\)/", $content, $m)) {
                return $m[1];
            }
        }
        return null;
    }

    // ── Maak een backup van alle CMS bestanden (excl. uploads) ────────────
    public static function createBackup(): string {
        $backupDir = BASE_PATH . '/backups';
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

        $filename  = $backupDir . '/backup_v' . CMS_VERSION . '_' . date('Y-m-d_H-i-s') . '.zip';

        if (!class_exists('ZipArchive')) {
            // Geen ZipArchive: maak een tekst-marker aan als fallback
            file_put_contents($filename . '.txt', 'Backup marker — ZipArchive niet beschikbaar');
            return $filename . '.txt';
        }

        $zip = new ZipArchive();
        $zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $skip = ['backups', 'uploads'];
        self::zipDirectory(BASE_PATH, BASE_PATH, $zip, $skip);
        $zip->close();

        return $filename;
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private static function step(string $label, bool $done = false): array {
        return ['label' => $label, 'done' => $done];
    }

    /** Kopieer update-bestanden over BASE_PATH, sla $skip over */
    private static function copyUpdate(string $src, string $dst, array $skip = []): void {
        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') continue;
            if (in_array($item, $skip)) continue;
            $s = $src . '/' . $item;
            $d = $dst . '/' . $item;
            if (is_dir($s)) {
                if (!is_dir($d)) mkdir($d, 0755, true);
                self::copyUpdate($s, $d, []);
            } else {
                copy($s, $d);
            }
        }
    }

    /** Voeg map recursief toe aan ZIP */
    private static function zipDirectory(string $base, string $dir, ZipArchive $zip, array $skip = []): void {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            if (in_array($item, $skip)) continue;
            $path = $dir . '/' . $item;
            $relative = ltrim(str_replace($base, '', $path), '/\\');
            if (is_dir($path)) {
                $zip->addEmptyDir($relative);
                self::zipDirectory($base, $path, $zip, $skip);
            } else {
                $zip->addFile($path, $relative);
            }
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
