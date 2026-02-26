<?php
session_start();
require_once dirname(__DIR__) . '/core/bootstrap.php';

if (!INSTALLED) {
    header('Location: ' . BASE_URL . '/install/');
    exit;
}

if (Auth::isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        redirect(BASE_URL . '/admin/');
    } else {
        $error = 'Onjuiste gebruikersnaam of wachtwoord.';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inloggen — <?= e(Settings::get('site_name', 'ROICT CMS')) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%); min-height: 100vh; display: flex; align-items: center; font-family: 'Segoe UI', system-ui, sans-serif; }
.login-card { background: white; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,.4); width: 100%; max-width: 420px; overflow: hidden; }
.login-header { background: linear-gradient(135deg, #2563eb, #7c3aed); padding: 2.5rem; color: white; text-align: center; }
.login-logo { width: 56px; height: 56px; background: rgba(255,255,255,.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1rem; }
.login-body { padding: 2.25rem; }
.form-control { border-radius: 10px; padding: .65rem 1rem; border: 1.5px solid #e2e8f0; }
.form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.btn-login { background: linear-gradient(135deg, #2563eb, #7c3aed); border: none; color: white; width: 100%; padding: .75rem; border-radius: 10px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: opacity .2s; }
.btn-login:hover { opacity: .9; }
.input-group-text { border-radius: 10px 0 0 10px; border: 1.5px solid #e2e8f0; border-right: none; background: #f8fafc; }
.input-group .form-control { border-radius: 0 10px 10px 0; }
</style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center" style="min-height:100vh;">
  <div class="login-card">
    <div class="login-header">
      <div class="login-logo">⚡</div>
      <h1 style="font-size:1.5rem;font-weight:800;margin:0;"><?= e(Settings::get('site_name', 'ROICT CMS')) ?></h1>
      <p style="opacity:.8;margin:.25rem 0 0;font-size:.9rem;">Beheerdersingang</p>
    </div>
    <div class="login-body">
      <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill"></i><?= e($error) ?>
      </div>
      <?php endif; ?>
      <form method="POST" autocomplete="on">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label fw-semibold">Gebruikersnaam of Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" name="username" value="<?= e($_POST['username'] ?? '') ?>" autofocus required>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">Wachtwoord</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" name="password" required>
          </div>
        </div>
        <button type="submit" class="btn-login">Inloggen →</button>
      </form>
      <div class="text-center mt-4">
        <a href="<?= BASE_URL ?>/" style="color:#64748b;font-size:.85rem;text-decoration:none;">
          <i class="bi bi-arrow-left"></i> Terug naar website
        </a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
