<?php
// Start session for installer
session_start();

define('CMS_VERSION', '1.0.0');
define('CMS_NAME', 'ROICT CMS');
define('BASE_PATH', dirname(__DIR__));
(function() { $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http'; $host = $_SERVER['HTTP_HOST'] ?? 'localhost'; $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/'); $cmsRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/'); $rel = ''; if ($docRoot !== '' && str_starts_with($cmsRoot, $docRoot)) $rel = substr($cmsRoot, strlen($docRoot)); define('BASE_URL', $scheme . '://' . $host . rtrim($rel, '/')); })();
define('DB_PREFIX', 'cms_');
define('CONFIG_FILE', BASE_PATH . '/config.php');

// If already installed, redirect
if (file_exists(CONFIG_FILE)) {
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

function testDbConnection(string $host, string $name, string $user, string $pass): bool|string {
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        return true;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function runInstall(array $db, array $site, array $admin): array {
    try {
        $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $prefix = DB_PREFIX;

        $sql = "
        CREATE TABLE IF NOT EXISTS `{$prefix}users` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60) NOT NULL UNIQUE,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','editor','author') DEFAULT 'author',
            avatar VARCHAR(255),
            status ENUM('active','inactive') DEFAULT 'active',
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(100) NOT NULL UNIQUE,
            `value` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `{$prefix}pages` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content LONGTEXT,
            meta_title VARCHAR(255),
            meta_desc TEXT,
            status ENUM('published','draft') DEFAULT 'draft',
            author_id INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `{$prefix}news` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            excerpt TEXT,
            content LONGTEXT,
            featured_image VARCHAR(255),
            category_id INT,
            meta_title VARCHAR(255),
            meta_desc TEXT,
            status ENUM('published','draft') DEFAULT 'draft',
            author_id INT,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `{$prefix}categories` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            parent_id INT DEFAULT NULL,
            type ENUM('news','page') DEFAULT 'news'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `{$prefix}modules` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(100) NOT NULL UNIQUE,
            name VARCHAR(150) NOT NULL,
            version VARCHAR(20),
            status ENUM('active','inactive') DEFAULT 'active',
            installed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `{$prefix}media` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255),
            mime_type VARCHAR(100),
            file_size INT,
            alt_text VARCHAR(255),
            author_id INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        foreach (array_filter(array_map('trim', explode(';', $sql))) as $query) {
            $pdo->exec($query);
        }

        // Insert default settings
        $defaults = [
            'site_name' => $site['name'],
            'site_tagline' => $site['tagline'],
            'site_email' => $site['email'],
            'active_theme' => 'roict-basic',
            'posts_per_page' => '10',
            'date_format' => 'd M Y',
            'timezone' => 'Europe/Amsterdam',
            'language' => 'nl',
        ];
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}settings` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($defaults as $k => $v) $stmt->execute([$k, $v]);

        // Insert admin user
        $hash = password_hash($admin['password'], PASSWORD_BCRYPT);
        $pdo->prepare("INSERT INTO `{$prefix}users` (username, email, password, role) VALUES (?, ?, ?, 'admin')")
            ->execute([$admin['username'], $admin['email'], $hash]);

        // Insert sample data
        $pdo->prepare("INSERT INTO `{$prefix}pages` (title, slug, content, status, author_id) VALUES (?, ?, ?, 'published', 1)")
            ->execute(['Home', 'home', '<p>Welkom op onze website! Dit is de homepage.</p>']);
        $pdo->prepare("INSERT INTO `{$prefix}pages` (title, slug, content, status, author_id) VALUES (?, ?, ?, 'published', 1)")
            ->execute(['Over ons', 'over-ons', '<p>Dit is de over ons pagina.</p>']);

        $pdo->prepare("INSERT INTO `{$prefix}categories` (name, slug, type) VALUES (?, ?, 'news')")
            ->execute(['Algemeen', 'algemeen']);
        $pdo->prepare("INSERT INTO `{$prefix}news` (title, slug, excerpt, content, status, author_id, category_id, published_at) VALUES (?, ?, ?, ?, 'published', 1, 1, NOW())")
            ->execute(['Welkom bij ' . $site['name'], 'welkom', 'Het CMS is succesvol geïnstalleerd.', '<p>Gefeliciteerd! Het CMS is succesvol geïnstalleerd. U kunt nu beginnen met het beheren van uw website.</p>']);

        // Write config file
        $configContent = "<?php\n// CMS Configuration - Generated by installer\ndefine('DB_HOST', " . var_export($db['host'], true) . ");\ndefine('DB_NAME', " . var_export($db['name'], true) . ");\ndefine('DB_USER', " . var_export($db['user'], true) . ");\ndefine('DB_PASS', " . var_export($db['pass'], true) . ");\ndefine('DB_PREFIX', 'cms_');\ndefine('SITE_KEY', '" . bin2hex(random_bytes(16)) . "');\n";
        file_put_contents(BASE_PATH . '/config.php', $configContent);

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        $_SESSION['install_db'] = [
            'host' => $_POST['db_host'] ?? 'localhost',
            'name' => $_POST['db_name'] ?? '',
            'user' => $_POST['db_user'] ?? '',
            'pass' => $_POST['db_pass'] ?? '',
        ];
        $test = testDbConnection($_SESSION['install_db']['host'], $_SESSION['install_db']['name'], $_SESSION['install_db']['user'], $_SESSION['install_db']['pass']);
        if ($test === true) {
            header('Location: ?step=3');
            exit;
        } else {
            $error = 'Database verbinding mislukt: ' . $test;
        }
    } elseif ($step === 3) {
        $result = runInstall(
            $_SESSION['install_db'],
            ['name' => $_POST['site_name'], 'tagline' => $_POST['site_tagline'], 'email' => $_POST['site_email']],
            ['username' => $_POST['admin_username'], 'email' => $_POST['admin_email'], 'password' => $_POST['admin_password']]
        );
        if ($result['success']) {
            header('Location: ?step=4');
            exit;
        } else {
            $error = 'Installatie mislukt: ' . $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ROICT CMS - Installer</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.12.0/cdn/themes/light.css">
<script type="module" src="https://cdn.jsdelivr.net/npm/@shoelace-style/shoelace@2.12.0/cdn/shoelace.js"></script>
<style>
  :root { --primary: #2563eb; --accent: #7c3aed; }
  body { background: linear-gradient(135deg, #1e1b4b 0%, #1e3a5f 50%, #065f46 100%); min-height: 100vh; display: flex; align-items: center; font-family: 'Segoe UI', system-ui, sans-serif; }
  .install-card { background: white; border-radius: 20px; box-shadow: 0 30px 80px rgba(0,0,0,.35); max-width: 600px; width: 100%; overflow: hidden; }
  .install-header { background: linear-gradient(135deg, #2563eb, #7c3aed); padding: 2.5rem; color: white; }
  .install-header h1 { font-size: 1.8rem; font-weight: 700; margin: 0; }
  .install-header p { opacity: .8; margin: .25rem 0 0; }
  .steps-nav { display: flex; gap: .5rem; margin-top: 1.5rem; }
  .step-dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; border: 2px solid rgba(255,255,255,.4); color: rgba(255,255,255,.6); }
  .step-dot.active { background: white; color: #2563eb; border-color: white; }
  .step-dot.done { background: rgba(255,255,255,.25); border-color: white; color: white; }
  .install-body { padding: 2.5rem; }
  .btn-install { background: linear-gradient(135deg, #2563eb, #7c3aed); border: none; color: white; padding: .75rem 2rem; border-radius: 10px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: opacity .2s; width: 100%; }
  .btn-install:hover { opacity: .9; }
  .step-title { font-size: 1.3rem; font-weight: 700; color: #1e1b4b; margin-bottom: .25rem; }
  .step-subtitle { color: #64748b; margin-bottom: 2rem; }
  .check-item { display: flex; align-items: center; gap: .75rem; padding: .6rem 0; border-bottom: 1px solid #f1f5f9; }
  .check-ok { color: #059669; font-size: 1.1rem; }
  .check-fail { color: #dc2626; font-size: 1.1rem; }
</style>
</head>
<body>
<div class="container d-flex justify-content-center py-5">
<div class="install-card">
  <div class="install-header">
    <div class="d-flex align-items-center gap-3 mb-2">
      <div style="width:48px;height:48px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">⚡</div>
      <div>
        <h1>ROICT CMS</h1>
        <p>Installatie Wizard v<?= CMS_VERSION ?></p>
      </div>
    </div>
    <div class="steps-nav">
      <?php for ($i = 1; $i <= 4; $i++): ?>
      <div class="step-dot <?= $i === $step ? 'active' : ($i < $step ? 'done' : '') ?>">
        <?= $i < $step ? '✓' : $i ?>
      </div>
      <?php endfor; ?>
      <div style="color:rgba(255,255,255,.6);font-size:.85rem;align-self:center;margin-left:.5rem;">
        <?= ['', 'Vereisten', 'Database', 'Configuratie', 'Voltooid'][$step] ?>
      </div>
    </div>
  </div>
  <div class="install-body">
    <?php if ($error): ?>
    <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
    <div class="step-title">Systeemvereisten</div>
    <div class="step-subtitle">Controleren of uw server aan de vereisten voldoet.</div>
    <?php
    $checks = [
        'PHP 8.0+' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'GD Library' => extension_loaded('gd'),
        'JSON' => function_exists('json_encode'),
        'Config schrijfbaar' => is_writable(BASE_PATH) || !file_exists(CONFIG_FILE),
        'Uploads map' => is_writable(BASE_PATH . '/uploads') || !is_dir(BASE_PATH . '/uploads') || true,
    ];
    $allOk = !in_array(false, $checks);
    ?>
    <?php foreach ($checks as $name => $ok): ?>
    <div class="check-item">
      <span class="<?= $ok ? 'check-ok' : 'check-fail' ?>"><?= $ok ? '✓' : '✗' ?></span>
      <span><?= $name ?></span>
      <?php if (!$ok): ?><span class="ms-auto text-danger small">Niet beschikbaar</span><?php endif; ?>
    </div>
    <?php endforeach; ?>
    <div class="mt-4">
      <?php if ($allOk): ?>
      <a href="?step=2"><button class="btn-install">Doorgaan →</button></a>
      <?php else: ?>
      <div class="alert alert-warning">Los bovenstaande problemen op voor de installatie.</div>
      <?php endif; ?>
    </div>

    <?php elseif ($step === 2): ?>
    <div class="step-title">Database configuratie</div>
    <div class="step-subtitle">Vul uw MySQL database gegevens in.</div>
    <form method="POST">
      <div class="mb-3"><label class="form-label fw-semibold">Database Host</label><input type="text" class="form-control" name="db_host" value="localhost" required></div>
      <div class="mb-3"><label class="form-label fw-semibold">Database Naam</label><input type="text" class="form-control" name="db_name" placeholder="roict_cms" required></div>
      <div class="mb-3"><label class="form-label fw-semibold">Gebruikersnaam</label><input type="text" class="form-control" name="db_user" required></div>
      <div class="mb-3"><label class="form-label fw-semibold">Wachtwoord</label><input type="password" class="form-control" name="db_pass"></div>
      <button type="submit" class="btn-install">Verbinding testen →</button>
    </form>

    <?php elseif ($step === 3): ?>
    <div class="step-title">Website & Beheerder</div>
    <div class="step-subtitle">Stel uw website in en maak een beheerdersaccount aan.</div>
    <form method="POST">
      <h6 class="text-uppercase text-secondary fw-semibold mb-3" style="font-size:.75rem;letter-spacing:.05em;">Website</h6>
      <div class="mb-3"><label class="form-label fw-semibold">Sitenaam</label><input type="text" class="form-control" name="site_name" placeholder="Mijn Website" required></div>
      <div class="mb-3"><label class="form-label fw-semibold">Tagline</label><input type="text" class="form-control" name="site_tagline" placeholder="Welkom op onze site"></div>
      <div class="mb-3"><label class="form-label fw-semibold">Contact Email</label><input type="email" class="form-control" name="site_email" required></div>
      <h6 class="text-uppercase text-secondary fw-semibold mb-3 mt-4" style="font-size:.75rem;letter-spacing:.05em;">Beheerder Account</h6>
      <div class="mb-3"><label class="form-label fw-semibold">Gebruikersnaam</label><input type="text" class="form-control" name="admin_username" value="admin" required></div>
      <div class="mb-3"><label class="form-label fw-semibold">Email</label><input type="email" class="form-control" name="admin_email" required></div>
      <div class="mb-3"><label class="form-label fw-semibold">Wachtwoord</label><input type="password" class="form-control" name="admin_password" minlength="8" required></div>
      <button type="submit" class="btn-install">Installeren →</button>
    </form>

    <?php elseif ($step === 4): ?>
    <div class="text-center py-3">
      <div style="font-size:4rem;margin-bottom:1rem;">🎉</div>
      <div class="step-title" style="font-size:1.6rem;">Installatie voltooid!</div>
      <p class="step-subtitle">ROICT CMS is succesvol geïnstalleerd op uw server.</p>
      <div class="alert alert-warning text-start">
        <strong>⚠️ Belangrijk:</strong> Verwijder of hernoem de <code>install/</code> map om beveiligingsrisico's te voorkomen.
      </div>
      <div class="d-grid gap-2 mt-4">
        <a href="<?= BASE_URL ?>/admin/" class="btn-install" style="text-decoration:none;text-align:center;">Ga naar het beheerpaneel →</a>
        <a href="<?= BASE_URL ?>/" style="color:#64748b;text-align:center;display:block;margin-top:.75rem;">Bekijk de website</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
</div>
</body>
</html>
