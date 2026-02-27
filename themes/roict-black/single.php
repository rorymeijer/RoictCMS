<?php if (!$post): ?>
<div class="container" style="padding: 5rem 1.5rem; text-align:center;">
  <h1>404 — Bericht niet gevonden</h1>
  <a href="<?= BASE_URL ?>/news" class="btn btn-primary">Terug naar nieuws</a>
</div>
<?php else: ?>

<!-- Breadcrumb -->
<div class="breadcrumb-wrap">
  <div class="container">
    <ol>
      <li><a href="<?= BASE_URL ?>/">Home</a></li>
      <li><a href="<?= BASE_URL ?>/news">Nieuws</a></li>
      <li><?= e(substr($post['title'], 0, 50)) ?></li>
    </ol>
  </div>
</div>

<article>
  <div class="container-sm">
    <!-- Article header -->
    <header class="article-header">
      <div class="article-meta">
        <span><i class="bi bi-calendar3"></i> <?= date(Settings::get('date_format', 'd M Y'), strtotime($post['published_at'] ?? $post['created_at'])) ?></span>
        <span><i class="bi bi-person"></i> <?= e($post['username'] ?? 'Redactie') ?></span>
      </div>
      <h1><?= e($post['title']) ?></h1>
      <?php if ($post['excerpt']): ?>
      <p style="font-size:1.15rem;color:var(--text-muted);font-style:italic;border-left:4px solid var(--primary);padding-left:1rem;"><?= e($post['excerpt']) ?></p>
      <?php endif; ?>
    </header>

    <?php if ($post['featured_image']): ?>
    <img src="<?= BASE_URL ?>/uploads/<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" style="width:100%;border-radius:16px;margin-bottom:2rem;">
    <?php endif; ?>

    <!-- Article body -->
    <div class="article-body">
      <?= $post['content'] ?>
    </div>

    <hr class="divider">

    <!-- Back link -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;padding:1rem 0 3rem;">
      <a href="<?= BASE_URL ?>/news" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Terug naar nieuws</a>
      <div style="display:flex;gap:.5rem;">
        <span style="font-size:.85rem;color:var(--text-muted);align-self:center;">Delen:</span>
        <a href="https://twitter.com/share?url=<?= urlencode(BASE_URL . '/news/' . $post['slug']) ?>" target="_blank" class="btn btn-sm" style="background:#1da1f2;color:white;border-radius:9px;"><i class="bi bi-twitter-x"></i></a>
        <a href="https://www.linkedin.com/shareArticle?url=<?= urlencode(BASE_URL . '/news/' . $post['slug']) ?>" target="_blank" class="btn btn-sm" style="background:#0077b5;color:white;border-radius:9px;"><i class="bi bi-linkedin"></i></a>
      </div>
    </div>
  </div>
</article>
<?php endif; ?>
