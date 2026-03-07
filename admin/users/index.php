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
  <sl-button href="add.php" variant="primary">
    <i slot="prefix" class="bi bi-plus-lg"></i> Nieuwe Gebruiker
  </sl-button>
</div>

<div class="cms-card">
  <table class="cms-table">
    <thead><tr><th>Gebruiker</th><th>Email</th><th>Rol</th><th>Laatste login</th><th>Status</th><th>Acties</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <?php $roles = ['admin' => ['primary','Admin'], 'editor' => ['success','Redacteur'], 'author' => ['neutral','Auteur'], 'lid' => ['neutral','Lid']]; $r = $roles[$u['role']] ?? ['neutral', $u['role']]; ?>
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
      <td><sl-badge variant="<?= $r[0] ?>" pill><?= $r[1] ?></sl-badge></td>
      <td class="text-muted"><?= $u['last_login'] ? date('d M Y H:i', strtotime($u['last_login'])) : 'Nooit' ?></td>
      <td><sl-badge variant="<?= $u['status'] === 'active' ? 'primary' : 'danger' ?>" pill><?= $u['status'] === 'active' ? 'Actief' : 'Inactief' ?></sl-badge></td>
      <td>
        <div class="action-btns">
          <sl-button href="edit.php?id=<?= $u['id'] ?>" size="small" variant="neutral" outline>
            <i class="bi bi-pencil"></i>
          </sl-button>
          <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
          <sl-button href="?action=delete&id=<?= $u['id'] ?>&csrf_token=<?= csrf_token() ?>" size="small" variant="danger" outline
            data-confirm="Gebruiker '<?= e(addslashes($u['username'])) ?>' verwijderen?">
            <i class="bi bi-trash"></i>
          </sl-button>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
