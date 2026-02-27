<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="logo"><?= e(Settings::get('site_name')) ?></div>
        <p><?= e(Settings::get('site_tagline', 'Welkom op onze website.')) ?></p>
      </div>
      <div class="footer-links">
        <h5>Navigatie</h5>
        <ul>
          <li><a href="<?= BASE_URL ?>/">Home</a></li>
          <li><a href="<?= BASE_URL ?>/news">Nieuws</a></li>
          <?php
          $db = Database::getInstance();
          $footerPages = $db->fetchAll("SELECT title, slug FROM `" . DB_PREFIX . "pages` WHERE status='published' ORDER BY id LIMIT 5");
          foreach ($footerPages as $fp): ?>
          <li><a href="<?= BASE_URL ?>/<?= e($fp['slug']) ?>"><?= e($fp['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="footer-links">
        <h5>Beheer</h5>
        <ul>
          <li><a href="<?= BASE_URL ?>/admin/">Admin Paneel</a></li>
          <li><a href="<?= BASE_URL ?>/admin/pages/add.php">Nieuwe Pagina</a></li>
          <li><a href="<?= BASE_URL ?>/admin/news/add.php">Nieuw Bericht</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> <?= e(Settings::get('site_name')) ?>.
      <?= Settings::get('footer_text') ? e(Settings::get('footer_text')) : 'Mogelijk gemaakt door ROICT CMS.' ?>
      </p>
      <p style="font-size:.78rem;opacity:.5;">ROICT CMS v<?= CMS_VERSION ?></p>
    </div>
  </div>
</footer>

<script>
// Mobile nav
const toggle = document.getElementById('nav-toggle');
const menu = document.getElementById('nav-menu');
toggle?.addEventListener('click', () => {
  menu.classList.toggle('open');
  toggle.classList.toggle('active');
});
</script>
<?php do_theme_footer(); ?>
</body>
</html>
