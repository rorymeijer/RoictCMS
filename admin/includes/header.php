<!DOCTYPE html>
<html lang="nl" data-bs-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= e(Settings::get('site_name', 'ROICT CMS')) ?> Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.12.0/cdn/themes/light.css">
<script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.12.0/cdn/shoelace.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
:root {
  --sidebar-w: 260px;
  --topbar-h: 62px;
  --primary: #2563eb;
  --primary-dark: #1d4ed8;
  --sidebar-bg: #0f172a;
  --sidebar-text: #94a3b8;
  --sidebar-hover: rgba(255,255,255,.06);
  --sidebar-active-bg: rgba(37,99,235,.18);
  --sidebar-active-text: #60a5fa;
  --accent: #7c3aed;
  --surface: #f8fafc;
  --card-bg: #ffffff;
  --border: #e2e8f0;
  --text: #0f172a;
  --text-muted: #64748b;
}
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; background: var(--surface); color: var(--text); min-height: 100vh; }

/* Sidebar */
#sidebar {
  position: fixed; top: 0; left: 0; width: var(--sidebar-w); height: 100vh;
  background: var(--sidebar-bg); display: flex; flex-direction: column;
  z-index: 300; transition: transform .3s; overflow-y: auto; overflow-x: hidden;
}
#sidebar .sidebar-logo {
  padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: .75rem;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
#sidebar .sidebar-logo .logo-icon {
  width: 38px; height: 38px; background: linear-gradient(135deg, var(--primary), var(--accent));
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; color: white; flex-shrink: 0;
}
#sidebar .sidebar-logo .logo-text { font-size: .95rem; font-weight: 700; color: white; line-height: 1.2; }
#sidebar .sidebar-logo .logo-version { font-size: .7rem; color: var(--sidebar-text); }

#sidebar nav { padding: 1rem 0; flex: 1; }
#sidebar .nav-section { padding: .25rem 1.25rem; font-size: .68rem; text-transform: uppercase;
  letter-spacing: .08em; color: #475569; font-weight: 600; margin-top: .75rem; }
#sidebar .nav-link {
  display: flex; align-items: center; gap: .7rem; padding: .55rem 1.25rem;
  color: var(--sidebar-text); text-decoration: none; font-size: .88rem; font-weight: 500;
  border-radius: 0; transition: all .15s; position: relative;
}
#sidebar .nav-link:hover { background: var(--sidebar-hover); color: #e2e8f0; }
#sidebar .nav-link.active { background: var(--sidebar-active-bg); color: var(--sidebar-active-text); }
#sidebar .nav-link.active::before {
  content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
  background: var(--primary); border-radius: 0 2px 2px 0;
}
#sidebar .nav-link i { font-size: 1rem; width: 18px; text-align: center; flex-shrink: 0; }
#sidebar .nav-badge {
  margin-left: auto; background: var(--primary); color: white;
  font-size: .65rem; padding: .15rem .45rem; border-radius: 999px; font-weight: 700;
}
#sidebar .sidebar-footer {
  padding: 1rem 1.25rem; border-top: 1px solid rgba(255,255,255,.06);
}
#sidebar .user-card {
  display: flex; align-items: center; gap: .6rem; padding: .5rem .75rem;
  background: rgba(255,255,255,.05); border-radius: 10px; cursor: pointer;
  transition: background .15s;
}
#sidebar .user-card:hover { background: rgba(255,255,255,.09); }
#sidebar .user-avatar {
  width: 32px; height: 32px; border-radius: 8px;
  background: linear-gradient(135deg, var(--primary), var(--accent));
  display: flex; align-items: center; justify-content: center; font-size: .85rem;
  font-weight: 700; color: white; flex-shrink: 0;
}
#sidebar .user-info .name { font-size: .82rem; font-weight: 600; color: #e2e8f0; }
#sidebar .user-info .role { font-size: .7rem; color: var(--sidebar-text); }

/* Main layout */
#main-wrap { margin-left: var(--sidebar-w); display: flex; flex-direction: column; min-height: 100vh; }

