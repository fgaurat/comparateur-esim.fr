<?php ob_start(); ?>
<section class="page-hero"><div class="page-hero-inner"><nav class="breadcrumb"><a href="/">Accueil</a><span>›</span><span><?= htmlspecialchars($page['title']) ?></span></nav><h1><?= htmlspecialchars($page['title']) ?></h1><p class="lead"><?= htmlspecialchars($page['excerpt']) ?></p></div></section><div class="content-wrap"><article class="post-content"><?= $page['content'] ?></article></div>
<?php $mainContent = ob_get_clean(); require __DIR__ . '/layout.php'; ?>
