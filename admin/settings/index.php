<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();
$pageTitle = 'Instellingen';
$activePage = 'settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    if (isset($_POST['refresh_marketplace_cache'])) {
        $refreshed = ModuleManager::refreshMarketplaceCache();
        if ($refreshed) {
            flash('success', 'Marketplace cache geflusht en opnieuw opgehaald.');
        } else {
            flash('error', 'Marketplace cache kon niet worden verwijderd. Controleer bestandsrechten.');
        }
        redirect(BASE_URL . '/admin/settings/');
    }

    $allowed = ['site_name','site_tagline','site_email','posts_per_page','date_format','timezone','language','maintenance_mode','maintenance_message','footer_text','homepage_type','homepage_page_id','marketplace_show_released','marketplace_show_beta','marketplace_show_alpha','marketplace_manual_upload'];
    $data = array_intersect_key($_POST, array_flip($allowed));
    // Ensure homepage_page_id is stored as int
    if (isset($data['homepage_page_id'])) {
        $data['homepage_page_id'] = (int)$data['homepage_page_id'];
    }
    // Checkboxes: als niet aangevinkt stuurt de browser geen waarde mee, expliciet op '0' zetten
    foreach (['maintenance_mode', 'marketplace_show_released', 'marketplace_show_beta', 'marketplace_show_alpha', 'marketplace_manual_upload'] as $cb) {
        if (!isset($data[$cb])) $data[$cb] = '0';
    }
    Settings::setMultiple($data);
    flash('success', 'Instellingen opgeslagen.');
    redirect(BASE_URL . '/admin/settings/');
}

$db = Database::getInstance();
$publishedPages = $db->fetchAll("SELECT id, title FROM `" . DB_PREFIX . "pages` WHERE status = 'published' ORDER BY title ASC");

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
          <div class="mb-3"><label class="form-label">Sitenaam *</label><input type="text" class="form-control" name="site_name" value="<?= e(Settings::get('site_name', '')) ?>" required></div>
          <div class="mb-3"><label class="form-label">Tagline</label><input type="text" class="form-control" name="site_tagline" value="<?= e(Settings::get('site_tagline', '')) ?>"></div>
          <div class="mb-3"><label class="form-label">Contact Email</label><input type="email" class="form-control" name="site_email" value="<?= e(Settings::get('site_email', '')) ?>"></div>
          <div class="mb-3"><label class="form-label">Footer Tekst</label><input type="text" class="form-control" name="footer_text" value="<?= e(Settings::get('footer_text', '')) ?>"></div>
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
                <option value="<?= $tz ?>" <?= Settings::get('timezone', '') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Taal</label>
              <select class="form-select" name="language">
                <option value="nl" <?= Settings::get('language', '') === 'nl' ? 'selected' : '' ?>>Nederlands</option>
                <option value="en" <?= Settings::get('language', '') === 'en' ? 'selected' : '' ?>>English</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">Homepage instelling</span></div>
        <div class="cms-card-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Homepage type</label>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="homepage_type" id="hp_default" value="default"
                <?= Settings::get('homepage_type', 'default') !== 'page' ? 'checked' : '' ?>>
              <label class="form-check-label" for="hp_default">Standaard homepage</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="homepage_type" id="hp_page" value="page"
                <?= Settings::get('homepage_type', 'default') === 'page' ? 'checked' : '' ?>>
              <label class="form-check-label" for="hp_page">Statische pagina</label>
            </div>
          </div>
          <div id="homepage-page-select" <?= Settings::get('homepage_type', 'default') !== 'page' ? 'style="display:none"' : '' ?>>
            <label class="form-label">Kies pagina</label>
            <select class="form-select" name="homepage_page_id">
              <option value="0">— Selecteer een pagina —</option>
              <?php foreach ($publishedPages as $p): ?>
              <option value="<?= (int)$p['id'] ?>" <?= (int)Settings::get('homepage_page_id', 0) === (int)$p['id'] ? 'selected' : '' ?>>
                <?= e($p['title']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($publishedPages)): ?>
            <small class="text-muted">Geen gepubliceerde pagina's beschikbaar.</small>
            <?php endif; ?>
          </div>
          <script>
            (function() {
              var radios = document.querySelectorAll('input[name="homepage_type"]');
              var sel = document.getElementById('homepage-page-select');
              radios.forEach(function(r) {
                r.addEventListener('change', function() {
                  sel.style.display = (this.value === 'page') ? '' : 'none';
                });
              });
            })();
          </script>
        </div>
      </div>
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">Marketplace</span></div>
        <div class="cms-card-body">
          <p class="text-muted mb-2" style="font-size:.85rem;">Kies welke modulestatus zichtbaar is in de marketplace.</p>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="marketplace_show_released" value="1" id="mkt_released" <?= Settings::get('marketplace_show_released', '1') !== '0' ? 'checked' : '' ?>>
            <label class="form-check-label" for="mkt_released">Released <span class="badge bg-success ms-1">Stabiel</span></label>
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="marketplace_show_beta" value="1" id="mkt_beta" <?= Settings::get('marketplace_show_beta', '0') == '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="mkt_beta">Beta <span class="badge bg-warning text-dark ms-1">Beta</span></label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="marketplace_show_alpha" value="1" id="mkt_alpha" <?= Settings::get('marketplace_show_alpha', '0') == '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="mkt_alpha">Alpha <span class="badge bg-danger ms-1">Alpha</span></label>
          </div>
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="marketplace_manual_upload" value="1" id="mkt_manual_upload" <?= Settings::get('marketplace_manual_upload', '0') == '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="mkt_manual_upload">Manuale upload <span class="badge bg-info text-dark ms-1">ZIP</span></label>
          </div>
          <div class="mt-3">
            <button type="submit" name="refresh_marketplace_cache" value="1" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-arrow-clockwise me-1"></i> Cache flushen & ZIP opnieuw ophalen
            </button>
            <div class="text-muted" style="font-size:.78rem;">Forceert direct een nieuwe controle van ZIP-bestanden in de module marketplace.</div>
          </div>
        </div>
      </div>
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">Site Status</span></div>
        <div class="cms-card-body">
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" id="maint" <?= Settings::get('maintenance_mode', '') == '1' ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="maint">Onderhoudsmodus</label>
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold" for="maintenance_message">Onderhoudsbericht</label>
            <textarea class="form-control" name="maintenance_message" id="maintenance_message" rows="4" placeholder="Bijv. We zijn even bezig. Kom later terug!"><?= e(Settings::get('maintenance_message', '')) ?></textarea>
            <small class="text-muted">Dit bericht wordt getoond aan bezoekers tijdens de onderhoudsmodus.</small>
          </div>
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
