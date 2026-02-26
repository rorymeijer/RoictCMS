<?php
class Settings {
    private static $cache = [];
    private static $db;

    public static function init(): void {
        self::$db = Database::getInstance();
    }

    public static function get(string $key, $default = null) {
        if (isset(self::$cache[$key])) return self::$cache[$key];
        $row = self::$db->fetch("SELECT `value` FROM `" . DB_PREFIX . "settings` WHERE `key` = ?", [$key]);
        self::$cache[$key] = $row ? $row['value'] : $default;
        return self::$cache[$key];
    }

    public static function set(string $key, $value): void {
        self::$cache[$key] = $value;
        $existing = self::$db->fetch("SELECT id FROM `" . DB_PREFIX . "settings` WHERE `key` = ?", [$key]);
        if ($existing) {
            self::$db->update(DB_PREFIX . 'settings', ['value' => $value], '`key` = ?', [$key]);
        } else {
            self::$db->getPdo()->prepare("INSERT INTO `" . DB_PREFIX . "settings` (`key`, `value`) VALUES (?, ?)")->execute([$key, $value]);
        }
    }

    public static function getAll(): array {
        $rows = self::$db->fetchAll("SELECT `key`, `value` FROM `" . DB_PREFIX . "settings`");
        foreach ($rows as $row) {
            self::$cache[$row['key']] = $row['value'];
        }
        return self::$cache;
    }

    public static function setMultiple(array $settings): void {
        foreach ($settings as $key => $value) {
            self::set($key, $value);
        }
    }
}
