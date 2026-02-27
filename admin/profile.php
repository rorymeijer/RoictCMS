<?php
require_once __DIR__ . '/includes/init.php';
Auth::requireLogin();

$db = Database::getInstance();
$pageTitle = 'Mijn Profiel';
$activePage = 'profile';

$currentUser = Auth::currentUser();
if (!$currentUser) {
    flash('error', 'Gebruiker niet gevonden.');
    redirect(BASE_URL . '/admin/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $passwordConfirm = trim($_POST['password_confirm'] ?? '');

    if (empty($username)) {
        $errors[] = 'Gebruikersnaam is verplicht.';
    } else {
        $taken = $db->fetch(
            "SELECT id FROM `" . DB_PREFIX . "users` WHERE username = ? AND id != ?",
            [$username, $currentUser['id']]
        );
        if ($taken) $errors[] = 'Deze gebruikersnaam is al in gebruik.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Voer een geldig e-mailadres in.';
    } else {
        $taken = $db->fetch(
            "SELECT id FROM `" . DB_PREFIX . "users` WHERE email = ? AND id != ?",
            [$email, $currentUser['id']]
        );
        if ($taken) $errors[] = 'Dit e-mailadres is al in gebruik.';
    }

    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = 'Wachtwoord moet minimaal 8 tekens bevatten.';
        } elseif ($password !== $passwordConfirm) {
            $errors[] = 'Wachtwoorden komen niet overeen.';
        }
    }

    if (empty($errors)) {
        $data = ['username' => $username, 'email' => $email];
        if (!empty($password)) {
            $data['password'] = Auth::hashPassword($password);
        }
        $db->update(DB_PREFIX . 'users', $data, 'id = ?', [$currentUser['id']]);
        $_SESSION['user_name'] = $username;
        flash('success', 'Profiel bijgewerkt.');
        redirect(BASE_URL . '/admin/profile.php');
    }

    // Keep entered values on validation error
    $currentUser['username'] = $username;
    $currentUser['email']    = $email;
}

$fullUser = $db->fetch(
    "SELECT last_login, created_at FROM `" . DB_PREFIX . "users` WHERE id = ?",
    [$currentUser['id']]
);

require_once __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Mijn Profiel</h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
  <ul class="mb-0 ps-3">
    <?php foreach ($errors as $err): ?>
    <li><?= e($err) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-md-7">
    <div class="cms-card">
      <div class="cms-card-header"><span class="cms-card-title">Profielgegevens</span></div>
      <div class="cms-card-body">
        <form method="POST">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Gebruikersnaam *</label>
            <input type="text" class="form-control" name="username" value="<?= e($currentUser['username'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">E-mailadres *</label>
            <input type="email" class="form-control" name="email" value="<?= e($currentUser['email'] ?? '') ?>" required>
          </div>
          <hr class="my-4">
          <p class="fw-semibold mb-3 text-muted" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;">Wachtwoord wijzigen</p>
          <div class="mb-3">
            <label class="form-label">Nieuw wachtwoord <span class="text-muted fw-normal">(laat leeg om niet te wijzigen)</span></label>
            <input type="password" class="form-control" name="password" minlength="8" autocomplete="new-password">
          </div>
          <div class="mb-4">
            <label class="form-label">Wachtwoord bevestigen</label>
            <input type="password" class="form-control" name="password_confirm" minlength="8" autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i> Wijzigingen opslaan
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-5">
    <div class="cms-card">
      <div class="cms-card-header"><span class="cms-card-title">Accountinformatie</span></div>
      <div class="cms-card-body">
        <div class="text-center mb-4">
          <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#fff;margin:0 auto;">
            <?= strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)) ?>
          </div>
          <div class="fw-bold mt-2"><?= e($currentUser['username'] ?? '') ?></div>
          <div class="text-muted small"><?= e($currentUser['email'] ?? '') ?></div>
        </div>
        <table style="width:100%;font-size:.85rem;">
          <tr class="border-bottom">
            <td class="text-muted py-2">Rol</td>
            <td class="text-end py-2 fw-semibold"><?= ucfirst(e($currentUser['role'] ?? '')) ?></td>
          </tr>
          <tr class="border-bottom">
            <td class="text-muted py-2">Laatste login</td>
            <td class="text-end py-2 fw-semibold">
              <?= $fullUser['last_login'] ? date('d M Y, H:i', strtotime($fullUser['last_login'])) : '—' ?>
            </td>
          </tr>
          <tr>
            <td class="text-muted py-2">Lid sinds</td>
            <td class="text-end py-2 fw-semibold">
              <?= $fullUser['created_at'] ? date('d M Y', strtotime($fullUser['created_at'])) : '—' ?>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
