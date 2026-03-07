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
        'role' => in_array($_POST['role'] ?? '', ['admin', 'editor', 'author', 'lid']) ? $_POST['role'] : 'author',
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
            $id = $db->insert(DB_PREFIX . 'users', $data);
            flash('success', 'Gebruiker aangemaakt.');
        }
        do_action('admin_user_saved', $id, $isEdit);
        redirect(BASE_URL . '/admin/users/');
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
<div class="row justify-content-center"><div class="col-md-7">
<div class="cms-card">
  <div class="cms-card-header"><span class="cms-card-title">Gebruikersgegevens</span></div>
  <div class="cms-card-body">
    <form method="POST">
      <?= csrf_field() ?>
      <sl-input class="mb-3" label="Gebruikersnaam *" type="text" name="username"
        value="<?= e($user['username'] ?? '') ?>" required></sl-input>
      <sl-input class="mb-3" label="Email *" type="email" name="email"
        value="<?= e($user['email'] ?? '') ?>" required></sl-input>
      <sl-input class="mb-3" label="Wachtwoord <?= $isEdit ? '(laat leeg om niet te wijzigen)' : '*' ?>"
        type="password" name="password" <?= !$isEdit ? 'required' : '' ?> minlength="8"
        password-toggle></sl-input>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <sl-select label="Rol" name="role" value="<?= e($user['role'] ?? 'author') ?>">
            <sl-option value="lid">Lid (alleen frontend)</sl-option>
            <sl-option value="author">Auteur</sl-option>
            <sl-option value="editor">Redacteur</sl-option>
            <sl-option value="admin">Beheerder</sl-option>
          </sl-select>
        </div>
        <div class="col-md-6">
          <sl-select label="Status" name="status" value="<?= e($user['status'] ?? 'active') ?>">
            <sl-option value="active">Actief</sl-option>
            <sl-option value="inactive">Inactief</sl-option>
          </sl-select>
        </div>
      </div>
      <?php do_action('admin_user_form_fields', $user ?? null, $id); ?>
      <sl-button type="submit" variant="primary" class="w-100">
        <i slot="prefix" class="bi bi-check-lg"></i>
        <?= $isEdit ? 'Wijzigingen opslaan' : 'Gebruiker aanmaken' ?>
      </sl-button>
    </form>
  </div>
</div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
