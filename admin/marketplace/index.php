<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();
$pageTitle = 'Marketplace';
$activePage = 'marketplace';

// Handle AJAX install/uninstall
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    if (!csrf_verify()) { echo json_encode(['success' => false, 'message' => 'Beveiligingsfout.']); exit; }
    $action = $_POST['action'] ?? '';
    $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
    $type = $_POST['type'] ?? 'module';
    
    if ($action === 'install') {
        if ($type === 'theme') {
            $result = ThemeManager::install($slug, $_POST['download_url'] ?? '');
        } else {
            $result = ModuleManager::install($slug, $_POST['download_url'] ?? '');
        }
        echo json_encode($result);
    } elseif ($action === 'toggle') {
        echo json_encode(ModuleManager::toggle($slug));
    } elseif ($action === 'uninstall') {
        echo json_encode(ModuleManager::uninstall($slug));
    } elseif ($action === 'update') {
        echo json_encode(ModuleManager::update($slug, $_POST['download_url'] ?? ''));
    } elseif ($action === 'update_theme') {
        echo json_encode(ThemeManager::update($slug, $_POST['download_url'] ?? ''));
    } elseif ($action === 'activate_theme') {
        $ok = ThemeManager::activate($slug);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Thema geactiveerd.' : 'Thema niet gevonden.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_upload'])) {
    if (!csrf_verify()) {
        flash('error', 'Beveiligingsfout.');
        redirect(BASE_URL . '/admin/marketplace/?tab=modules');
    }

    if (Settings::get('marketplace_manual_upload', '0') !== '1') {
        flash('error', 'Handmatige uploads zijn uitgeschakeld in de instellingen.');
        redirect(BASE_URL . '/admin/marketplace/?tab=modules');
    }

    $result = ModuleManager::installFromUpload($_FILES['module_zip'] ?? []);
    flash($result['success'] ? 'success' : 'error', $result['message']);
    redirect(BASE_URL . '/admin/marketplace/?tab=modules');
}

$marketplace = ModuleManager::getMarketplace();
// Bouw een lookup van remote versies voor update-vergelijking
$remoteVersions = [];
foreach ($marketplace['modules'] ?? [] as $m) {
    $remoteVersions[$m['slug']] = ['version' => $m['version'] ?? '0', 'download_url' => $m['download_url'] ?? ''];
}
$marketplaceThemes = ThemeManager::getMarketplace();
// Remote thema versies voor update-vergelijking
$remoteThemeVersions = [];
foreach ($marketplaceThemes as $t) {
    $remoteThemeVersions[$t['slug']] = ['version' => $t['version'] ?? '0', 'download_url' => $t['download_url'] ?? ''];
}
$installedModules = array_column(ModuleManager::getInstalled(), null, 'slug');
$availableThemes = array_column(ThemeManager::getAvailable(), null, 'slug');
$activeTheme = ThemeManager::getActive();
$tab = $_GET['tab'] ?? 'modules';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Marketplace</h1>
    <p class="text-muted mb-0" style="font-size:.85rem;">Modules en thema's voor uw CMS</p>
  </div>
</div>

<sl-tab-group id="market-tabs">
  <sl-tab slot="nav" panel="modules" <?= $tab !== 'themes' ? 'active' : '' ?>>
    <i class="bi bi-puzzle me-1"></i> Modules
  </sl-tab>
  <sl-tab slot="nav" panel="themes" <?= $tab === 'themes' ? 'active' : '' ?>>
    <i class="bi bi-palette me-1"></i> Thema's
  </sl-tab>

  <!-- MODULES PANEL -->
  <sl-tab-panel name="modules">
<?php $manualUploadEnabled = Settings::get('marketplace_manual_upload', '0') === '1'; ?>
<?php if ($manualUploadEnabled): ?>
<div class="cms-card mb-4">
  <div class="cms-card-header"><span class="cms-card-title"><i class="bi bi-upload me-2"></i>Handmatige module upload</span></div>
  <div class="cms-card-body">
    <p class="text-muted mb-3" style="font-size:.85rem;">Upload een ZIP met een geldige <code>module.json</code>. Random ZIP-bestanden worden geweigerd.</p>
    <form method="POST" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
      <?= csrf_field() ?>
      <input type="hidden" name="manual_upload" value="1">
      <input type="file" name="module_zip" class="form-control" accept=".zip,application/zip" required style="max-width:360px;">
      <sl-button type="submit" variant="primary">
        <i slot="prefix" class="bi bi-cloud-arrow-up"></i> Upload & installeer
      </sl-button>
    </form>
  </div>
