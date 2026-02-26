<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();
$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$user = $isEdit ? $db->fetch("SELECT * FROM `" . DB_PREFIX . "users` WHERE id = ?", [$id]) : null;
if ($isEdit && !$user) { flash('error', 'Gebruiker niet gevonden.'); redirect(BASE_URL . '/admin/users/'); }

$pageTitle = $isEdit ? 'Gebruiker bewerken' : 'Nieuwe Gebruiker';
$activePage = 'users';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'role' => in_array($_POST['role'] ?? '', ['admin', 'editor', 'author']) ? $_POST['role'] : 'author',
        'status' => in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active',
    ];
    $password = trim($_POST['password'] ?? '');
    if (!$isEdit && empty($password)) { $error = 'Wachtwoord is verplicht voor nieuwe gebruikers.'; }
    elseif (!empty($password)) { $data['password'] = Auth::hashPassword($password); }
    
    if (!isset($error)) {
        if ($isEdit) {
            $db->update(DB_PREFIX . 'users', $data, 'id = ?', [$id]);
            flash('success', 'Gebruiker bijgewerkt.');
        } else {
            $db->insert(DB_PREFIX . 'users', $data);
            flash('success', 'Gebruiker aangemaakt.');
        }
        redirect(BASE_URL . '/admin/users/');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 style="font-size:1.4rem;font-weight:800;margin:0;"><?= $pageTitle ?></h1>
  <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Terug</a>
</div>
<?php if (isset($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="row justify-content-center"><div class="col-md-7">
<div class="cms-card">
  <div class="cms-card-header"><span class="cms-card-title">Gebruikersgegevens</span></div>
  <div class="cms-card-body">
    <form method="POST">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Gebruikersnaam *</label><input type="text" class="form-control" name="username" value="<?= e($user['username'] ?? '') ?>" required></div>
      <div class="mb-3"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" value="<?= e($user['email'] ?? '') ?>" required></div>
      <div class="mb-3">
        <label class="form-label">Wachtwoord <?= $isEdit ? '(laat leeg om niet te wijzigen)' : '*' ?></label>
        <input type="password" class="form-control" name="password" <?= !$isEdit ? 'required' : '' ?> minlength="8">
      </div>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label">Rol</label>
          <select class="form-select" name="role">
            <option value="author" <?= ($user['role'] ?? 'author') === 'author' ? 'selected' : '' ?>>Auteur</option>
            <option value="editor" <?= ($user['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Redacteur</option>
            <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Beheerder</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Actief</option>
            <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactief</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i> <?= $isEdit ? 'Wijzigingen opslaan' : 'Gebruiker aanmaken' ?></button>
    </form>
  </div>
</div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
