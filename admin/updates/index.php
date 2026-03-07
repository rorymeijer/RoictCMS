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
          <div class="fw-semibold mb-2" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);">
            <i class="bi bi-journal-text me-1"></i>Wijzigingen in v<?= e($updateInfo['latest']) ?>
          </div>
          <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:.75rem 1rem;display:flex;flex-direction:column;gap:.4rem;">
            <?php foreach ($updateInfo['changelog'] as $change): ?>
            <div style="display:flex;align-items:flex-start;gap:.6rem;font-size:.84rem;color:var(--text);">
              <i class="bi bi-check2-circle" style="color:#059669;font-size:.9rem;margin-top:.05rem;flex-shrink:0;"></i>
              <span><?= e($change) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-md-5">
        <div class="d-grid gap-2">
          <?php if ($updateInfo['update_available']): ?>
          <form method="POST" id="update-form">
            <?= csrf_field() ?>
            <sl-button type="submit" name="perform_update" value="1" variant="primary" class="w-100"
              onclick="return handleUpdateClick(this, 'v<?= e($updateInfo['latest']) ?>')">
              <i slot="prefix" class="bi bi-arrow-up-circle"></i>Updaten naar v<?= e($updateInfo['latest']) ?>
            </sl-button>
          </form>
          <?php else: ?>
          <sl-button variant="neutral" outline disabled class="w-100">
            <i slot="prefix" class="bi bi-check-circle"></i>Alles up-to-date
          </sl-button>
          <?php endif; ?>
          <sl-button href="" variant="neutral" outline class="w-100">
            <i slot="prefix" class="bi bi-arrow-clockwise"></i>Opnieuw controleren
          </sl-button>
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
    <sl-button variant="neutral" outline onclick="createBackup(this)">
      <i slot="prefix" class="bi bi-download"></i>Backup nu maken
    </sl-button>
  </div>
</div>

<!-- Installed modules versions -->
<div class="cms-card">
  <div class="cms-card-header">
    <span class="cms-card-title"><i class="bi bi-puzzle me-2"></i>Geïnstalleerde Modules</span>
    <sl-button href="<?= BASE_URL ?>/admin/modules/" size="small" variant="neutral" outline>Beheren</sl-button>
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
      <td><sl-badge variant="<?= $m['status'] === 'active' ? 'primary' : 'danger' ?>" pill><?= $m['status'] === 'active' ? 'Actief' : 'Inactief' ?></sl-badge></td>
      <td class="text-muted"><?= date('d M Y', strtotime($m['installed_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<script>
function createBackup(btn) {
  btn.setAttribute('loading', '');
  setTimeout(() => {
    btn.removeAttribute('loading');
    showToast('success', 'Backup aangemaakt!');
  }, 2000);
}
async function handleUpdateClick(btn, version) {
  const confirmed = await cmsConfirm(
    'CMS updaten naar ' + version + '? Zorg eerst voor een backup!',
    'Updaten'
  );
  if (confirmed) {
    btn.setAttribute('loading', '');
    document.getElementById('update-form').submit();
  }
  return false;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
