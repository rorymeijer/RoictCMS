<!-- Breadcrumb -->
<div class="breadcrumb-wrap">
  <div class="container">
    <ol><li><a href="<?= BASE_URL ?>/">Home</a></li><li>Nieuws</li></ol>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="section-title" style="text-align:left;">
      <div class="label">Alle berichten</div>
      <h1 style="font-size:clamp(1.8rem,4vw,2.5rem);">Nieuws</h1>
      <p><?= $total ?> bericht<?= $total !== 1 ? 'en' : '' ?></p>
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
            <?php if ($post['cat_name']): ?><span class="news-card-cat"><?= e($post['cat_name']) ?></span><?php endif; ?>
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

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="pagination-wrap">
      <?php if ($pagination['current_page'] > 1): ?>
      <a href="?p=<?= $pagination['current_page'] - 1 ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a>
      <?php endif; ?>
      <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
      <a href="?p=<?= $i ?>" class="page-btn <?= $i === $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
      <a href="?p=<?= $pagination['current_page'] + 1 ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="text-align:center;padding:5rem;color:var(--text-muted);">
      <i class="bi bi-newspaper" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>
      <p>Nog geen berichten gepubliceerd.</p>
    </div>
    <?php endif; ?>
  </div>
</section>
