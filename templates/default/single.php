<?php
/** @var array   $post    */
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
    <?php if (!empty($post['categories'])): ?>
        <a href="/category/<?= htmlspecialchars(Markdown::slugify($post['categories'][0])) ?>/">
            <?= htmlspecialchars($post['categories'][0]) ?>
        </a>
        <span aria-hidden="true">›</span>
    <?php endif; ?>
    <span aria-current="page"><?= htmlspecialchars($post['title']) ?></span>
</nav>

<article class="post-single" itemscope itemtype="https://schema.org/BlogPosting">

    <header class="post-header">
        <div class="post-meta">
            <time datetime="<?= htmlspecialchars($post['date']) ?>" itemprop="datePublished">
                <?= date('j F Y', strtotime($post['date'])) ?>
            </time>
            <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                Par <span itemprop="name"><?= htmlspecialchars($post['author']) ?></span>
            </span>
            <?php foreach ($post['categories'] as $cat): ?>
                <a href="/category/<?= htmlspecialchars(Markdown::slugify($cat)) ?>/" class="tag"
                   itemprop="articleSection"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>

        <h1 itemprop="headline"><?= htmlspecialchars($post['title']) ?></h1>
    </header>

    <?php if (!empty($post['featured_image_url'])): ?>
    <img
        class="post-featured"
        src="<?= htmlspecialchars(str_starts_with($post['featured_image_url'], 'http') ? $post['featured_image_url'] : $config['site_url'] . $post['featured_image_url']) ?>"
        alt="<?= htmlspecialchars($post['featured_image_alt']) ?>"
        itemprop="image"
        width="760" height="420"
    >
    <?php endif; ?>

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

</article>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
