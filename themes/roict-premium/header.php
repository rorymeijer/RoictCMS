<!DOCTYPE html>
<html lang="<?= Settings::get('language', 'nl') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($metaTitle) ? e($metaTitle) . ' — ' : '' ?><?= e(Settings::get('site_name', 'ROICT CMS')) ?></title>
<?php if (isset($metaDesc) && $metaDesc): ?><meta name="description" content="<?= e($metaDesc) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= themeAssetUrl('css/theme.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<?php do_theme_head(); ?>
</head>
<body>

<!-- Navigation -->
<header class="site-nav">
  <div class="container">
    <nav class="nav-inner">
      <a href="<?= BASE_URL ?>/" class="nav-logo">
        <span><?= e(Settings::get('site_name', 'ROICT CMS')) ?></span>
      </a>
      <ul class="nav-links" id="nav-menu">
        <?php
        $db = Database::getInstance();
        $navPages = $db->fetchAll("SELECT title, slug FROM `" . DB_PREFIX . "pages` WHERE status='published' ORDER BY id LIMIT 8");
        $currentSlug = $currentSlug ?? '';
        foreach ($navPages as $np): ?>
        <li><a href="<?= BASE_URL ?>/<?= e($np['slug']) ?>" class="<?= $currentSlug === $np['slug'] ? 'active' : '' ?>"><?= e($np['title']) ?></a></li>
        <?php endforeach; ?>
        <li><a href="<?= BASE_URL ?>/news" class="<?= ($currentPage ?? '') === 'news' ? 'active' : '' ?>">Nieuws</a></li>
      </ul>
      <div class="nav-cta">
        <button class="nav-toggle" id="nav-toggle" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>
  </div>
</header>
