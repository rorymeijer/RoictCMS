<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <div class="hero-label">
        <i class="bi bi-lightning-charge-fill"></i>
        Welkom bij <?= e(Settings::get('site_name')) ?>
      </div>
      <h1><?= e(Settings::get('site_name')) ?><br><em><?= e(Settings::get('site_tagline', 'Uw online thuis')) ?></em></h1>
      <p>Blijf op de hoogte van het laatste nieuws en updates. Ontdek onze pagina's en lees meer over onze organisatie.</p>
      <div class="hero-actions">
        <a href="<?= BASE_URL ?>/news" class="btn btn-primary"><i class="bi bi-newspaper"></i> Bekijk nieuws</a>
        <a href="<?= BASE_URL ?>/over-ons" class="btn btn-outline"><i class="bi bi-arrow-right"></i> Meer over ons</a>
      </div>
    </div>
  </div>
</section>

<!-- LATEST NEWS -->
<section class="section">
  <div class="container">
    <div class="section-title">
      <div class="label">Laatste berichten</div>
      <h2>Actueel nieuws</h2>
      <p>De meest recente berichten van onze redactie.</p>
    </div>

    <?php if ($posts): ?>
    <div class="news-grid">
      <?php foreach ($posts as $post): ?>
      <article class="news-card">
        <div class="news-card-image">
          <?php if ($post['featured_image']): ?>
          <?php $fi = $post['featured_image']; $fiSrc = (strpos($fi, '://') !== false) ? $fi : BASE_URL . '/uploads/' . $fi; ?>
          <img src="<?= e($fiSrc) ?>" alt="<?= e($post['title']) ?>">
          <?php else: ?>
          <span class="placeholder"><i class="bi bi-newspaper"></i></span>
          <?php endif; ?>
        </div>
        <div class="news-card-body">
          <div class="news-card-meta">
            <?php if ($post['cat_name']): ?>
            <span class="news-card-cat"><?= e($post['cat_name']) ?></span>
            <?php endif; ?>
            <span class="news-card-date"><?= date(Settings::get('date_format', 'd M Y'), strtotime($post['published_at'] ?? $post['created_at'])) ?></span>
          </div>
          <h3><a href="<?= BASE_URL ?>/news/<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h3>
          <p><?= e($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 130)) ?>...</p>
        </div>
        <div class="news-card-footer">
          <div class="news-author">
            <div class="author-avatar"><?= strtoupper(substr($post['username'] ?? 'A', 0, 1)) ?></div>
            <?= e($post['username'] ?? 'Redactie') ?>
          </div>
          <a href="<?= BASE_URL ?>/news/<?= e($post['slug']) ?>" class="read-more">Lees meer</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:2.5rem;">
      <a href="<?= BASE_URL ?>/news" class="btn btn-primary">Alle berichten bekijken <i class="bi bi-arrow-right"></i></a>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:4rem;color:var(--text-muted);">
      <i class="bi bi-newspaper" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>
      <p>Nog geen nieuwsberichten gepubliceerd.</p>
      <a href="<?= BASE_URL ?>/admin/news/add.php" class="btn btn-primary">Schrijf het eerste bericht</a>
    </div>
    <?php endif; ?>
  </div>
</section>
