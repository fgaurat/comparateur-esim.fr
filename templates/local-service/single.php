<?php
/** @var array   $post    */
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

<div class="post-hero">
    <div class="post-hero-inner">

        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="/">Accueil</a>
            <span aria-hidden="true">›</span>
            <?php if (!empty($post['categories'])): ?>
                <a href="/category/<?= htmlspecialchars(Markdown::slugify($post['categories'][0])) ?>/">
                    <?= htmlspecialchars($post['categories'][0]) ?>
                </a>
                <span aria-hidden="true">›</span>
            <?php endif; ?>
            <span aria-current="page"><?= htmlspecialchars($post['title']) ?></span>
        </nav>

        <div class="post-hero-meta">
            <time datetime="<?= htmlspecialchars($post['date']) ?>" itemprop="datePublished">
                <?= date('j F Y', strtotime($post['date'])) ?>
            </time>
            <?php if (!empty($post['author'])): ?>
            <span class="author" itemprop="author" itemscope itemtype="https://schema.org/Person">
                Par <span itemprop="name"><?= htmlspecialchars($post['author']) ?></span>
            </span>
            <?php endif; ?>
            <?php foreach ($post['categories'] as $cat): ?>
                <a href="/category/<?= htmlspecialchars(Markdown::slugify($cat)) ?>/"
                   class="tag" itemprop="articleSection">
                    <?= htmlspecialchars($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <h1 itemprop="headline"><?= htmlspecialchars($post['title']) ?></h1>

    </div>
</div>

<div class="post-body" itemscope itemtype="https://schema.org/BlogPosting">

    <?php
    $featuredSrc = !empty($post['featured_image_url'])
        ? (str_starts_with($post['featured_image_url'], 'http')
            ? $post['featured_image_url']
            : $config['site_url'] . $post['featured_image_url'])
        : $_ph;
    ?>
    <img
        class="post-featured"
        src="<?= htmlspecialchars($featuredSrc) ?>"
        alt="<?= htmlspecialchars($post['featured_image_alt'] ?? '') ?>"
        itemprop="image"
        width="800" height="450"
        onerror="this.onerror=null;this.src='<?= htmlspecialchars($_ph) ?>'"
    >

    <div class="post-content" itemprop="articleBody">
        <?= $post['content'] ?>
    </div>

    <?php if (!empty($post['tags'])): ?>
    <div class="post-taxonomy" aria-label="Tags de l'article">
        <?php foreach ($post['tags'] as $tag): ?>
            <a href="/tag/<?= htmlspecialchars(Markdown::slugify($tag)) ?>/" class="tag">
                #<?= htmlspecialchars($tag) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
