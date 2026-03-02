<?php
require_once __DIR__ . '/../includes/init.php';
$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$post = $isEdit ? $db->fetch("SELECT * FROM `" . DB_PREFIX . "news` WHERE id = ?", [$id]) : null;
if ($isEdit && !$post) { flash('error', 'Bericht niet gevonden.'); redirect(BASE_URL . '/admin/news/'); }

$pageTitle = $isEdit ? 'Bericht bewerken' : 'Nieuw Bericht';
$activePage = 'news';
$categories = $db->fetchAll("SELECT * FROM `" . DB_PREFIX . "categories` WHERE type = 'news' ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'slug' => slug(trim($_POST['slug'] ?? $_POST['title'] ?? '')),
        'excerpt' => trim($_POST['excerpt'] ?? ''),
        'content' => $_POST['content'] ?? '',
        'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
        'meta_title' => trim($_POST['meta_title'] ?? ''),
        'meta_desc' => trim($_POST['meta_desc'] ?? ''),
        'status' => in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft',
        'featured_image' => trim($_POST['featured_image'] ?? '') ?: null,
    ];
    if ($_POST['status'] === 'published' && !$post['published_at']) {
        $data['published_at'] = date('Y-m-d H:i:s');
    }
    if (empty($data['title'])) { $error = 'Titel is verplicht.'; }
    else {
        if ($isEdit) {
            $db->update(DB_PREFIX . 'news', $data, 'id = ?', [$id]);
            flash('success', 'Bericht bijgewerkt.');
        } else {
            $data['author_id'] = $_SESSION['user_id'];
            $db->insert(DB_PREFIX . 'news', $data);
            flash('success', 'Bericht aangemaakt.');
        }
        redirect(BASE_URL . '/admin/news/');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 style="font-size:1.4rem;font-weight:800;margin:0;"><?= $pageTitle ?></h1>
  <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Terug</a>
</div>
<?php if (isset($error)): ?><div class="alert alert-danger mb-4"><?= e($error) ?></div><?php endif; ?>
<form method="POST">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-md-8">
      <div class="cms-card mb-4">
        <div class="cms-card-header"><span class="cms-card-title">Bericht Inhoud</span></div>
        <div class="cms-card-body">
          <div class="mb-3">
            <label class="form-label">Titel *</label>
            <input type="text" class="form-control" name="title" value="<?= e($post['title'] ?? '') ?>" data-slug-source="[name=slug]" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Slug</label>
            <div class="input-group">
              <span class="input-group-text text-muted"><?= BASE_URL ?>/news/</span>
              <input type="text" class="form-control" name="slug" value="<?= e($post['slug'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Samenvatting</label>
            <textarea class="form-control" name="excerpt" rows="3"><?= e($post['excerpt'] ?? '') ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Inhoud</label>
            <textarea class="form-control" name="content" rows="18" style="font-family:monospace;"><?= e($post['content'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
      <div class="cms-card">
        <div class="cms-card-header"><span class="cms-card-title">SEO</span></div>
        <div class="cms-card-body">
          <div class="mb-3"><label class="form-label">Meta Titel</label><input type="text" class="form-control" name="meta_title" value="<?= e($post['meta_title'] ?? '') ?>"></div>
          <div class="mb-0"><label class="form-label">Meta Beschrijving</label><textarea class="form-control" name="meta_desc" rows="2" maxlength="160"><?= e($post['meta_desc'] ?? '') ?></textarea></div>
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
              <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Concept</option>
              <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Gepubliceerd</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Categorie</label>
            <select class="form-select" name="category_id">
              <option value="">Geen categorie</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="px-4 pb-4 d-grid gap-2">
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'Opslaan' : 'Aanmaken' ?></button>
        </div>
      </div>
      <div class="cms-card">
        <div class="cms-card-header"><span class="cms-card-title">Uitgelichte Afbeelding</span></div>
        <div class="cms-card-body">
          <input type="hidden" name="featured_image" id="featured-image-url" value="<?= e($post['featured_image'] ?? '') ?>">
          <div id="featured-image-picker" class="border rounded p-3 text-center text-muted" style="border-style:dashed!important;cursor:pointer;" onclick="openFeaturedImagePicker()">
            <?php if (!empty($post['featured_image'])): ?>
            <img id="featured-image-preview" src="<?= e($post['featured_image']) ?>" alt="" style="max-width:100%;border-radius:8px;margin-bottom:.5rem;">
            <?php else: ?>
            <i id="featured-image-icon" class="bi bi-image" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
            <small id="featured-image-label">Klik om afbeelding te selecteren</small>
            <?php endif; ?>
          </div>
          <?php if (!empty($post['featured_image'])): ?>
          <button type="button" class="btn btn-outline-secondary btn-sm mt-2 w-100" onclick="removeFeaturedImage()"><i class="bi bi-x-lg me-1"></i> Verwijderen</button>
          <?php else: ?>
          <button type="button" class="btn btn-outline-secondary btn-sm mt-2 w-100" id="featured-remove-btn" style="display:none;" onclick="removeFeaturedImage()"><i class="bi bi-x-lg me-1"></i> Verwijderen</button>
          <?php endif; ?>
        </div>
      </div>
<script>
function openFeaturedImagePicker() {
  if (typeof openMediaModal !== 'function') return;
  openMediaModal(function(url) {
    document.getElementById('featured-image-url').value = url;
    var picker = document.getElementById('featured-image-picker');
    picker.innerHTML = '<img src="' + url + '" alt="" style="max-width:100%;border-radius:8px;">';
    var removeBtn = document.getElementById('featured-remove-btn');
    if (removeBtn) removeBtn.style.display = '';
  });
}
function removeFeaturedImage() {
  document.getElementById('featured-image-url').value = '';
  var picker = document.getElementById('featured-image-picker');
  picker.innerHTML = '<i class="bi bi-image" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i><small>Klik om afbeelding te selecteren</small>';
  var removeBtn = document.getElementById('featured-remove-btn');
  if (removeBtn) removeBtn.style.display = 'none';
}
</script>
    </div>
  </div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