</div>
<?php endif; ?>
<!-- Search & filter bar -->
<div class="d-flex gap-3 align-items-center mb-4 flex-wrap">
  <sl-input id="market-search" type="search" placeholder="Modules zoeken..." style="max-width:300px;"></sl-input>
  <div class="d-flex gap-2 flex-wrap" id="category-filters">
    <sl-button class="filter-btn" variant="primary" size="small" data-cat="all">Alles</sl-button>
    <?php
    $cats = array_unique(array_column($marketplace['modules'] ?? [], 'category'));
    foreach ($cats as $cat): ?>
    <sl-button class="filter-btn" variant="neutral" outline size="small" data-cat="<?= e($cat) ?>"><?= e($cat) ?></sl-button>
    <?php endforeach; ?>
  </div>
</div>

<div class="row g-3" id="modules-grid">
  <?php foreach ($marketplace['modules'] ?? [] as $item): 
    $installed = isset($installedModules[$item['slug']]);
    $isActive = $installed && $installedModules[$item['slug']]['status'] === 'active';
    $iconColors = ['Forms'=>'#2563eb','Media'=>'#7c3aed','SEO'=>'#059669','Marketing'=>'#d97706','Commerce'=>'#dc2626','Analytics'=>'#0891b2'];
    $iconColor = $iconColors[$item['category']] ?? '#6366f1';
    $iconBg = $iconColor . '20';
  ?>
  <div class="col-md-6 col-lg-4 market-item" data-cat="<?= e($item['category']) ?>" data-name="<?= e(strtolower($item['name'])) ?>">
    <div class="market-card" style="height:100%;">
      <div class="d-flex align-items-start gap-3">
        <div class="market-icon" style="background:<?= $iconBg ?>;color:<?= $iconColor ?>;">
          <i class="bi bi-<?= e($item['icon']) ?>"></i>
        </div>
        <div style="flex:1;min-width:0;">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-bold"><?= e($item['name']) ?></span>
            <sl-badge variant="neutral" style="font-size:.65rem;"><?= e($item['category']) ?></sl-badge>
            <?php if (($item['status'] ?? '') === 'alpha'): ?>
            <sl-badge variant="danger" style="font-size:.65rem;">Alpha</sl-badge>
            <?php elseif (($item['status'] ?? '') === 'beta'): ?>
            <sl-badge variant="warning" style="font-size:.65rem;">Beta</sl-badge>
            <?php endif; ?>
          </div>
        </div>
        <div class="market-price <?= $item['price'] === 'free' ? 'free' : 'paid' ?>">
          <?= $item['price'] === 'free' ? 'Gratis' : e($item['price']) ?>
        </div>
      </div>

      <p style="font-size:.82rem;color:var(--text-muted);margin:.5rem 0 0;"><?= e($item['description']) ?></p>
      
      <?php
        $installedVer = $installed ? ($installedModules[$item['slug']]['version'] ?? '0.0.0') : '0.0.0';
        $installedVer = $installedVer ?: '0.0.0'; // null/empty fallback
        $remoteVer    = $remoteVersions[$item['slug']]['version'] ?? '0.0.0';
        $hasUpdate    = $installed && isset($remoteVersions[$item['slug']]) &&
            version_compare($remoteVer, $installedVer, '>');
        $updateUrl = $remoteVersions[$item['slug']]['download_url'] ?? '';
        $installedVersion = $installedModules[$item['slug']]['version'] ?? null;
      ?>
      <div class="d-flex align-items-center justify-content-between mt-auto pt-3" style="border-top:1px solid var(--border);">
        <div>
          <span style="font-size:.75rem;color:var(--text-muted);">v<?= e($item['version']) ?> door <?php if (!empty($item['author_url'])): ?><a href="<?= e($item['author_url']) ?>" target="_blank" rel="noopener" style="color:inherit;"><?= e($item['author']) ?></a><?php else: ?><?= e($item['author']) ?><?php endif; ?></span>
          <?php if ($hasUpdate): ?>
          <span data-update-badge style="display:inline-block;margin-left:.4rem;background:#f59e0b;color:white;border-radius:6px;padding:.1rem .45rem;font-size:.65rem;font-weight:700;">
            ↑ v<?= e($installedVer) ?> → v<?= e($remoteVer) ?>
          </span>
          <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <?php if ($installed): ?>
          <?php if ($hasUpdate): ?>
          <sl-button size="small" variant="warning" onclick="updateModule('<?= e($item['slug']) ?>', '<?= e($updateUrl) ?>', this)" title="Bijwerken naar v<?= e($remoteVersions[$item['slug']]['version']) ?>">
            <i slot="prefix" class="bi bi-arrow-up-circle"></i> Update
          </sl-button>
          <?php endif; ?>
          <sl-badge variant="<?= $isActive ? 'primary' : 'danger' ?>" pill style="font-size:.7rem;"><?= $isActive ? 'Actief' : 'Inactief' ?></sl-badge>
          <sl-button size="small" variant="neutral" outline onclick="toggleModule('<?= e($item['slug']) ?>', this)" title="<?= $isActive ? 'Deactiveren' : 'Activeren' ?>">
            <i class="bi bi-<?= $isActive ? 'pause' : 'play' ?>-fill"></i>
          </sl-button>
          <sl-button size="small" variant="danger" outline onclick="uninstallModule('<?= e($item['slug']) ?>', this)">
            <i class="bi bi-trash"></i>
          </sl-button>
          <?php else: ?>
          <sl-button size="small" variant="primary" class="install-btn" onclick="installItem('<?= e($item['slug']) ?>', 'module', '<?= e($item['download_url'] ?? '') ?>', this)">
            <i slot="prefix" class="bi bi-download"></i> Installeren
          </sl-button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

  </sl-tab-panel>

  <!-- THEMES PANEL -->
  <sl-tab-panel name="themes">
