<?php
if (!defined('DB_PREFIX')) exit;

// Migration 1.0.1 – Add 'lid' to the role ENUM so members can be saved
$db = Database::getInstance();
try {
    $db->getPdo()->exec(
        "ALTER TABLE `" . DB_PREFIX . "users`
         MODIFY COLUMN `role` ENUM('admin','editor','author','lid') DEFAULT 'author'"
    );
} catch (PDOException $e) {
    // Column is already up-to-date; nothing to do
}

if (class_exists('Settings')) {
    Settings::set('db_schema_version', '1.0.1');
}
