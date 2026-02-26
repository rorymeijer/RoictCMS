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
    } elseif ($action === 'activate_theme') {
        $ok = ThemeManager::activate($slug);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Thema geactiveerd.' : 'Thema niet gevonden.']);
    }
    exit;
}

$marketplace = ModuleManager::getMarketplace();
$marketplaceThemes = ThemeManager::getMarketplace();
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

<!-- Tabs -->
<div class="mb-4">
  <div style="display:inline-flex;background:white;border:1px solid var(--border);border-radius:12px;padding:4px;gap:4px;">
    <a href="?tab=modules" class="tab-btn <?= $tab === 'modules' ? 'active' : '' ?>" style="padding:.45rem 1.25rem;border-radius:9px;text-decoration:none;font-size:.875rem;font-weight:600;transition:all .15s;<?= $tab === 'modules' ? 'background:var(--primary);color:white;' : 'color:var(--text-muted);' ?>">
      <i class="bi bi-puzzle me-1"></i> Modules
    </a>
    <a href="?tab=themes" class="tab-btn <?= $tab === 'themes' ? 'active' : '' ?>" style="padding:.45rem 1.25rem;border-radius:9px;text-decoration:none;font-size:.875rem;font-weight:600;transition:all .15s;<?= $tab === 'themes' ? 'background:var(--primary);color:white;' : 'color:var(--text-muted);' ?>">
      <i class="bi bi-palette me-1"></i> Thema's
    </a>
  </div>
</div>

<?php if ($tab === 'modules'): ?>
<!-- Search & filter bar -->
<div class="d-flex gap-3 align-items-center mb-4 flex-wrap">
  <input type="search" id="market-search" class="form-control" style="max-width:300px;" placeholder="Modules zoeken...">
  <div class="d-flex gap-2 flex-wrap" id="category-filters">
    <button class="filter-btn active" data-cat="all">Alles</button>
    <?php
    $cats = array_unique(array_column($marketplace['modules'] ?? [], 'category'));
    foreach ($cats as $cat): ?>
    <button class="filter-btn" data-cat="<?= e($cat) ?>"><?= e($cat) ?></button>
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
            <span class="badge bg-secondary" style="font-size:.65rem;"><?= e($item['category']) ?></span>
          </div>
          <div class="market-rating">
            <?= str_repeat('★', round($item['rating'])) ?><?= str_repeat('☆', 5 - round($item['rating'])) ?>
            <span style="color:var(--text-muted);font-size:.75rem;"><?= $item['rating'] ?> (<?= number_format($item['downloads']) ?>)</span>
          </div>
        </div>
        <div class="market-price <?= $item['price'] === 'free' ? 'free' : 'paid' ?>">
          <?= $item['price'] === 'free' ? 'Gratis' : e($item['price']) ?>
        </div>
      </div>
      
      <p style="font-size:.82rem;color:var(--text-muted);margin:.5rem 0 0;"><?= e($item['description']) ?></p>
      
      <div class="d-flex align-items-center justify-content-between mt-auto pt-3" style="border-top:1px solid var(--border);">
        <span style="font-size:.75rem;color:var(--text-muted);">v<?= e($item['version']) ?> door <?= e($item['author']) ?></span>
        <div class="d-flex gap-2 align-items-center">
          <?php if ($installed): ?>
          <span class="badge-status <?= $isActive ? 'badge-active' : 'badge-inactive' ?>" style="font-size:.7rem;"><?= $isActive ? 'Actief' : 'Inactief' ?></span>
          <button class="btn btn-sm btn-outline-secondary" onclick="toggleModule('<?= e($item['slug']) ?>', this)" title="<?= $isActive ? 'Deactiveren' : 'Activeren' ?>">
            <i class="bi bi-<?= $isActive ? 'pause' : 'play' ?>-fill"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger" onclick="uninstallModule('<?= e($item['slug']) ?>', this)">
            <i class="bi bi-trash"></i>
          </button>
          <?php else: ?>
          <button class="btn btn-sm btn-primary install-btn" onclick="installItem('<?= e($item['slug']) ?>', 'module', '<?= e($item['download_url'] ?? '') ?>', this)">
            <i class="bi bi-download me-1"></i> Installeren
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: // THEMES tab ?>
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
        <button class="btn btn-sm btn-success flex-fill" disabled><i class="bi bi-check-circle me-1"></i> Actief Thema</button>
        <?php elseif ($isInstalled): ?>
        <button class="btn btn-sm btn-primary flex-fill" onclick="activateTheme('<?= e($theme['slug']) ?>', this)">
          <i class="bi bi-palette me-1"></i> Activeren
        </button>
        <?php elseif (!isset($theme['included'])): ?>
        <button class="btn btn-sm btn-primary flex-fill" onclick="installItem('<?= e($theme['slug']) ?>', 'theme', '<?= e($theme['download_url'] ?? '') ?>', this)">
          <i class="bi bi-download me-1"></i> Installeren
        </button>
        <?php else: ?>
        <button class="btn btn-sm btn-outline-secondary flex-fill" disabled>Standaard Thema</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.filter-btn { background: white; border: 1.5px solid var(--border); border-radius: 8px; padding: .3rem .9rem; font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .15s; color: var(--text-muted); }
.filter-btn:hover, .filter-btn.active { background: var(--primary); border-color: var(--primary); color: white; }
</style>

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
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Installeren...';
  const result = await apiCall({action: 'install', slug, type, download_url: url});
  if (result.success) {
    showToast('success', result.message);
    setTimeout(() => location.reload(), 1200);
  } else {
    showToast('error', result.message);
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-download me-1"></i> Installeren';
  }
}

async function toggleModule(slug, btn) {
  const result = await apiCall({action: 'toggle', slug});
  if (result.success) {
    showToast('success', result.status === 'active' ? 'Module geactiveerd.' : 'Module gedeactiveerd.');
    setTimeout(() => location.reload(), 800);
  }
}

async function uninstallModule(slug, btn) {
  if (!confirm('Module verwijderen? Dit kan niet ongedaan gemaakt worden.')) return;
  const result = await apiCall({action: 'uninstall', slug});
  if (result.success) { showToast('success', result.message); setTimeout(() => location.reload(), 800); }
}

async function activateTheme(slug, btn) {
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  const result = await apiCall({action: 'activate_theme', slug});
  if (result.success) { showToast('success', result.message); setTimeout(() => location.reload(), 800); }
  else { showToast('error', result.message); btn.disabled = false; btn.innerHTML = '<i class="bi bi-palette me-1"></i> Activeren'; }
}

function showToast(type, msg) {
  const t = document.createElement('div');
  t.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
  t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,.2);border-radius:12px;padding:.75rem 1.25rem;min-width:280px;animation:slideIn .2s ease;';
  t.innerHTML = '<i class="bi bi-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' + msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 3000);
}

// Search & filter
const searchInput = document.getElementById('market-search');
const items = document.querySelectorAll('.market-item');
let activeFilter = 'all';

function filterItems() {
  const q = searchInput?.value.toLowerCase() || '';
  items.forEach(item => {
    const matchCat = activeFilter === 'all' || item.dataset.cat === activeFilter;
    const matchSearch = !q || item.dataset.name.includes(q);
    item.style.display = matchCat && matchSearch ? '' : 'none';
  });
}

searchInput?.addEventListener('input', filterItems);
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    activeFilter = this.dataset.cat;
    filterItems();
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