<div class="row g-3">
  <?php foreach ($marketplaceThemes as $theme):
    $isInstalled = isset($availableThemes[$theme['slug']]) || ($theme['included'] ?? false);
    $isActive = $theme['slug'] === $activeTheme;
  ?>
  <div class="col-md-6 col-lg-4">
    <div class="market-card" style="height:100%;">
      <!-- Theme preview placeholder -->
      <div style="height:160px;background:linear-gradient(135deg,#1e293b,#334155);border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center;margin-bottom:.75rem;position:relative;">
        <div style="text-align:center;color:rgba(255,255,255,.6);">
          <i class="bi bi-display" style="font-size:2.5rem;display:block;margin-bottom:.5rem;"></i>
          <span style="font-size:.8rem;"><?= e($theme['name']) ?> Preview</span>
        </div>
        <?php if ($isActive): ?>
        <div style="position:absolute;top:.5rem;right:.5rem;background:#059669;color:white;padding:.2rem .6rem;border-radius:999px;font-size:.7rem;font-weight:700;">
          <i class="bi bi-check-circle me-1"></i>Actief
        </div>
        <?php endif; ?>
      </div>
      
      <div class="d-flex justify-content-between align-items-start mb-1">
        <span class="fw-bold"><?= e($theme['name']) ?></span>
        <span class="market-price <?= ($theme['price'] ?? 'free') === 'free' ? 'free' : 'paid' ?>">
          <?= ($theme['price'] ?? 'free') === 'free' ? 'Gratis' : e($theme['price']) ?>
        </span>
      </div>
      <div class="market-rating mb-1">
        <?= str_repeat('★', round($theme['rating'] ?? 5)) ?><?= str_repeat('☆', 5 - round($theme['rating'] ?? 5)) ?>
        <span style="color:var(--text-muted);font-size:.75rem;"><?= $theme['rating'] ?? '5.0' ?> (<?= number_format($theme['downloads'] ?? 0) ?>)</span>
      </div>
      <p style="font-size:.82rem;color:var(--text-muted);"><?= e($theme['description']) ?></p>
      
      <div class="d-flex gap-2 mt-auto pt-3" style="border-top:1px solid var(--border);">
        <?php if ($isActive): ?>
        <sl-button variant="success" size="small" style="flex:1;" disabled>
          <i slot="prefix" class="bi bi-check-circle"></i> Actief Thema
        </sl-button>
        <?php elseif ($isInstalled): ?>
        <sl-button variant="primary" size="small" style="flex:1;" onclick="activateTheme('<?= e($theme['slug']) ?>', this)">
          <i slot="prefix" class="bi bi-palette"></i> Activeren
        </sl-button>
        <?php elseif (!isset($theme['included'])): ?>
        <sl-button variant="primary" size="small" style="flex:1;" onclick="installItem('<?= e($theme['slug']) ?>', 'theme', '<?= e($theme['download_url'] ?? '') ?>', this)">
          <i slot="prefix" class="bi bi-download"></i> Installeren
        </sl-button>
        <?php else: ?>
        <sl-button variant="neutral" size="small" outline style="flex:1;" disabled>Standaard Thema</sl-button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
  </sl-tab-panel>
