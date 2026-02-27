<?php
/**
 * Media Manager JSON API
 *
 * Geeft een JSON-lijst van media-bestanden terug voor gebruik in de WYSIWYG editor.
 * Alleen toegankelijk voor ingelogde beheerders.
 *
 * GET  /admin/media/api.php           → lijst van alle afbeeldingen
 * GET  /admin/media/api.php?type=all  → lijst van alle bestanden (ook PDF)
 */
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();

// Filter op type: standaard alleen afbeeldingen
$typeFilter = ($_GET['type'] ?? 'images') === 'all' ? '' : "WHERE m.mime_type LIKE 'image/%'";

$mediaFiles = $db->fetchAll(
    "SELECT m.id, m.filename, m.original_name, m.mime_type, m.file_size, m.alt_text, m.created_at
     FROM `" . DB_PREFIX . "media` m
     $typeFilter
     ORDER BY m.created_at DESC"
);

$result = array_map(function (array $m): array {
    return [
        'id'            => (int) $m['id'],
        'url'           => BASE_URL . '/uploads/' . $m['filename'],
        'filename'      => $m['filename'],
        'original_name' => $m['original_name'],
        'mime_type'     => $m['mime_type'],
        'file_size'     => (int) $m['file_size'],
        'alt_text'      => $m['alt_text'] ?? '',
    ];
}, $mediaFiles);

echo json_encode(['success' => true, 'media' => $result], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
