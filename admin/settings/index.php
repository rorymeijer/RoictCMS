<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();
$pageTitle = 'Instellingen';
$activePage = 'settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $allowed = ['site_name','site_tagline','site_email','posts_per_page','date_format','timezone','language','maintenance_mode','footer_text'];
    $data = array_intersect_key($_POST, array_flip($allowed));
    Settings::setMultiple($data);
    flash('success', 'Instellingen opgeslagen.');
    redirect(BASE_URL . '/admin/settings/');
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="mb-4">
  <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Instellingen</h1>
</div>
<form method="POST">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-md-8">
      <div class="cms-card mb-4">
        <div class="cms-card-header"><span class="cms-card-title">Algemene Instellingen</span></div>
        <div class="cms-card-body">
          <div class="mb-3"><label class="form-label">Sitenaam *</label><input type="text" class="form-control" name="site_name" value="<?= e(Settings::get('site_name')) ?>" required></div>
          <div class="mb-3"><label class="form-label">Tagline</label><input type="text" class="form-control" name="site_tagline" value="<?= e(Settings::get('site_tagline')) ?>"></div>
          <div class="mb-3"><label class="form-label">Contact Email</label><input type="email" class="form-control" name="site_email" value="<?= e(Settings::get('site_email')) ?>"></div>
          <div class="mb-3"><label class="form-label">Footer Tekst</label><input type="text" class="form-control" name="footer_text" value="<?= e(Settings::get('footer_text')) ?>"></div>
        </div>
      </div>
      <div class="cms-card">
        <div class="cms-card-header"><span class="cms-card-title">Lees & Weergave</span></div>
        <div class="cms-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Berichten per pagina</label>
              <input type="number" class="form-control" name="posts_per_page" value="<?= e(Settings::get('posts_per_page', '10')) ?>" min="1" max="100">
            </div>
            <div class="col-md-6">
              <label class="form-label">Datumformaat</label>
              <input type="text" class="form-control" name="date_format" value="<?= e(Settings::get('date_format', 'd M Y')) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tijdzone</label>
              <select class="form-select" name="timezone">
                <?php foreach (['Europe/Amsterdam','Europe/London','Europe/Berlin','America/New_York','UTC'] as $tz): ?>
                <option value="<?= $tz ?>" <?= Settings::get('timezone') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Taal</label>
              <select class="form-select" name="language">
                <option value="nl" <?= Settings::get('language') === 'nl' ? 'selected' : '' ?>>Nederlands</option>
                <option value="en" <?= Settings::get('language') === 'en' ? 'selected' : '' ?>>English</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">Site Status</span></div>
        <div class="cms-card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" id="maint" <?= Settings::get('maintenance_mode') ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="maint">Onderhoudsmodus</label>
          </div>
          <small class="text-muted">Bezoekers zien een onderhoudspagina.</small>
        </div>
      </div>
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">CMS Info</span></div>
        <div class="cms-card-body">
          <table style="font-size:.82rem;width:100%;">
            <tr><td class="text-muted">Versie</td><td class="text-end fw-semibold"><?= CMS_VERSION ?></td></tr>
            <tr><td class="text-muted">PHP</td><td class="text-end fw-semibold"><?= PHP_VERSION ?></td></tr>
            <tr><td class="text-muted">Database</td><td class="text-end fw-semibold"><?= DB_NAME ?></td></tr>
            <tr><td class="text-muted">Thema</td><td class="text-end fw-semibold"><?= ThemeManager::getActive() ?></td></tr>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Instellingen opslaan</button>
  </div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
