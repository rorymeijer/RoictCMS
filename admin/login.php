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

if (!headers_sent()) {
    ob_start('admin_translate_html');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        redirect(BASE_URL . '/admin/');
    } else {
        $error = 'Onjuiste gebruikersnaam of wachtwoord.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(admin_lang()) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inloggen — <?= e(Settings::get('site_name', 'ROICT CMS')) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.12.0/cdn/themes/light.css">
<script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.12.0/cdn/shoelace.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<?php do_action('admin_login_head'); ?>
<style>
body { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%); min-height: 100vh; display: flex; align-items: center; font-family: 'Segoe UI', system-ui, sans-serif; }
.login-card { background: white; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,.4); width: 100%; max-width: 420px; overflow: hidden; }
.login-header { background: linear-gradient(135deg, #2563eb, #7c3aed); padding: 2.5rem; color: white; text-align: center; }
.login-logo { width: 56px; height: 56px; background: rgba(255,255,255,.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1rem; }
.login-body { padding: 2.25rem; }
sl-input { --sl-input-border-color: #e2e8f0; --sl-input-border-color-focus: #2563eb; --sl-focus-ring-color: rgba(37,99,235,.1); --sl-input-border-radius-medium: 10px; }
sl-alert { --sl-border-radius-medium: 10px; }
.btn-login { background: linear-gradient(135deg, #2563eb, #7c3aed); border: none; color: white; width: 100%; padding: .75rem; border-radius: 10px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: opacity .2s; }
.btn-login:hover { opacity: .9; }
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
      <sl-alert variant="danger" open style="margin-bottom:1.25rem;">
        <sl-icon slot="icon" name="exclamation-triangle"></sl-icon><?= e($error) ?>
      </sl-alert>
      <?php endif; ?>
      <form method="POST" autocomplete="on">
        <?= csrf_field() ?>
        <div style="margin-bottom:1rem;">
          <sl-input label="Gebruikersnaam of Email" type="text" name="username"
            value="<?= e($_POST['username'] ?? '') ?>" autofocus required>
            <i slot="prefix" class="bi bi-person"></i>
          </sl-input>
        </div>
        <div style="margin-bottom:1.5rem;">
          <sl-input label="Wachtwoord" type="password" name="password" required password-toggle>
            <i slot="prefix" class="bi bi-lock"></i>
          </sl-input>
        </div>
        <button type="submit" class="btn-login">Inloggen →</button>
      </form>
      <div class="text-center mt-4">
        <a href="<?= BASE_URL ?>/" style="color:#64748b;font-size:.85rem;text-decoration:none;">
          <i class="bi bi-arrow-left"></i> Terug naar website
        </a>
      </div>
      <?php do_action('admin_login_form_footer'); ?>
    </div>
  </div>
</div>
<?php do_action('admin_login_footer'); ?>
</body>
</html>
