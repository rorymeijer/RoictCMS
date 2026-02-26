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
  <a href="add.php" class="quick-add-btn"><i class="bi bi-plus-lg"></i> Nieuwe Pagina</a>
</div>

<div class="cms-card">
  <div class="cms-card-header">
    <form method="GET" class="d-flex gap-2" style="flex:1;max-width:360px;">
      <input type="search" class="form-control form-control-sm" name="q" value="<?= e($search) ?>" placeholder="Zoeken...">
      <button class="btn btn-sm btn-outline-secondary">Zoek</button>
    </form>
    <div class="d-flex gap-2 align-items-center">
      <select class="form-select form-select-sm" onchange="location='?status='+this.value" style="width:auto;">
        <option value="">Alle statussen</option>
        <option value="published" <?= ($_GET['status'] ?? '') === 'published' ? 'selected' : '' ?>>Gepubliceerd</option>
        <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Concept</option>
      </select>
    </div>
  </div>
  <table class="cms-table">
    <thead>
      <tr>
        <th style="width:40px;"><input type="checkbox" class="form-check-input"></th>
        <th>Titel</th><th>Slug</th><th>Auteur</th><th>Status</th><th>Bijgewerkt</th><th style="width:100px;">Acties</th>
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
        <td><span class="badge-status badge-<?= $p['status'] ?>"><?= $p['status'] === 'published' ? 'Gepubliceerd' : 'Concept' ?></span></td>
        <td class="text-muted"><?= date('d M Y', strtotime($p['updated_at'])) ?></td>
        <td>
          <div class="action-btns">
            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary btn-icon" title="Bewerken"><i class="bi bi-pencil"></i></a>
            <a href="<?= BASE_URL ?>/<?= e($p['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary btn-icon" title="Bekijken"><i class="bi bi-eye"></i></a>
            <a href="?action=delete&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger btn-icon" data-confirm="Pagina '<?= e(addslashes($p['title'])) ?>' verwijderen?" title="Verwijderen"><i class="bi bi-trash"></i></a>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  <?php if ($pagination['total_pages'] > 1): ?>
  <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
    <span class="text-muted" style="font-size:.82rem;"><?= $pagination['offset'] + 1 ?>–<?= min($pagination['offset'] + $perPage, $total) ?> van <?= $total ?></span>
    <nav><ul class="pagination pagination-sm mb-0">
      <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
      <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
        <a class="page-link" href="?p=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>"><?= $i ?></a>
      </li>
      <?php endfor; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
