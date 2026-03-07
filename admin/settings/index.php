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

    $allowed = ['site_name','site_tagline','site_email','posts_per_page','date_format','timezone','admin_language','maintenance_mode','maintenance_message','footer_text','homepage_type','homepage_page_id','marketplace_show_released','marketplace_show_beta','marketplace_show_alpha','marketplace_show_dev','marketplace_manual_upload'];
    $data = array_intersect_key($_POST, array_flip($allowed));

    // Ensure homepage_page_id is stored as int
    if (isset($data['homepage_page_id'])) {
        $data['homepage_page_id'] = (int)$data['homepage_page_id'];
    }
    // Synchroniseer site-taal altijd met de gekozen beheertaal
    if (isset($data['admin_language'])) {
        $data['language'] = $data['admin_language'];
    }

    // Checkboxes: als niet aangevinkt stuurt de browser geen waarde mee, expliciet op '0' zetten
    foreach (['maintenance_mode', 'marketplace_show_released', 'marketplace_show_beta', 'marketplace_show_alpha', 'marketplace_show_dev', 'marketplace_manual_upload'] as $cb) {
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
          <sl-input class="mb-3" label="Sitenaam *" name="site_name"
            value="<?= e(Settings::get('site_name', '')) ?>" required></sl-input>
          <sl-input class="mb-3" label="Tagline" name="site_tagline"
            value="<?= e(Settings::get('site_tagline', '')) ?>"></sl-input>
          <sl-input class="mb-3" label="Contact Email" type="email" name="site_email"
            value="<?= e(Settings::get('site_email', '')) ?>"></sl-input>
          <sl-input label="Footer Tekst" name="footer_text"
            value="<?= e(Settings::get('footer_text', '')) ?>"></sl-input>
        </div>
      </div>
      <div class="cms-card">
        <div class="cms-card-header"><span class="cms-card-title">Lees & Weergave</span></div>
        <div class="cms-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <sl-input label="Berichten per pagina" type="number" name="posts_per_page"
                value="<?= e(Settings::get('posts_per_page', '10')) ?>" min="1" max="100"></sl-input>
            </div>
            <div class="col-md-6">
              <sl-input label="Datumformaat" name="date_format"
                value="<?= e(Settings::get('date_format', 'd M Y')) ?>"></sl-input>
            </div>
            <div class="col-md-6">
              <sl-select label="Tijdzone" name="timezone" value="<?= e(Settings::get('timezone', 'Europe/Amsterdam')) ?>">
                <?php foreach (['Europe/Amsterdam','Europe/London','Europe/Berlin','America/New_York','UTC'] as $tz): ?>
                <sl-option value="<?= $tz ?>"><?= $tz ?></sl-option>
                <?php endforeach; ?>
              </sl-select>
            </div>
            <div class="col-md-6">
              <sl-select label="Beheertaal" name="admin_language" value="<?= e(admin_lang()) ?>">
                <?php foreach (admin_available_languages() as $code => $label): ?>
                <sl-option value="<?= e($code) ?>"><?= e($label) ?></sl-option>
                <?php endforeach; ?>
              </sl-select>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">Homepage instelling</span></div>
        <div class="cms-card-body">
          <sl-radio-group class="mb-3" label="Homepage type" name="homepage_type"
            value="<?= e(Settings::get('homepage_type', 'default')) ?>" id="hp-type-group">
            <sl-radio value="default">Standaard homepage</sl-radio>
            <sl-radio value="page">Statische pagina</sl-radio>
          </sl-radio-group>
          <div id="homepage-page-select" <?= Settings::get('homepage_type', 'default') !== 'page' ? 'style="display:none"' : '' ?>>
            <sl-select label="Kies pagina" name="homepage_page_id"
              value="<?= e((int)Settings::get('homepage_page_id', 0)) ?>" placeholder="— Selecteer een pagina —">
              <sl-option value="0">— Selecteer een pagina —</sl-option>
              <?php foreach ($publishedPages as $p): ?>
              <sl-option value="<?= (int)$p['id'] ?>"><?= e($p['title']) ?></sl-option>
              <?php endforeach; ?>
            </sl-select>
            <?php if (empty($publishedPages)): ?>
            <small class="text-muted">Geen gepubliceerde pagina's beschikbaar.</small>
            <?php endif; ?>
          </div>
          <script>
          document.getElementById('hp-type-group').addEventListener('sl-change', function() {
            document.getElementById('homepage-page-select').style.display =
              (this.value === 'page') ? '' : 'none';
          });
          </script>
        </div>
      </div>
      <div class="cms-card mb-3">
        <div class="cms-card-header"><span class="cms-card-title">Marketplace</span></div>
        <div class="cms-card-body">
          <p class="text-muted mb-3" style="font-size:.85rem;">Kies welke modulestatus zichtbaar is in de marketplace.</p>
          <sl-switch class="mb-2" name="marketplace_show_released" value="1"
            <?= Settings::get('marketplace_show_released', '1') !== '0' ? 'checked' : '' ?>>
            Released <sl-badge variant="success" style="margin-left:.4rem;">Stabiel</sl-badge>
          </sl-switch>
          <sl-switch class="mb-2" name="marketplace_show_beta" value="1"
            <?= Settings::get('marketplace_show_beta', '0') == '1' ? 'checked' : '' ?>>
            Beta <sl-badge variant="warning" style="margin-left:.4rem;">Beta</sl-badge>
          </sl-switch>
          <sl-switch class="mb-2" name="marketplace_show_alpha" value="1"
            <?= Settings::get('marketplace_show_alpha', '0') == '1' ? 'checked' : '' ?>>
            Alpha <sl-badge variant="danger" style="margin-left:.4rem;">Alpha</sl-badge>
          </sl-switch>
          <sl-switch class="mb-3" name="marketplace_manual_upload" value="1"
            <?= Settings::get('marketplace_manual_upload', '0') == '1' ? 'checked' : '' ?>>
            Handmatige upload <sl-badge variant="primary" style="margin-left:.4rem;">ZIP</sl-badge>
          </sl-switch>
          <div>
            <sl-button type="submit" name="refresh_marketplace_cache" value="1" variant="neutral" outline size="small">
              <i slot="prefix" class="bi bi-arrow-clockwise"></i> Cache flushen & ZIP opnieuw ophalen
            </sl-button>
            <div class="text-muted mt-1" style="font-size:.78rem;">Forceert direct een nieuwe controle van ZIP-bestanden.</div>
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
          <div class="form-check form-switch mt-2" id="mkt_dev_row" style="display:none;">
            <input class="form-check-input" type="checkbox" name="marketplace_show_dev" value="1" id="mkt_dev" <?= Settings::get('marketplace_show_dev', '0') == '1' ? 'checked' : '' ?>>
            <label class="form-check-label" for="mkt_dev">Dev <span class="badge ms-1" style="background:#6f42c1;">Dev</span></label>
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
          <sl-switch class="mb-3" name="maintenance_mode" value="1"
            <?= Settings::get('maintenance_mode', '') == '1' ? 'checked' : '' ?>>
            <strong>Onderhoudsmodus</strong>
          </sl-switch>
          <sl-textarea label="Onderhoudsbericht" name="maintenance_message" rows="4"
            placeholder="Bijv. We zijn even bezig. Kom later terug!"
            value="<?= e(Settings::get('maintenance_message', '')) ?>"
            help-text="Dit bericht wordt getoond aan bezoekers tijdens de onderhoudsmodus."></sl-textarea>
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
    <sl-button type="submit" variant="primary">
      <i slot="prefix" class="bi bi-check-lg"></i> Instellingen opslaan
    </sl-button>
  </div>
</form>
<script>
(function() {
  var STORAGE_KEY = 'roict_dev_switch_unlocked';
  var devRow = document.getElementById('mkt_dev_row');
  var alphaCheckbox = document.getElementById('mkt_alpha');
  if (!devRow || !alphaCheckbox) return;

  // Als de dev switch al eerder ontgrendeld is, direct tonen
  if (localStorage.getItem(STORAGE_KEY) === '1') {
    devRow.style.display = '';
  }

  var alphaClickCount = 0;

  alphaCheckbox.addEventListener('change', function() {
    // Toon dev switch pas als hij nog verborgen is
    if (localStorage.getItem(STORAGE_KEY) === '1') return;

    alphaClickCount++;
    if (alphaClickCount >= 3) {
      localStorage.setItem(STORAGE_KEY, '1');
      devRow.style.display = '';
      alphaClickCount = 0;
    }
  });
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
