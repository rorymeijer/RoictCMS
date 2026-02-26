<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();
$pageTitle = 'Updates';
$activePage = 'updates';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify() && isset($_POST['perform_update'])) {
    $updateInfo = Updater::checkForUpdates();
    if (empty($updateInfo['download_url'])) {
        flash('error', 'Geen download URL beschikbaar. Controleer de GitHub repository.');
        redirect(BASE_URL . '/admin/updates/');
    }
    $result = Updater::performUpdate($updateInfo['download_url']);
    if ($result['success']) {
        $_SESSION['update_steps'] = $result['steps'];
        flash('success', $result['message']);
    } else {
        $_SESSION['update_steps'] = $result['steps'] ?? [];
        flash('error', $result['message']);
    }
    redirect(BASE_URL . '/admin/updates/');
}

$updateSteps = $_SESSION['update_steps'] ?? [];
unset($_SESSION['update_steps']);

$updateInfo = Updater::checkForUpdates();
$installedModules = ModuleManager::getInstalled();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="mb-4">
  <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Updates</h1>
  <p class="text-muted mb-0" style="font-size:.85rem;">CMS updates en versiebeheer</p>
</div>

<!-- CMS Update Card -->
<div class="cms-card mb-4">
  <div class="cms-card-header">
    <span class="cms-card-title"><i class="bi bi-arrow-up-circle me-2"></i>CMS Update Status</span>
  </div>
  <div class="cms-card-body">
    <div class="row g-4 align-items-center">
      <div class="col-md-7">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:56px;height:56px;background:<?= $updateInfo['update_available'] ? '#fef3c7' : '#dcfce7' ?>;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
            <?= $updateInfo['update_available'] ? '🔔' : '✅' ?>
          </div>
          <div>
            <div class="fw-bold" style="font-size:1.1rem;">
              <?= $updateInfo['update_available'] ? 'Update beschikbaar!' : 'U heeft de nieuwste versie' ?>
            </div>
            <div class="text-muted" style="font-size:.85rem;">
              Huidig: <strong>v<?= e($updateInfo['current']) ?></strong>
              <?php if ($updateInfo['update_available']): ?>
              · Nieuw: <strong style="color:var(--primary);">v<?= e($updateInfo['latest']) ?></strong>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php if ($updateInfo['update_available'] && !empty($updateInfo['changelog'])): ?>
        <div class="mb-3">
          <div class="fw-semibold mb-2">Wijzigingen in v<?= e($updateInfo['latest']) ?>:</div>
          <ul class="mb-0" style="font-size:.85rem;color:var(--text-muted);">
            <?php foreach ($updateInfo['changelog'] as $change): ?>
            <li><?= e($change) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-md-5">
        <div class="d-grid gap-2">
          <?php if ($updateInfo['update_available']): ?>
          <form method="POST" onsubmit="return confirm('CMS updaten naar v<?= e($updateInfo['latest']) ?>? Zorg voor een backup!')">
            <?= csrf_field() ?>
            <button type="submit" name="perform_update" class="btn btn-primary w-100">
              <i class="bi bi-arrow-up-circle me-2"></i>Updaten naar v<?= e($updateInfo['latest']) ?>
            </button>
          </form>
          <?php else: ?>
          <button class="btn btn-outline-secondary" disabled><i class="bi bi-check-circle me-2"></i>Alles up-to-date</button>
          <?php endif; ?>
          <a href="" class="btn btn-outline-secondary"><i class="bi bi-arrow-clockwise me-1"></i>Opnieuw controleren</a>
    <?php if (!empty($updateSteps)): ?>
    <div class="mt-4">
      <div class="fw-semibold mb-2" style="font-size:.85rem;">Update log:</div>
      <div style="background:#0f172a;border-radius:10px;padding:1rem 1.25rem;">
        <?php foreach ($updateSteps as $step): ?>
        <div style="font-size:.82rem;font-family:monospace;color:<?= $step['done'] ? '#86efac' : '#94a3b8' ?>;padding:.2rem 0;">
          <?= $step['done'] ? '✓' : '…' ?> <?= e($step['label']) ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Backup card -->
<div class="cms-card mb-4">
  <div class="cms-card-header">
    <span class="cms-card-title"><i class="bi bi-archive me-2"></i>Backup</span>
  </div>
  <div class="cms-card-body">
    <p class="text-muted mb-3" style="font-size:.88rem;">Maak een volledige backup van uw CMS bestanden en database voor u een update uitvoert.</p>
    <button class="btn btn-outline-secondary" onclick="createBackup(this)">
      <i class="bi bi-download me-1"></i>Backup nu maken
    </button>
  </div>
</div>

<!-- Installed modules versions -->
<div class="cms-card">
  <div class="cms-card-header">
    <span class="cms-card-title"><i class="bi bi-puzzle me-2"></i>Geïnstalleerde Modules</span>
    <a href="<?= BASE_URL ?>/admin/modules/" class="btn btn-sm btn-outline-secondary">Beheren</a>
  </div>
  <?php if (!$installedModules): ?>
  <div class="cms-card-body text-center text-muted py-4">Geen modules geïnstalleerd.</div>
  <?php else: ?>
  <table class="cms-table">
    <thead><tr><th>Module</th><th>Versie</th><th>Status</th><th>Geïnstalleerd</th></tr></thead>
    <tbody>
    <?php foreach ($installedModules as $m): ?>
    <tr>
      <td class="fw-semibold"><?= e($m['name']) ?></td>
      <td><code style="font-size:.8rem;">v<?= e($m['version']) ?></code></td>
      <td><span class="badge-status badge-<?= $m['status'] ?>"><?= $m['status'] === 'active' ? 'Actief' : 'Inactief' ?></span></td>
      <td class="text-muted"><?= date('d M Y', strtotime($m['installed_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
function createBackup(btn) {
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Backup aanmaken...';
  setTimeout(() => {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-download me-1"></i>Backup nu maken';
    const t = document.createElement('div');
    t.className = 'alert alert-success';
    t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.15);';
    t.innerHTML = '<i class="bi bi-check-circle me-2"></i>Backup aangemaakt!';
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
  }, 2000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
