<?php
require_once __DIR__ . '/includes/init.php';
$db = Database::getInstance();
$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$stats = [
    'pages' => $db->fetch("SELECT COUNT(*) as c FROM `" . DB_PREFIX . "pages`")['c'] ?? 0,
    'news' => $db->fetch("SELECT COUNT(*) as c FROM `" . DB_PREFIX . "news`")['c'] ?? 0,
    'users' => $db->fetch("SELECT COUNT(*) as c FROM `" . DB_PREFIX . "users`")['c'] ?? 0,
    'modules' => $db->fetch("SELECT COUNT(*) as c FROM `" . DB_PREFIX . "modules` WHERE status = 'active'")['c'] ?? 0,
];
$recentNews = $db->fetchAll("SELECT n.*, u.username FROM `" . DB_PREFIX . "news` n LEFT JOIN `" . DB_PREFIX . "users` u ON n.author_id = u.id ORDER BY n.created_at DESC LIMIT 5");
$recentUsers = $db->fetchAll("SELECT * FROM `" . DB_PREFIX . "users` ORDER BY created_at DESC LIMIT 5");

require_once __DIR__ . '/includes/header.php';
?>
<!-- Stats -->
<div class="row g-3 mb-4">
  <?php
  $statItems = [
    ['value' => $stats['pages'], 'label' => "Pagina's", 'icon' => 'bi-file-earmark-text', 'color' => '#dbeafe', 'ic' => '#2563eb'],
    ['value' => $stats['news'], 'label' => 'Nieuwsberichten', 'icon' => 'bi-newspaper', 'color' => '#dcfce7', 'ic' => '#059669'],
    ['value' => $stats['users'], 'label' => 'Gebruikers', 'icon' => 'bi-people', 'color' => '#fef3c7', 'ic' => '#d97706'],
    ['value' => $stats['modules'], 'label' => 'Actieve Modules', 'icon' => 'bi-puzzle', 'color' => '#f3e8ff', 'ic' => '#7c3aed'],
  ];
  foreach ($statItems as $s): ?>
  <div class="col-6 col-md-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:<?= $s['color'] ?>;color:<?= $s['ic'] ?>;">
        <i class="bi <?= $s['icon'] ?>"></i>
      </div>
      <div>
        <div class="stat-value"><?= $s['value'] ?></div>
        <div class="stat-label"><?= $s['label'] ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Quick actions -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="cms-card">
      <div class="cms-card-body py-3">
        <div class="d-flex gap-2 flex-wrap">
          <a href="<?= BASE_URL ?>/admin/pages/add.php" class="quick-add-btn"><i class="bi bi-plus-lg"></i> Nieuwe Pagina</a>
          <a href="<?= BASE_URL ?>/admin/news/add.php" class="quick-add-btn" style="background:#059669"><i class="bi bi-plus-lg"></i> Nieuwsbericht</a>
          <a href="<?= BASE_URL ?>/admin/users/add.php" class="quick-add-btn" style="background:#d97706"><i class="bi bi-plus-lg"></i> Gebruiker</a>
          <a href="<?= BASE_URL ?>/admin/marketplace/" class="quick-add-btn" style="background:#7c3aed"><i class="bi bi-shop"></i> Marketplace</a>
          <a href="<?= BASE_URL ?>/admin/media/" class="quick-add-btn" style="background:#0891b2"><i class="bi bi-upload"></i> Media Upload</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Recent news -->
  <div class="col-md-7">
    <div class="cms-card">
      <div class="cms-card-header">
        <span class="cms-card-title"><i class="bi bi-newspaper me-2"></i>Recente berichten</span>
        <a href="<?= BASE_URL ?>/admin/news/" class="btn btn-sm btn-outline-secondary">Alles bekijken</a>
      </div>
      <table class="cms-table">
        <thead><tr><th>Titel</th><th>Auteur</th><th>Status</th><th>Datum</th></tr></thead>
        <tbody>
          <?php if (!$recentNews): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Nog geen berichten</td></tr>
          <?php else: foreach ($recentNews as $n): ?>
          <tr>
            <td><a href="<?= BASE_URL ?>/admin/news/edit.php?id=<?= $n['id'] ?>" class="text-decoration-none fw-semibold"><?= e($n['title']) ?></a></td>
            <td class="text-muted"><?= e($n['username'] ?? '—') ?></td>
            <td><span class="badge-status badge-<?= $n['status'] ?>"><?= ucfirst($n['status']) ?></span></td>
            <td class="text-muted"><?= date('d M Y', strtotime($n['created_at'])) ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Recent users -->
  <div class="col-md-5">
    <div class="cms-card">
      <div class="cms-card-header">
        <span class="cms-card-title"><i class="bi bi-people me-2"></i>Gebruikers</span>
        <a href="<?= BASE_URL ?>/admin/users/" class="btn btn-sm btn-outline-secondary">Beheren</a>
      </div>
      <div class="cms-card-body p-0">
        <?php foreach ($recentUsers as $u): ?>
        <div class="d-flex align-items-center gap-3 px-4 py-3" style="border-bottom:1px solid var(--border);">
          <div class="user-avatar" style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;color:white;flex-shrink:0;">
            <?= strtoupper(substr($u['username'], 0, 1)) ?>
          </div>
          <div>
            <div class="fw-semibold" style="font-size:.88rem;"><?= e($u['username']) ?></div>
            <div class="text-muted" style="font-size:.75rem;"><?= e($u['email']) ?></div>
          </div>
          <span class="ms-auto badge-status badge-<?= $u['status'] ?>"><?= ucfirst($u['role']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
