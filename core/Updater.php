<?php
class Updater {
    // version.json op GitHub bevat versie + changelog + download_url naar de CMS ZIP
    private static $versionUrl = 'https://raw.githubusercontent.com/rorymeijer/RoictCMS/main/version.json';

    // ── Versie check ──────────────────────────────────────────────────────
    public static function checkForUpdates(): array {
        $ctx = stream_context_create([
            'http' => [
                'timeout'    => 8,
                'user_agent' => 'ROICT-CMS/' . CMS_VERSION,
            ],
        ]);

        $data = @file_get_contents(self::$versionUrl, false, $ctx);
        if ($data) {
            $remote = json_decode($data, true);
            if ($remote && isset($remote['version'])) {
                return [
                    'current'          => CMS_VERSION,
                    'latest'           => $remote['version'],
                    'changelog'        => $remote['changelog'] ?? [],
                    'download_url'     => $remote['download_url'] ?? null,
                    'update_available' => version_compare($remote['version'], CMS_VERSION, '>'),
                    'release_date'     => $remote['release_date'] ?? null,
                ];
            }
        }

        return [
            'current'          => CMS_VERSION,
            'latest'           => CMS_VERSION,
            'changelog'        => [],
            'download_url'     => null,
            'update_available' => false,
            'release_date'     => null,
        ];
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
        self::copyUpdate($sourceDir, BASE_PATH, $skip);
        self::rrmdir($tmpDir);
        $steps[] = self::step('Bestanden geïnstalleerd', true);

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
