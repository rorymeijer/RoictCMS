<?php
require_once __DIR__ . '/../includes/init.php';
$db = Database::getInstance();
$pageTitle = 'Nieuws';
$activePage = 'news';

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id']) && csrf_verify()) {
    $db->delete(DB_PREFIX . 'news', 'id = ?', [(int)$_GET['id']]);
    flash('success', 'Bericht verwijderd.');
    redirect(BASE_URL . '/admin/news/');
}

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 15;
$search = trim($_GET['q'] ?? '');
$where = $search ? "WHERE n.title LIKE ?" : "";
$params = $search ? ["%{$search}%"] : [];
$total = $db->fetch("SELECT COUNT(*) as c FROM `" . DB_PREFIX . "news` n {$where}", $params)['c'];
$pagination = paginate($total, $perPage, $page);
$posts = $db->fetchAll("SELECT n.*, u.username, c.name as category_name FROM `" . DB_PREFIX . "news` n LEFT JOIN `" . DB_PREFIX . "users` u ON n.author_id = u.id LEFT JOIN `" . DB_PREFIX . "categories` c ON n.category_id = c.id {$where} ORDER BY n.created_at DESC LIMIT {$perPage} OFFSET {$pagination['offset']}", $params);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Nieuwsberichten</h1>
    <p class="text-muted mb-0" style="font-size:.85rem;"><?= $total ?> berichten totaal</p>
  </div>
  <a href="add.php" class="quick-add-btn"><i class="bi bi-plus-lg"></i> Nieuw Bericht</a>
</div>

<div class="cms-card">
  <div class="cms-card-header">
    <form method="GET" class="d-flex gap-2" style="flex:1;max-width:360px;">
      <input type="search" class="form-control form-control-sm" name="q" value="<?= e($search) ?>" placeholder="Zoeken...">
      <button class="btn btn-sm btn-outline-secondary">Zoek</button>
    </form>
  </div>
  <table class="cms-table">
    <thead>
      <tr>
        <th style="width:40px;"><input type="checkbox" class="form-check-input"></th>
        <th>Titel</th><th>Categorie</th><th>Auteur</th><th>Status</th><th>Datum</th><th style="width:100px;">Acties</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$posts): ?>
      <tr><td colspan="7" class="text-center text-muted py-5">
        <i class="bi bi-newspaper" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3;"></i>
        Nog geen berichten. <a href="add.php">Schrijf het eerste bericht.</a>
      </td></tr>
      <?php else: foreach ($posts as $p): ?>
      <tr>
        <td><input type="checkbox" class="form-check-input"></td>
        <td>
          <a href="edit.php?id=<?= $p['id'] ?>" class="text-decoration-none fw-semibold"><?= e($p['title']) ?></a>
          <?php if ($p['excerpt']): ?><br><small class="text-muted"><?= e(substr($p['excerpt'], 0, 80)) ?>...</small><?php endif; ?>
        </td>
        <td class="text-muted"><?= e($p['category_name'] ?? '—') ?></td>
        <td class="text-muted"><?= e($p['username'] ?? '—') ?></td>
        <td><span class="badge-status badge-<?= $p['status'] ?>"><?= $p['status'] === 'published' ? 'Gepubliceerd' : 'Concept' ?></span></td>
        <td class="text-muted"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
        <td>
          <div class="action-btns">
            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary btn-icon"><i class="bi bi-pencil"></i></a>
            <a href="<?= BASE_URL ?>/news/<?= e($p['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary btn-icon"><i class="bi bi-eye"></i></a>
            <a href="?action=delete&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger btn-icon" data-confirm="Bericht '<?= e(addslashes($p['title'])) ?>' verwijderen?"><i class="bi bi-trash"></i></a>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