/* Topbar */
#topbar {
  height: var(--topbar-h); background: var(--card-bg); border-bottom: 1px solid var(--border);
  display: flex; align-items: center; padding: 0 1.75rem; gap: 1rem; position: sticky; top: 0; z-index: 400;
}
#topbar .page-title { font-size: 1rem; font-weight: 700; color: var(--text); }
#topbar .topbar-actions { margin-left: auto; display: flex; align-items: center; gap: .5rem; }
.topbar-icon-btn {
  width: 36px; height: 36px; border-radius: 9px; border: 1px solid var(--border);
  background: transparent; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .15s; color: var(--text-muted); text-decoration: none;
}
.topbar-icon-btn:hover { background: var(--surface); color: var(--primary); border-color: var(--primary); }
.quick-add-btn {
  background: var(--primary); color: white; border: none; padding: .45rem 1rem;
  border-radius: 9px; font-size: .85rem; font-weight: 600; cursor: pointer;
  display: flex; align-items: center; gap: .4rem; text-decoration: none; transition: background .15s;
}
.quick-add-btn:hover { background: var(--primary-dark); color: white; }

/* Content */
#content { padding: 1.75rem; flex: 1; }

/* Cards */
.cms-card {
  background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px;
  overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.cms-card-header {
  padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
}
.cms-card-title { font-size: .95rem; font-weight: 700; color: var(--text); margin: 0; }
.cms-card-body { padding: 1.5rem; }

/* Stats cards */
.stat-card {
  background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px;
  padding: 1.25rem 1.5rem; display: flex; align-items: flex-start; gap: 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.stat-icon {
  width: 44px; height: 44px; border-radius: 11px; display: flex; align-items: center;
  justify-content: center; font-size: 1.2rem; flex-shrink: 0;
}
.stat-value { font-size: 1.6rem; font-weight: 800; line-height: 1; color: var(--text); }
.stat-label { font-size: .8rem; color: var(--text-muted); margin-top: .15rem; }

/* Tables */
.cms-table { width: 100%; border-collapse: collapse; }
.cms-table th { padding: .75rem 1rem; font-size: .75rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .05em; color: var(--text-muted); border-bottom: 2px solid var(--border); text-align: left; }
.cms-table td { padding: .8rem 1rem; border-bottom: 1px solid var(--border); font-size: .88rem; vertical-align: middle; }
.cms-table tr:last-child td { border-bottom: none; }
.cms-table tbody tr:hover { background: #f8fafc; }

/* Badges */
.badge-status { padding: .25rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
.badge-published { background: #dcfce7; color: #166534; }
.badge-draft { background: #f1f5f9; color: #475569; }
.badge-active { background: #dbeafe; color: #1e40af; }
.badge-inactive { background: #fee2e2; color: #991b1b; }

/* Forms */
.form-label { font-size: .85rem; font-weight: 600; color: var(--text); margin-bottom: .35rem; }
.form-control, .form-select {
  border: 1.5px solid var(--border); border-radius: 9px; padding: .55rem .85rem;
  font-size: .88rem; transition: border-color .15s, box-shadow .15s; background: white;
}
.form-control:focus, .form-select:focus {
  border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,.1); outline: none;
}

/* Buttons */
.btn-primary { background: var(--primary) !important; border-color: var(--primary) !important; border-radius: 9px !important; font-weight: 600 !important; }
.btn-primary:hover { background: var(--primary-dark) !important; border-color: var(--primary-dark) !important; }
.btn-outline-secondary { border-radius: 9px !important; }
.btn-sm { padding: .3rem .7rem !important; font-size: .8rem !important; }
.btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0 !important; border-radius: 8px !important; }

/* Action buttons row */
.action-btns { display: flex; gap: .35rem; }

/* Sidebar toggle for mobile */
/* Hamburger alleen op mobiel */
.sidebar-toggle-btn { display: none; }

@media (max-width: 768px) {
  .sidebar-toggle-btn { display: flex; }
  #sidebar { transform: translateX(-100%); transition: transform .28s cubic-bezier(.4,0,.2,1); }
  #sidebar.open { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,.35); }
  #main-wrap { margin-left: 0; }
  #sidebar-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 299;
  }
  #sidebar-overlay.show { display: block; }
}

/* Misc */
.text-muted { color: var(--text-muted) !important; }
.separator { height: 1px; background: var(--border); margin: .5rem 0; }

/* Marketplace */
.market-card {
  background: var(--card-bg); border: 1.5px solid var(--border); border-radius: 14px;
  padding: 1.25rem; display: flex; flex-direction: column; gap: .5rem; transition: border-color .2s, box-shadow .2s;
}
.market-card:hover { border-color: var(--primary); box-shadow: 0 4px 20px rgba(37,99,235,.1); }
.market-icon {
  width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; flex-shrink: 0;
}
.market-rating { color: #f59e0b; font-size: .8rem; }
.market-price { font-size: .8rem; font-weight: 700; }
.market-price.free { color: #059669; }
.market-price.paid { color: var(--primary); }
</style>
</head>
<body>

<!-- Sidebar -->
<aside id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">⚡</div>
    <div>
      <div class="logo-text"><?= e(Settings::get('site_name', 'ROICT CMS')) ?></div>
      <div class="logo-version">v<?= Updater::currentVersion() ?> Admin</div>
    </div>
  </div>
  <nav>
    <a href="<?= BASE_URL ?>/admin/" class="nav-link <?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-grid-1x2-fill"></i> Dashboard
    </a>

    <div class="nav-section">Inhoud</div>
    <a href="<?= BASE_URL ?>/admin/pages/" class="nav-link <?= ($activePage ?? '') === 'pages' ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-text"></i> Pagina's
    </a>
    <a href="<?= BASE_URL ?>/admin/news/" class="nav-link <?= ($activePage ?? '') === 'news' ? 'active' : '' ?>">
      <i class="bi bi-newspaper"></i> Nieuws
    </a>
    <a href="<?= BASE_URL ?>/admin/media/" class="nav-link <?= ($activePage ?? '') === 'media' ? 'active' : '' ?>">
      <i class="bi bi-images"></i> Media
    </a>

    <div class="nav-section">Beheer</div>
    <a href="<?= BASE_URL ?>/admin/users/" class="nav-link <?= ($activePage ?? '') === 'users' ? 'active' : '' ?>">
      <i class="bi bi-people"></i> Gebruikers
    </a>
    <a href="<?= BASE_URL ?>/admin/themes/" class="nav-link <?= ($activePage ?? '') === 'themes' ? 'active' : '' ?>">
      <i class="bi bi-palette"></i> Thema's
    </a>

    <div class="nav-section">Uitbreidingen</div>
    <a href="<?= BASE_URL ?>/admin/modules/" class="nav-link <?= ($activePage ?? '') === 'modules' ? 'active' : '' ?>">
      <i class="bi bi-puzzle"></i> Modules
    </a>
    <a href="<?= BASE_URL ?>/admin/marketplace/" class="nav-link <?= ($activePage ?? '') === 'marketplace' ? 'active' : '' ?>">
      <i class="bi bi-shop"></i> Marketplace
    </a>

    <div class="separator mx-3 my-2"></div>
    <a href="<?= BASE_URL ?>/admin/settings/" class="nav-link <?= ($activePage ?? '') === 'settings' ? 'active' : '' ?>">
      <i class="bi bi-gear"></i> Instellingen
    </a>
    <a href="<?= BASE_URL ?>/admin/updates/" class="nav-link <?= ($activePage ?? '') === 'updates' ? 'active' : '' ?>">
      <i class="bi bi-arrow-up-circle"></i> Updates
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-card" onclick="window.location='<?= BASE_URL ?>/admin/profile.php'">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?></div>
      <div class="user-info">
        <div class="name"><?= e($_SESSION['user_name'] ?? 'Admin') ?></div>
        <div class="role"><?= ucfirst($_SESSION['user_role'] ?? 'admin') ?></div>
      </div>
      <i class="bi bi-three-dots-vertical ms-auto text-secondary" style="font-size:.9rem;"></i>
    </div>
  </div>
</aside>
<div id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- Main -->
<div id="main-wrap">
  <div id="topbar">
    <button class="topbar-icon-btn sidebar-toggle-btn" onclick="toggleSidebar()">
      <i class="bi bi-list"></i>
    </button>
    <span class="page-title"><?= isset($pageTitle) ? e($pageTitle) : 'Dashboard' ?></span>
    <div class="topbar-actions">
      <a href="<?= BASE_URL ?>/" target="_blank" class="topbar-icon-btn" title="Bekijk website">
        <i class="bi bi-box-arrow-up-right"></i>
      </a>
      <a href="<?= BASE_URL ?>/admin/settings/" class="topbar-icon-btn">
        <i class="bi bi-gear"></i>
      </a>
      <a href="<?= BASE_URL ?>/admin/logout.php" class="topbar-icon-btn" title="Uitloggen">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>
  </div>
  <div id="content">
    <?= renderFlash() ?>
<script>
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  sidebar.classList.toggle('open');
  overlay.classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('show');
}
// Sluit sidebar bij nav-link klik op mobiel
document.addEventListener('DOMContentLoaded', function() {
  if (window.innerWidth <= 768) {
    document.querySelectorAll('#sidebar .nav-link').forEach(function(link) {
      link.addEventListener('click', closeSidebar);
    });
  }
});
</script>
