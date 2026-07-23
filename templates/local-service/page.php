<?php
/** @var array   $page    */
/** @var array   $config  */
/** @var Seo     $seo     */
/** @var array   $seoData */
/** @var Content $content */
$pages = $content->getPages();

$_hex = ltrim($config['theme_color'] ?? '#2563eb', '#');
$_ph  = 'https://placehold.eolem.com/800x450.webp'
      . '?gradient=linear&bg=' . $_hex . '&bg2=1e293b&angle=135&fg=ffffff&fit=stable';

ob_start();
?>

<div class="page-header">
    <div class="page-header-inner">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="/">Accueil</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?= htmlspecialchars($page['title']) ?></span>
        </nav>
        <h1><?= htmlspecialchars($page['title']) ?></h1>
    </div>
</div>

<div class="page-body" itemscope itemtype="https://schema.org/WebPage">

    <?php
    $featuredSrc = !empty($page['featured_image_url'])
        ? (str_starts_with($page['featured_image_url'], 'http')
            ? $page['featured_image_url']
            : $config['site_url'] . $page['featured_image_url'])
        : $_ph;
    ?>
    <img
        class="post-featured"
        src="<?= htmlspecialchars($featuredSrc) ?>"
        alt="<?= htmlspecialchars($page['featured_image_alt'] ?? '') ?>"
        itemprop="image"
        width="800" height="450"
        onerror="this.onerror=null;this.src='<?= htmlspecialchars($_ph) ?>'"
    >

    <div class="post-content" itemprop="description">
        <?= $page['content'] ?>
    </div>

</div>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
