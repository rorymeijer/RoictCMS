<?php
/**
 * Admin Toegankelijkheid – uninstall.php
 * Verwijdert de module-instelling uit de database.
 */
$db = Database::getInstance();
$db->query("DELETE FROM `" . DB_PREFIX . "settings` WHERE `key` LIKE 'admin_accessibility_%'");
