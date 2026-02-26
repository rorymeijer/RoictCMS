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
  <a href="<?= BASE_URL ?>/admin/marketplace/" class="quick-add-btn"><i class="bi bi-shop me-1"></i> Meer modules</a>
</div>

<?php if (!$modules): ?>
<div class="cms-card">
  <div class="cms-card-body text-center py-5">
    <i class="bi bi-puzzle" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.3;"></i>
    <h5>Geen modules geïnstalleerd</h5>
    <p class="text-muted">Ga naar de marketplace om modules te installeren.</p>
    <a href="<?= BASE_URL ?>/admin/marketplace/" class="btn btn-primary">Ga naar Marketplace</a>
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
        <span class="badge-status badge-<?= $m['status'] ?>"><?= $m['status'] === 'active' ? 'Actief' : 'Inactief' ?></span>
      </td>
      <td class="text-muted"><?= date('d M Y', strtotime($m['installed_at'])) ?></td>
      <td>
        <div class="action-btns">
          <button onclick="toggleModule('<?= e($m['slug']) ?>')" class="btn btn-sm btn-outline-secondary btn-icon" title="<?= $m['status'] === 'active' ? 'Deactiveren' : 'Activeren' ?>">
            <i class="bi bi-<?= $m['status'] === 'active' ? 'pause' : 'play' ?>-fill"></i>
          </button>
          <button onclick="uninstallModule('<?= e($m['slug']) ?>')" class="btn btn-sm btn-outline-danger btn-icon" title="Verwijderen">
            <i class="bi bi-trash"></i>
          </button>
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
async function toggleModule(slug) {
  const r = await api({action:'toggle', slug});
  if (r.success) setTimeout(() => location.reload(), 600);
}
async function uninstallModule(slug) {
  if (!confirm('Module definitief verwijderen?')) return;
  const r = await api({action:'uninstall', slug});
  if (r.success) setTimeout(() => location.reload(), 600);
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
