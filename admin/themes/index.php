<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();
$pageTitle = "Thema's";
$activePage = 'themes';

// AJAX handler voor thema-updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    if (!csrf_verify()) { echo json_encode(['success' => false, 'message' => 'Beveiligingsfout.']); exit; }
    $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
    if (($_POST['action'] ?? '') === 'update_theme') {
        echo json_encode(ThemeManager::update($slug, $_POST['download_url'] ?? ''));
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Ongeldige aanvraag.');
        redirect(BASE_URL . '/admin/themes/');
    }
    if ($_POST['activate'] ?? '') {
        if (ThemeManager::activate($_POST['activate'])) {
            flash('success', 'Thema geactiveerd.');
        }
    } elseif ($_POST['delete'] ?? '') {
        $result = ThemeManager::delete($_POST['delete']);
        flash($result['success'] ? 'success' : 'error', $result['message']);
    }
    redirect(BASE_URL . '/admin/themes/');
}

$themes = ThemeManager::getAvailable();
$activeTheme = ThemeManager::getActive();

// Haal marketplace versies op voor update-vergelijking
$marketplaceThemes = ThemeManager::getMarketplace();
$remoteThemeVersions = [];
foreach ($marketplaceThemes as $t) {
    if (!empty($t['download_url'])) {
        $remoteThemeVersions[$t['slug']] = [
            'version'      => $t['version'] ?? '0',
            'download_url' => $t['download_url'],
        ];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Thema's</h1>
    <p class="text-muted mb-0" style="font-size:.85rem;">Geïnstalleerde thema's — <?= count($themes) ?> beschikbaar</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/marketplace/?tab=themes" class="quick-add-btn"><i class="bi bi-shop me-1"></i> Meer thema's</a>
</div>

<div class="row g-3">
<?php foreach ($themes as $theme): ?>
<?php
  $isActive  = $theme['slug'] === $activeTheme;
  $isLocked  = !empty($theme['locked']);
  $localVer  = $theme['version'] ?? '1.0';
  $remoteInfo = $remoteThemeVersions[$theme['slug']] ?? null;
  $remoteVer  = $remoteInfo['version'] ?? '0';
  $hasUpdate  = !$isLocked && $remoteInfo && version_compare($remoteVer, $localVer, '>');
  $updateUrl  = $remoteInfo['download_url'] ?? '';
?>
<div class="col-md-6 col-lg-4">
  <div class="cms-card" style="<?= $isActive ? 'border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1);' : '' ?>">
    <div style="height:160px;background:linear-gradient(135deg,#1e293b,#475569);display:flex;align-items:center;justify-content:center;position:relative;border-radius:14px 14px 0 0;">
      <div style="text-align:center;color:rgba(255,255,255,.5);">
        <i class="bi bi-display" style="font-size:3rem;display:block;margin-bottom:.5rem;"></i>
        <span style="font-size:.85rem;"><?= e($theme['name'] ?? $theme['slug']) ?></span>
      </div>
      <?php if ($isActive): ?>
      <div style="position:absolute;top:.75rem;right:.75rem;background:#059669;color:white;padding:.25rem .75rem;border-radius:999px;font-size:.75rem;font-weight:700;">
        <i class="bi bi-check-circle me-1"></i> Actief
      </div>
      <?php endif; ?>
      <?php if ($hasUpdate): ?>
      <div style="position:absolute;top:.75rem;left:.75rem;background:#f59e0b;color:white;padding:.25rem .6rem;border-radius:999px;font-size:.7rem;font-weight:700;">
        <i class="bi bi-arrow-up-circle me-1"></i> v<?= e($remoteVer) ?>
      </div>
      <?php endif; ?>
      <?php if ($isLocked): ?>
      <div style="position:absolute;top:.75rem;left:.75rem;background:#64748b;color:white;padding:.25rem .6rem;border-radius:999px;font-size:.7rem;font-weight:700;" title="Dit thema is beveiligd en kan alleen via het CMS worden bijgewerkt.">
        <i class="bi bi-lock me-1"></i> Beveiligd
      </div>
      <?php endif; ?>
    </div>
    <div class="cms-card-body">
      <div class="fw-bold mb-1"><?= e($theme['name'] ?? $theme['slug']) ?></div>
      <div class="text-muted mb-1" style="font-size:.8rem;">Versie <?= e($localVer) ?> · <?= e($theme['author'] ?? 'ROICT') ?></div>
      <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem;"><?= e($theme['description'] ?? '') ?></p>
      <?php if (!$isActive): ?>
      <div class="d-flex gap-2">
        <form method="POST" class="flex-grow-1">
          <?= csrf_field() ?>
          <input type="hidden" name="activate" value="<?= e($theme['slug']) ?>">
          <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-palette me-1"></i> Activeren</button>
        </form>
        <?php if ($hasUpdate): ?>
        <button class="btn btn-warning btn-sm" onclick="updateTheme('<?= e($theme['slug']) ?>', '<?= e($updateUrl) ?>', this)" title="Bijwerken naar v<?= e($remoteVer) ?>">
          <i class="bi bi-arrow-up-circle"></i>
        </button>
        <?php endif; ?>
        <?php if (!$isLocked): ?>
        <form method="POST" onsubmit="return confirm('Weet je zeker dat je het thema \"<?= e($theme['name'] ?? $theme['slug']) ?>\" wilt verwijderen?');">
          <?= csrf_field() ?>
          <input type="hidden" name="delete" value="<?= e($theme['slug']) ?>">
          <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
        </form>
        <?php else: ?>
        <button class="btn btn-secondary btn-sm" disabled title="Dit thema is beveiligd en kan niet worden verwijderd."><i class="bi bi-lock"></i></button>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="d-flex gap-2">
        <button class="btn btn-success btn-sm flex-grow-1" disabled><i class="bi bi-check-circle me-1"></i> Huidig thema</button>
        <?php if ($hasUpdate): ?>
        <button class="btn btn-warning btn-sm" onclick="updateTheme('<?= e($theme['slug']) ?>', '<?= e($updateUrl) ?>', this)" title="Bijwerken naar v<?= e($remoteVer) ?>">
          <i class="bi bi-arrow-up-circle"></i>
        </button>
        <?php endif; ?>
        <?php if ($isLocked): ?>
        <button class="btn btn-secondary btn-sm" disabled title="Dit thema is beveiligd en kan alleen via het CMS worden bijgewerkt."><i class="bi bi-lock"></i></button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>

<script>
const CSRF_T = '<?= csrf_token() ?>';
async function updateTheme(slug, downloadUrl, btn) {
  if (!confirm('Thema bijwerken naar de nieuwste versie?')) return;
  const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  btn.disabled = true;
  const fd = new FormData();
  fd.append('ajax', '1'); fd.append('csrf_token', CSRF_T);
  fd.append('action', 'update_theme'); fd.append('slug', slug);
  fd.append('download_url', downloadUrl);
  try {
    const r = await (await fetch('', {method:'POST', body:fd})).json();
    if (r.success) {
      btn.remove();
      const t = document.createElement('div');
      t.className = 'alert alert-success';
      t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;box-shadow:0 8px 30px rgba(0,0,0,.2);border-radius:12px;padding:.75rem 1.25rem;min-width:280px;';
      t.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + r.message;
      document.body.appendChild(t);
      setTimeout(() => { t.remove(); location.reload(); }, 2500);
    } else {
      btn.innerHTML = orig; btn.disabled = false; alert(r.message);
    }
  } catch(e) { btn.innerHTML = orig; btn.disabled = false; }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
