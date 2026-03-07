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
        <div class="cms-card-header"><span class="cms-card-title">Inhoud</span></div>
        <div class="cms-card-body">
          <sl-input class="mb-3" label="Titel *" type="text" name="title"
            value="<?= e($page['title'] ?? $_POST['title'] ?? '') ?>"
            data-slug-source="[name=slug]" required></sl-input>
          <sl-input class="mb-3" label="Slug" name="slug" value="<?= e($page['slug'] ?? '') ?>">
            <span slot="prefix"><?= BASE_URL ?>/</span>
          </sl-input>
          <sl-textarea class="mb-0 monospace" label="Inhoud" name="content" rows="18"
            value="<?= e($page['content'] ?? '') ?>"></sl-textarea>
        </div>
      </div>
      <div class="cms-card">
        <div class="cms-card-header"><span class="cms-card-title">SEO</span></div>
        <div class="cms-card-body">
          <sl-input class="mb-3" label="Meta Titel" name="meta_title"
            value="<?= e($page['meta_title'] ?? '') ?>"
            placeholder="Laat leeg om paginatitel te gebruiken"></sl-input>
          <sl-textarea label="Meta Beschrijving" name="meta_desc" rows="3" maxlength="160"
            placeholder="Maximaal 160 tekens" value="<?= e($page['meta_desc'] ?? '') ?>"></sl-textarea>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">Publiceren</span></div>
        <div class="cms-card-body">
          <sl-select class="mb-3" label="Status" name="status" value="<?= e($page['status'] ?? 'draft') ?>">
            <sl-option value="draft">Concept</sl-option>
            <sl-option value="published">Gepubliceerd</sl-option>
          </sl-select>
          <?php if ($isEdit): ?>
          <div class="text-muted" style="font-size:.78rem;">
            Aangemaakt: <?= date('d M Y H:i', strtotime($page['created_at'])) ?><br>
            Bijgewerkt: <?= date('d M Y H:i', strtotime($page['updated_at'])) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="px-4 pb-4 d-grid gap-2">
          <sl-button type="submit" variant="primary" class="w-100">
            <i slot="prefix" class="bi bi-check-lg"></i>
            <?= $isEdit ? 'Wijzigingen opslaan' : 'Pagina aanmaken' ?>
          </sl-button>
          <?php if ($isEdit): ?>
          <sl-button href="<?= BASE_URL ?>/<?= e($page['slug']) ?>" target="_blank" variant="neutral" outline class="w-100">
            <i slot="prefix" class="bi bi-eye"></i> Bekijk pagina
          </sl-button>
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
