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
  <sl-button href="index.php" variant="neutral" outline size="small">
    <i slot="prefix" class="bi bi-arrow-left"></i> Terug
  </sl-button>
</div>
<?php if (isset($error)): ?>
<sl-alert variant="danger" open class="mb-4">
  <sl-icon slot="icon" name="exclamation-circle"></sl-icon><?= e($error) ?>
</sl-alert>
<?php endif; ?>
<form method="POST">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-md-8">
      <div class="cms-card mb-4">
        <div class="cms-card-header"><span class="cms-card-title">Bericht Inhoud</span></div>
        <div class="cms-card-body">
          <sl-input class="mb-3" label="Titel *" type="text" name="title"
            value="<?= e($post['title'] ?? '') ?>" data-slug-source="[name=slug]" required></sl-input>
          <sl-input class="mb-3" label="Slug" name="slug" value="<?= e($post['slug'] ?? '') ?>">
            <span slot="prefix"><?= BASE_URL ?>/news/</span>
          </sl-input>
          <sl-textarea class="mb-3" label="Samenvatting" name="excerpt" rows="3"
            value="<?= e($post['excerpt'] ?? '') ?>"></sl-textarea>
          <sl-textarea class="mb-0 monospace" label="Inhoud" name="content" rows="18"
            value="<?= e($post['content'] ?? '') ?>"></sl-textarea>
        </div>
      </div>
      <div class="cms-card">
        <div class="cms-card-header"><span class="cms-card-title">SEO</span></div>
        <div class="cms-card-body">
          <sl-input class="mb-3" label="Meta Titel" name="meta_title" value="<?= e($post['meta_title'] ?? '') ?>"></sl-input>
          <sl-textarea label="Meta Beschrijving" name="meta_desc" rows="2" maxlength="160"
            value="<?= e($post['meta_desc'] ?? '') ?>"></sl-textarea>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">Publiceren</span></div>
        <div class="cms-card-body">
          <sl-select class="mb-3" label="Status" name="status" value="<?= e($post['status'] ?? 'draft') ?>">
            <sl-option value="draft">Concept</sl-option>
            <sl-option value="published">Gepubliceerd</sl-option>
          </sl-select>
          <sl-select class="mb-0" label="Categorie" name="category_id" value="<?= e($post['category_id'] ?? '') ?>" placeholder="Geen categorie">
            <sl-option value="">Geen categorie</sl-option>
            <?php foreach ($categories as $cat): ?>
            <sl-option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></sl-option>
            <?php endforeach; ?>
          </sl-select>
        </div>
        <div class="px-4 pb-4 d-grid gap-2 mt-3">
          <sl-button type="submit" variant="primary" class="w-100">
            <i slot="prefix" class="bi bi-check-lg"></i>
            <?= $isEdit ? 'Opslaan' : 'Aanmaken' ?>
          </sl-button>
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
          <sl-button type="button" variant="neutral" outline size="small" class="w-100 mt-2" onclick="removeFeaturedImage()">
            <i slot="prefix" class="bi bi-x-lg"></i> Verwijderen
          </sl-button>
          <?php else: ?>
          <sl-button type="button" variant="neutral" outline size="small" class="w-100 mt-2" id="featured-remove-btn" style="display:none;" onclick="removeFeaturedImage()">
            <i slot="prefix" class="bi bi-x-lg"></i> Verwijderen
          </sl-button>
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
