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
  <div class="d-flex gap-2">
    <sl-button href="settings.php" variant="neutral" outline size="small">
      <i slot="prefix" class="bi bi-gear"></i> Instellingen
    </sl-button>
    <sl-button href="add.php" variant="primary">
      <i slot="prefix" class="bi bi-plus-lg"></i> Nieuw Bericht
    </sl-button>
  </div>
</div>

<div class="cms-card">
  <div class="cms-card-header">
    <form method="GET" class="d-flex gap-2" style="flex:1;max-width:360px;">
      <sl-input type="search" name="q" value="<?= e($search) ?>" placeholder="Zoeken..." size="small" style="flex:1;"></sl-input>
      <sl-button type="submit" size="small" variant="neutral" outline>Zoek</sl-button>
    </form>
  </div>
  <table class="cms-table">
    <thead>
      <tr>
        <th style="width:40px;"><input type="checkbox" class="form-check-input"></th>
        <th>Titel</th><th>Categorie</th><th>Auteur</th><th>Status</th><th>Datum</th><th style="width:110px;">Acties</th>
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
        <td>
          <sl-badge variant="<?= $p['status'] === 'published' ? 'success' : 'neutral' ?>" pill>
            <?= $p['status'] === 'published' ? 'Gepubliceerd' : 'Concept' ?>
          </sl-badge>
        </td>
        <td class="text-muted"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
        <td>
          <div class="action-btns">
            <sl-button href="edit.php?id=<?= $p['id'] ?>" size="small" variant="neutral" outline>
              <i class="bi bi-pencil"></i>
            </sl-button>
            <sl-button href="<?= BASE_URL ?>/news/<?= e($p['slug']) ?>" target="_blank" size="small" variant="neutral" outline>
              <i class="bi bi-eye"></i>
            </sl-button>
            <sl-button href="?action=delete&id=<?= $p['id'] ?>&csrf_token=<?= csrf_token() ?>" size="small" variant="danger" outline
              data-confirm="Bericht '<?= e(addslashes($p['title'])) ?>' verwijderen?">
              <i class="bi bi-trash"></i>
            </sl-button>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
