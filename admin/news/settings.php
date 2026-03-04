<?php
require_once dirname(__DIR__, 2) . '/admin/includes/init.php';
Auth::requireAdmin();

$db = Database::getInstance();
$pageTitle = 'Nieuwspagina Instellingen';
$activePage = 'news';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash('error', 'Ongeldige aanvraag.');
        redirect(BASE_URL . '/admin/news/settings.php');
    }
    $title   = trim($_POST['news_page_title'] ?? '');
    $enabled = isset($_POST['news_page_enabled']) ? '1' : '0';

    Settings::set('news_page_title', $title !== '' ? $title : 'Nieuws');
    Settings::set('news_page_enabled', $enabled);

    flash('success', 'Instellingen opgeslagen.');
    redirect(BASE_URL . '/admin/news/settings.php');
}

$newsTitle   = Settings::get('news_page_title', 'Nieuws');
$newsEnabled = Settings::get('news_page_enabled', '1');

require_once ADMIN_PATH . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Nieuwspagina Instellingen</h1>
    <p class="text-muted mb-0" style="font-size:.85rem;">Naam en zichtbaarheid van de nieuwspagina</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/news/" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Terug naar berichten
  </a>
</div>

<?= renderFlash() ?>

<div class="cms-card">
  <div class="cms-card-header"><strong>Configuratie</strong></div>
  <div class="card-body p-4">
    <form method="POST">
      <?= csrf_field() ?>

      <div class="mb-4">
        <label for="news_page_title" class="form-label fw-semibold">Naam van de nieuwspagina</label>
        <input type="text" id="news_page_title" name="news_page_title" class="form-control"
               value="<?= e($newsTitle) ?>" placeholder="Nieuws" required>
        <div class="form-text">Deze naam verschijnt in de navigatie, paginatitel en de pagina zelf.</div>
      </div>

      <div class="mb-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch"
                 id="news_page_enabled" name="news_page_enabled" value="1"
                 <?= $newsEnabled === '1' ? 'checked' : '' ?>>
          <label class="form-check-label fw-semibold" for="news_page_enabled">
            Nieuwspagina inschakelen
          </label>
        </div>
        <div class="form-text mt-1">
          Wanneer uitgeschakeld is de nieuwspagina (<code>/news</code>) niet bereikbaar voor bezoekers
          en wordt de link verborgen in de navigatie en footer.
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Opslaan
      </button>
    </form>
  </div>
</div>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
