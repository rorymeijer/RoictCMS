<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();
$db = Database::getInstance();
$pageTitle = 'Gebruikers';
$activePage = 'users';

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id']) && csrf_verify()) {
    if ((int)$_GET['id'] === (int)$_SESSION['user_id']) { flash('error', 'U kunt uzelf niet verwijderen.'); }
    else { $db->delete(DB_PREFIX . 'users', 'id = ?', [(int)$_GET['id']]); flash('success', 'Gebruiker verwijderd.'); }
    redirect(BASE_URL . '/admin/users/');
}

$users = $db->fetchAll("SELECT * FROM `" . DB_PREFIX . "users` ORDER BY created_at DESC");
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Gebruikers</h1>
    <p class="text-muted mb-0" style="font-size:.85rem;"><?= count($users) ?> gebruikers</p>
  </div>
  <a href="add.php" class="quick-add-btn"><i class="bi bi-plus-lg"></i> Nieuwe Gebruiker</a>
</div>

<div class="cms-card">
  <table class="cms-table">
    <thead><tr><th>Gebruiker</th><th>Email</th><th>Rol</th><th>Laatste login</th><th>Status</th><th>Acties</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
      <td>
        <div class="d-flex align-items-center gap-3">
          <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;color:white;flex-shrink:0;"><?= strtoupper(substr($u['username'],0,1)) ?></div>
          <div>
            <div class="fw-semibold"><?= e($u['username']) ?></div>
            <?php if ($u['id'] === (int)$_SESSION['user_id']): ?><small class="text-primary">Dat ben jij</small><?php endif; ?>
          </div>
        </div>
      </td>
      <td class="text-muted"><?= e($u['email']) ?></td>
      <td>
        <?php $roles = ['admin' => ['primary','Admin'], 'editor' => ['success','Redacteur'], 'author' => ['secondary','Auteur']]; $r = $roles[$u['role']] ?? ['secondary',$u['role']]; ?>
        <span class="badge bg-<?= $r[0] ?>"><?= $r[1] ?></span>
      </td>
      <td class="text-muted"><?= $u['last_login'] ? date('d M Y H:i', strtotime($u['last_login'])) : 'Nooit' ?></td>
      <td><span class="badge-status badge-<?= $u['status'] ?>"><?= $u['status'] === 'active' ? 'Actief' : 'Inactief' ?></span></td>
      <td>
        <div class="action-btns">
          <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-secondary btn-icon"><i class="bi bi-pencil"></i></a>
          <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
          <a href="?action=delete&id=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger btn-icon" data-confirm="Gebruiker '<?= e(addslashes($u['username'])) ?>' verwijderen?"><i class="bi bi-trash"></i></a>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
