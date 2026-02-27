<?php if (!$page): ?>
<?php include __DIR__ . '/404.php'; ?>
<?php else: ?>
<div class="breadcrumb-wrap">
  <div class="container">
    <ol><li><a href="<?= BASE_URL ?>/">Home</a></li><li><?= e($page['title']) ?></li></ol>
  </div>
</div>
<article class="container-sm">
  <header class="article-header">
    <h1><?= e($page['title']) ?></h1>
  </header>
  <div class="article-body">
    <?= do_shortcode($page['content']) ?>
  </div>
</article>
<?php endif; ?>
