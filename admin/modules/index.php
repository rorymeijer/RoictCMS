<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();
$pageTitle = 'Modules';
$activePage = 'modules';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    if (!csrf_verify()) { echo json_encode(['success'=>false,'message'=>'Beveiligingsfout.']); exit; }
    $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') echo json_encode(ModuleManager::toggle($slug));
    elseif ($action === 'uninstall') echo json_encode(ModuleManager::uninstall($slug));
    exit;
}

$modules = ModuleManager::getInstalled();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Modules</h1>
    <p class="text-muted mb-0" style="font-size:.85rem;"><?= count($modules) ?> geïnstalleerde modules</p>
  </div>
  <sl-button href="<?= BASE_URL ?>/admin/marketplace/" variant="primary">
    <i slot="prefix" class="bi bi-shop"></i> Meer modules
  </sl-button>
</div>

<?php if (!$modules): ?>
<div class="cms-card">
  <div class="cms-card-body text-center py-5">
    <i class="bi bi-puzzle" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.3;"></i>
    <h5>Geen modules geïnstalleerd</h5>
    <p class="text-muted">Ga naar de marketplace om modules te installeren.</p>
    <sl-button href="<?= BASE_URL ?>/admin/marketplace/" variant="primary">Ga naar Marketplace</sl-button>
  </div>
</div>
<?php else: ?>
<div class="cms-card">
  <table class="cms-table">
    <thead><tr><th>Module</th><th>Versie</th><th>Status</th><th>Geïnstalleerd</th><th>Acties</th></tr></thead>
    <tbody>
    <?php foreach ($modules as $m): ?>
    <tr>
      <td class="fw-semibold"><?= e($m['name']) ?></td>
      <td><code style="font-size:.78rem;">v<?= e($m['version']) ?></code></td>
      <td id="status-<?= e($m['slug']) ?>">
        <sl-badge variant="<?= $m['status'] === 'active' ? 'primary' : 'danger' ?>" pill>
          <?= $m['status'] === 'active' ? 'Actief' : 'Inactief' ?>
        </sl-badge>
      </td>
      <td class="text-muted"><?= date('d M Y', strtotime($m['installed_at'])) ?></td>
      <td>
        <div class="action-btns">
          <?php if ($m['status'] === 'active' && file_exists(MODULES_PATH . '/' . $m['slug'] . '/admin/index.php')): ?>
          <sl-button href="<?= BASE_URL ?>/modules/<?= e($m['slug']) ?>/admin/" size="small" variant="primary" outline title="Beheer">
            <i class="bi bi-gear"></i>
          </sl-button>
          <?php endif; ?>
          <sl-button onclick="toggleModule('<?= e($m['slug']) ?>', this)" size="small" variant="neutral" outline
            title="<?= $m['status'] === 'active' ? 'Deactiveren' : 'Activeren' ?>">
            <i class="bi bi-<?= $m['status'] === 'active' ? 'pause' : 'play' ?>-fill"></i>
          </sl-button>
          <sl-button onclick="uninstallModule('<?= e($m['slug']) ?>', this)" size="small" variant="danger" outline title="Verwijderen">
            <i class="bi bi-trash"></i>
          </sl-button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script>
const CSRF = '<?= csrf_token() ?>';
async function api(data) {
  data.ajax = 1; data.csrf_token = CSRF;
  const fd = new FormData();
  Object.entries(data).forEach(([k,v]) => fd.append(k,v));
  return (await fetch('', {method:'POST', body:fd})).json();
}
async function toggleModule(slug, btn) {
  if (btn) btn.setAttribute('loading', '');
  const r = await api({action:'toggle', slug});
  if (r.success) setTimeout(() => location.reload(), 600);
  else if (btn) btn.removeAttribute('loading');
}
async function uninstallModule(slug, btn) {
  const confirmed = await cmsConfirm('Module definitief verwijderen?', 'Verwijderen');
  if (!confirmed) return;
  if (btn) btn.setAttribute('loading', '');
  const r = await api({action:'uninstall', slug});
  if (r.success) setTimeout(() => location.reload(), 600);
  else if (btn) btn.removeAttribute('loading');
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