</sl-tab-group>

<script>
const CSRF = '<?= csrf_token() ?>';

async function apiCall(data) {
  data.ajax = 1;
  data.csrf_token = CSRF;
  const form = new FormData();
  Object.entries(data).forEach(([k,v]) => form.append(k, v));
  const res = await fetch('', {method:'POST', body: form});
  return res.json();
}

async function installItem(slug, type, url, btn) {
  btn.setAttribute('loading', '');
  const result = await apiCall({action: 'install', slug, type, download_url: url});
  if (result.success) {
    showToast('success', result.message);
    setTimeout(() => location.reload(), 1200);
  } else {
    showToast('error', result.message);
    btn.removeAttribute('loading');
  }
}

async function updateModule(slug, downloadUrl, btn) {
  const confirmed = await cmsConfirm('Module bijwerken? De module wordt tijdelijk gedeactiveerd.', 'Bijwerken');
  if (!confirmed) return;
  btn.setAttribute('loading', '');
  const result = await apiCall({action: 'update', slug, download_url: downloadUrl});
  if (result.success) {
    const card = btn.closest('.market-card') || btn.parentElement;
    card.querySelectorAll('[data-update-badge]').forEach(el => el.remove());
    btn.remove();
    showToast('success', result.message);
  } else {
    btn.removeAttribute('loading');
    showToast('error', result.message);
  }
}

async function toggleModule(slug, btn) {
  btn.setAttribute('loading', '');
  const result = await apiCall({action: 'toggle', slug});
  if (result.success) {
    showToast('success', result.status === 'active' ? 'Module geactiveerd.' : 'Module gedeactiveerd.');
    setTimeout(() => location.reload(), 800);
  } else { btn.removeAttribute('loading'); }
}

async function uninstallModule(slug, btn) {
  const confirmed = await cmsConfirm('Module verwijderen? Dit kan niet ongedaan gemaakt worden.', 'Verwijderen');
  if (!confirmed) return;
  btn.setAttribute('loading', '');
  const result = await apiCall({action: 'uninstall', slug});
  if (result.success) { showToast('success', result.message); setTimeout(() => location.reload(), 800); }
  else { btn.removeAttribute('loading'); showToast('error', result.message); }
}

async function updateTheme(slug, downloadUrl, btn) {
  const confirmed = await cmsConfirm('Thema bijwerken? Het actieve thema blijft actief.', 'Bijwerken');
  if (!confirmed) return;
  btn.setAttribute('loading', '');
  const result = await apiCall({action: 'update_theme', slug, download_url: downloadUrl});
  if (result.success) {
    btn.remove();
    showToast('success', result.message);
  } else {
    btn.removeAttribute('loading');
    showToast('error', result.message);
  }
}

async function activateTheme(slug, btn) {
  btn.setAttribute('loading', '');
  const result = await apiCall({action: 'activate_theme', slug});
  if (result.success) { showToast('success', result.message); setTimeout(() => location.reload(), 800); }
  else { showToast('error', result.message); btn.removeAttribute('loading'); }
}

// Search & filter
const searchInput = document.getElementById('market-search');
const items = document.querySelectorAll('.market-item');
let activeFilter = 'all';

function filterItems() {
  const q = (searchInput?.value || '').toLowerCase();
  items.forEach(item => {
    const matchCat = activeFilter === 'all' || item.dataset.cat === activeFilter;
    const matchSearch = !q || item.dataset.name.includes(q);
    item.style.display = matchCat && matchSearch ? '' : 'none';
  });
}

searchInput?.addEventListener('sl-input', filterItems);
searchInput?.addEventListener('input', filterItems);

document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b => {
      b.variant = 'neutral'; b.outline = true;
    });
    this.variant = 'primary'; this.outline = false;
    activeFilter = this.dataset.cat;
    filterItems();
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
