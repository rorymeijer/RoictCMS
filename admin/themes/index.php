<?php
require_once __DIR__ . '/../includes/init.php';
Auth::requireAdmin();
$pageTitle = "Thema's";
$activePage = 'themes';

if ($_POST['activate'] ?? '') {
    if (ThemeManager::activate($_POST['activate'])) {
        flash('success', 'Thema geactiveerd.');
    }
    redirect(BASE_URL . '/admin/themes/');
}

$themes = ThemeManager::getAvailable();
$activeTheme = ThemeManager::getActive();

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
<?php $isActive = $theme['slug'] === $activeTheme; ?>
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
    </div>
    <div class="cms-card-body">
      <div class="fw-bold mb-1"><?= e($theme['name'] ?? $theme['slug']) ?></div>
      <div class="text-muted mb-1" style="font-size:.8rem;">Versie <?= e($theme['version'] ?? '1.0') ?> · <?= e($theme['author'] ?? 'ROICT') ?></div>
      <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem;"><?= e($theme['description'] ?? '') ?></p>
      <?php if (!$isActive): ?>
      <form method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="activate" value="<?= e($theme['slug']) ?>">
        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-palette me-1"></i> Activeren</button>
      </form>
      <?php else: ?>
      <button class="btn btn-success btn-sm w-100" disabled><i class="bi bi-check-circle me-1"></i> Huidig thema</button>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
