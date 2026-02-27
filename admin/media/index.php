<?php
require_once __DIR__ . '/../includes/init.php';
$db = Database::getInstance();
$pageTitle = 'Media';
$activePage = 'media';

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && csrf_verify()) {
    $file = $_FILES['file'];
    $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $uploadDir = UPLOADS_PATH . '/images';

    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'Bestand is te groot voor de serverinstellingen.',
        UPLOAD_ERR_FORM_SIZE => 'Bestand is te groot voor dit formulier.',
        UPLOAD_ERR_PARTIAL => 'Bestand is slechts gedeeltelijk geüpload.',
        UPLOAD_ERR_NO_FILE => 'Geen bestand geselecteerd.',
        UPLOAD_ERR_NO_TMP_DIR => 'Serverfout: tijdelijke uploadmap ontbreekt.',
        UPLOAD_ERR_CANT_WRITE => 'Serverfout: bestand kon niet worden weggeschreven.',
        UPLOAD_ERR_EXTENSION => 'Upload geblokkeerd door een serverextensie.',
    ];

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        flash('error', $uploadErrors[$file['error']] ?? 'Upload mislukt door een onbekende serverfout.');
    }
    elseif (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        flash('error', 'Uploadmap kon niet worden aangemaakt. Controleer bestandsrechten.');
    }
    elseif (!is_writable($uploadDir)) {
        flash('error', 'Uploadmap is niet schrijfbaar. Controleer bestandsrechten.');
    }
    elseif (!in_array($file['type'], $allowedTypes, true)) { flash('error', 'Bestandstype niet toegestaan.'); }
    elseif ($file['size'] > $maxSize) { flash('error', 'Bestand is te groot (max 5MB).'); }
    else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = uniqid() . '_' . time() . '.' . strtolower($ext);
        $dest = $uploadDir . '/' . $newName;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $db->insert(DB_PREFIX . 'media', [
                'filename' => 'images/' . $newName,
                'original_name' => $file['name'],
                'mime_type' => $file['type'],
                'file_size' => $file['size'],
                'alt_text' => pathinfo($file['name'], PATHINFO_FILENAME),
                'author_id' => $_SESSION['user_id'],
            ]);
            flash('success', 'Bestand geüpload.');
        } else {
            flash('error', 'Upload mislukt.');
        }
    }
    redirect(BASE_URL . '/admin/media/');
}

// Handle delete
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id']) && csrf_verify()) {
    $media = $db->fetch("SELECT * FROM `" . DB_PREFIX . "media` WHERE id = ?", [(int)$_GET['id']]);
    if ($media) {
        @unlink(UPLOADS_PATH . '/' . $media['filename']);
        $db->delete(DB_PREFIX . 'media', 'id = ?', [(int)$_GET['id']]);
        flash('success', 'Bestand verwijderd.');
    }
    redirect(BASE_URL . '/admin/media/');
}

$mediaFiles = $db->fetchAll("SELECT m.*, u.username FROM `" . DB_PREFIX . "media` m LEFT JOIN `" . DB_PREFIX . "users` u ON m.author_id = u.id ORDER BY m.created_at DESC");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Media</h1>
    <p class="text-muted mb-0" style="font-size:.85rem;"><?= count($mediaFiles) ?> bestanden</p>
  </div>
</div>

<!-- Upload zone -->
<div class="cms-card mb-4">
  <div class="cms-card-header"><span class="cms-card-title"><i class="bi bi-upload me-2"></i>Bestand uploaden</span></div>
  <div class="cms-card-body">
    <form method="POST" enctype="multipart/form-data" id="upload-form">
      <?= csrf_field() ?>
      <div id="drop-zone" style="border:2px dashed var(--border);border-radius:14px;padding:3rem;text-align:center;cursor:pointer;transition:all .2s;" onclick="document.getElementById('file-input').click()">
        <i class="bi bi-cloud-upload" style="font-size:2.5rem;color:var(--text-muted);display:block;margin-bottom:.75rem;"></i>
        <div style="font-weight:700;margin-bottom:.25rem;">Sleep bestanden hier of klik om te selecteren</div>
        <div class="text-muted" style="font-size:.85rem;">JPG, PNG, GIF, WebP, PDF — max 5MB</div>
        <input type="file" name="file" id="file-input" style="display:none" accept="image/*,.pdf" onchange="this.form.submit()">
      </div>
    </form>
  </div>
</div>

<!-- Media grid -->
<?php if ($mediaFiles): ?>
<div class="row g-3">
  <?php foreach ($mediaFiles as $m): ?>
  <div class="col-6 col-md-3 col-lg-2">
    <div class="cms-card h-100" style="overflow:hidden;">
      <div style="height:140px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:14px 14px 0 0;">
        <?php if (str_starts_with($m['mime_type'], 'image/')): ?>
        <img src="<?= BASE_URL ?>/uploads/<?= e($m['filename']) ?>" alt="<?= e($m['alt_text']) ?>" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
        <i class="bi bi-file-earmark-pdf" style="font-size:3rem;color:#dc2626;"></i>
        <?php endif; ?>
      </div>
      <div style="padding:.75rem;">
        <div style="font-size:.75rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= e($m['original_name']) ?></div>
        <div class="text-muted" style="font-size:.7rem;"><?= round($m['file_size'] / 1024) ?>KB</div>
        <div class="d-flex gap-1 mt-2">
          <a href="<?= BASE_URL ?>/uploads/<?= e($m['filename']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary btn-icon flex-fill"><i class="bi bi-eye"></i></a>
          <a href="?action=delete&id=<?= $m['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger btn-icon flex-fill" data-confirm="Verwijderen?"><i class="bi bi-trash"></i></a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="cms-card">
  <div class="cms-card-body text-center py-5">
    <i class="bi bi-images" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.3;"></i>
    <p class="text-muted">Nog geen bestanden geüpload.</p>
  </div>
</div>
<?php endif; ?>

<script>
const dz = document.getElementById('drop-zone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor='var(--primary)'; dz.style.background='rgba(37,99,235,.04)'; });
dz.addEventListener('dragleave', () => { dz.style.borderColor=''; dz.style.background=''; });
dz.addEventListener('drop', e => {
  e.preventDefault();
  const dt = new DataTransfer();
  dt.items.add(e.dataTransfer.files[0]);
  document.getElementById('file-input').files = dt.files;
  document.getElementById('upload-form').submit();
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
