<?php
require_once __DIR__ . '/../includes/init.php';
$db = Database::getInstance();
$pageTitle = "Pagina's";
$activePage = 'pages';

// Handle delete
if ($_GET['action'] ?? '' === 'delete' && isset($_GET['id']) && csrf_verify()) {
    $db->delete(DB_PREFIX . 'pages', 'id = ?', [(int)$_GET['id']]);
    flash('success', "Pagina verwijderd.");
    redirect(BASE_URL . '/admin/pages/');
}

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$search = trim($_GET['q'] ?? '');
$where = $search ? "WHERE title LIKE ?" : "";
$params = $search ? ["%{$search}%"] : [];
$total = $db->fetch("SELECT COUNT(*) as c FROM `" . DB_PREFIX . "pages` {$where}", $params)['c'];
$pagination = paginate($total, $perPage, $page);
$pages = $db->fetchAll("SELECT p.*, u.username FROM `" . DB_PREFIX . "pages` p LEFT JOIN `" . DB_PREFIX . "users` u ON p.author_id = u.id {$where} ORDER BY p.updated_at DESC LIMIT {$perPage} OFFSET {$pagination['offset']}", $params);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Pagina's</h1>
    <p class="text-muted mb-0" style="font-size:.85rem;"><?= $total ?> pagina's totaal</p>
  </div>
  <sl-button href="add.php" variant="primary">
    <i slot="prefix" class="bi bi-plus-lg"></i> Nieuwe Pagina
  </sl-button>
</div>

<div class="cms-card">
  <div class="cms-card-header">
    <form method="GET" class="d-flex gap-2" style="flex:1;max-width:360px;">
      <sl-input type="search" name="q" value="<?= e($search) ?>" placeholder="Zoeken..." size="small" style="flex:1;"></sl-input>
      <sl-button type="submit" size="small" variant="neutral" outline>Zoek</sl-button>
    </form>
    <sl-select id="page-status-filter" size="small" value="<?= e($_GET['status'] ?? '') ?>" placeholder="Alle statussen" style="min-width:170px;" clearable>
      <sl-option value="published">Gepubliceerd</sl-option>
      <sl-option value="draft">Concept</sl-option>
    </sl-select>
    <script>
    document.getElementById('page-status-filter').addEventListener('sl-change', function() {
      location = '?status=' + (this.value || '');
    });
    </script>
  </div>
  <table class="cms-table">
    <thead>
      <tr>
        <th style="width:40px;"><input type="checkbox" class="form-check-input"></th>
        <th>Titel</th><th>Slug</th><th>Auteur</th><th>Status</th><th>Bijgewerkt</th><th style="width:110px;">Acties</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$pages): ?>
      <tr><td colspan="7" class="text-center text-muted py-5">
        <i class="bi bi-file-earmark-text" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3;"></i>
        Nog geen pagina's. <a href="add.php">Maak de eerste pagina aan.</a>
      </td></tr>
      <?php else: foreach ($pages as $p): ?>
      <tr>
        <td><input type="checkbox" class="form-check-input"></td>
        <td>
          <a href="edit.php?id=<?= $p['id'] ?>" class="text-decoration-none fw-semibold"><?= e($p['title']) ?></a>
        </td>
        <td><code style="font-size:.78rem;color:#64748b;">/<?= e($p['slug']) ?></code></td>
        <td class="text-muted"><?= e($p['username'] ?? '—') ?></td>
        <td>
          <sl-badge variant="<?= $p['status'] === 'published' ? 'success' : 'neutral' ?>" pill>
            <?= $p['status'] === 'published' ? 'Gepubliceerd' : 'Concept' ?>
          </sl-badge>
        </td>
        <td class="text-muted"><?= date('d M Y', strtotime($p['updated_at'])) ?></td>
        <td>
          <div class="action-btns">
            <sl-button href="edit.php?id=<?= $p['id'] ?>" size="small" variant="neutral" outline title="Bewerken">
              <i class="bi bi-pencil"></i>
            </sl-button>
            <sl-button href="<?= BASE_URL ?>/<?= e($p['slug']) ?>" target="_blank" size="small" variant="neutral" outline title="Bekijken">
              <i class="bi bi-eye"></i>
            </sl-button>
            <sl-button href="?action=delete&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>" size="small" variant="danger" outline title="Verwijderen"
              data-confirm="Pagina '<?= e(addslashes($p['title'])) ?>' verwijderen?">
              <i class="bi bi-trash"></i>
            </sl-button>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  <?php if ($pagination['total_pages'] > 1): ?>
  <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
    <span class="text-muted" style="font-size:.82rem;"><?= $pagination['offset'] + 1 ?>–<?= min($pagination['offset'] + $perPage, $total) ?> van <?= $total ?></span>
    <div class="d-flex gap-1">
      <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
      <sl-button href="?p=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>" size="small"
        variant="<?= $i === $pagination['current_page'] ? 'primary' : 'neutral' ?>"
        <?= $i === $pagination['current_page'] ? '' : 'outline' ?>><?= $i ?></sl-button>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
