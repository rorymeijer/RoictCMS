<?php
require_once __DIR__ . '/../includes/init.php';
$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$page = $isEdit ? $db->fetch("SELECT * FROM `" . DB_PREFIX . "pages` WHERE id = ?", [$id]) : null;
if ($isEdit && !$page) { flash('error', 'Pagina niet gevonden.'); redirect(BASE_URL . '/admin/pages/'); }

$pageTitle = $isEdit ? 'Pagina bewerken' : 'Nieuwe Pagina';
$activePage = 'pages';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'slug' => slug(trim($_POST['slug'] ?? $_POST['title'] ?? '')),
        'content' => $_POST['content'] ?? '',
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_desc' => trim($_POST['meta_desc'] ?? ''),
        'status' => in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft',
    ];
    if (empty($data['title'])) { $error = 'Titel is verplicht.'; }
    else {
        if ($isEdit) {
            $db->update(DB_PREFIX . 'pages', $data, 'id = ?', [$id]);
            flash('success', 'Pagina bijgewerkt.');
        } else {
            $data['author_id'] = $_SESSION['user_id'];
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->insert(DB_PREFIX . 'pages', $data);
            flash('success', 'Pagina aangemaakt.');
        }
        redirect(BASE_URL . '/admin/pages/');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0;"><?= $pageTitle ?></h1>
  </div>
  <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Terug</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger mb-4"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-md-8">
      <div class="cms-card mb-4">
        <div class="cms-card-header"><span class="cms-card-title">Inhoud</span></div>
        <div class="cms-card-body">
          <div class="mb-3">
            <label class="form-label">Titel *</label>
            <input type="text" class="form-control" name="title" value="<?= e($page['title'] ?? $_POST['title'] ?? '') ?>" data-slug-source="[name=slug]" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Slug</label>
            <div class="input-group">
              <span class="input-group-text text-muted"><?= BASE_URL ?>/</span>
              <input type="text" class="form-control" name="slug" value="<?= e($page['slug'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Inhoud</label>
            <textarea class="form-control" name="content" id="editor" rows="18" style="font-family:monospace;"><?= e($page['content'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
      <div class="cms-card">
        <div class="cms-card-header"><span class="cms-card-title">SEO</span></div>
        <div class="cms-card-body">
          <div class="mb-3">
            <label class="form-label">Meta Titel</label>
            <input type="text" class="form-control" name="meta_title" value="<?= e($page['meta_title'] ?? '') ?>" placeholder="Laat leeg om paginatitel te gebruiken">
          </div>
          <div class="mb-0">
            <label class="form-label">Meta Beschrijving</label>
            <textarea class="form-control" name="meta_desc" rows="3" maxlength="160" placeholder="Maximaal 160 tekens"><?= e($page['meta_desc'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">Publiceren</span></div>
        <div class="cms-card-body">
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Concept</option>
              <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Gepubliceerd</option>
            </select>
          </div>
          <?php if ($isEdit): ?>
          <div class="text-muted" style="font-size:.78rem;">
            Aangemaakt: <?= date('d M Y H:i', strtotime($page['created_at'])) ?><br>
            Bijgewerkt: <?= date('d M Y H:i', strtotime($page['updated_at'])) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="px-4 pb-4 d-grid gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'Wijzigingen opslaan' : 'Pagina aanmaken' ?></button>
          <?php if ($isEdit): ?>
          <a href="<?= BASE_URL ?>/<?= e($page['slug']) ?>" target="_blank" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> Bekijk pagina
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</form>

<?php
$extraScript = "
// Simple rich-text toggle
const textarea = document.getElementById('editor');
if (textarea) {
  textarea.style.minHeight = '400px';
}
";
require_once __DIR__ . '/../includes/footer.php'; ?>
