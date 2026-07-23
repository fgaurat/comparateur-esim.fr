<?php
/** @var array   $page    */
/** @var array   $config  */
/** @var Seo     $seo     */
/** @var array   $seoData */
/** @var Content $content */
$pages = $content->getPages();
ob_start();
?>

<nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="/">Accueil</a>
    <span aria-hidden="true">›</span>
    <span aria-current="page"><?= htmlspecialchars($page['title']) ?></span>
</nav>

<article class="post-single" itemscope itemtype="https://schema.org/WebPage">

    <header class="post-header">
        <h1 itemprop="name"><?= htmlspecialchars($page['title']) ?></h1>
    </header>

    <?php if (!empty($page['featured_image_url'])): ?>
    <img
        class="post-featured"
        src="<?= htmlspecialchars(str_starts_with($page['featured_image_url'], 'http') ? $page['featured_image_url'] : $config['site_url'] . $page['featured_image_url']) ?>"
        alt="<?= htmlspecialchars($page['featured_image_alt']) ?>"
        itemprop="image"
        width="760" height="420"
    >
    <?php endif; ?>

    <div class="post-content" itemprop="description">
        <?= $page['content'] ?>
    </div>

</article>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
