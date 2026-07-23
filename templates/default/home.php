<?php
/** @var array   $posts       */
/** @var array   $config      */
/** @var Seo     $seo         */
/** @var array   $seoData     */
/** @var Content $content     */
/** @var int     $currentPage */
/** @var int     $totalPages  */
$pages = $content->getPages();
ob_start();
?>

<h1 class="sr-only">Articles récents — <?= htmlspecialchars($config['site_name']) ?></h1>

<?php if (empty($posts)): ?>
<p style="color:var(--muted);text-align:center;padding:3rem 0;">Aucun article pour le moment.</p>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
<article class="post-card" itemscope itemtype="https://schema.org/BlogPosting">

    <?php if (!empty($post['featured_image_url'])): ?>
    <a href="/<?= htmlspecialchars($post['slug']) ?>/" tabindex="-1" aria-hidden="true">
        <img
            class="post-card__thumb"
            src="<?= htmlspecialchars(str_starts_with($post['featured_image_url'], 'http') ? $post['featured_image_url'] : $config['site_url'] . $post['featured_image_url']) ?>"
            alt="<?= htmlspecialchars($post['featured_image_alt']) ?>"
            itemprop="image"
            width="760" height="220"
            loading="lazy"
        >
    </a>
    <?php endif; ?>

    <div class="post-meta">
        <time datetime="<?= htmlspecialchars($post['date']) ?>" itemprop="datePublished">
            <?= date('j F Y', strtotime($post['date'])) ?>
        </time>
        <?php foreach ($post['categories'] as $cat): ?>
            <a href="/category/<?= htmlspecialchars(Markdown::slugify($cat)) ?>/" class="tag"
               itemprop="articleSection"><?= htmlspecialchars($cat) ?></a>
        <?php endforeach; ?>
    </div>

    <h2 itemprop="headline">
        <a href="/<?= htmlspecialchars($post['slug']) ?>/" itemprop="url">
            <?= htmlspecialchars($post['title']) ?>
        </a>
    </h2>

    <p class="post-card__excerpt" itemprop="description"><?= htmlspecialchars($post['excerpt']) ?></p>

    <a href="/<?= htmlspecialchars($post['slug']) ?>/" class="read-more"
       aria-label="Lire «<?= htmlspecialchars($post['title']) ?>»">
        Lire la suite →
    </a>

</article>
<?php endforeach; ?>

<?php if ($totalPages > 1): ?>
<nav class="pagination" aria-label="Pagination des articles">
    <?php if ($currentPage > 1): ?>
        <a href="<?= $currentPage === 2 ? '/' : '/page/' . ($currentPage - 1) . '/' ?>" rel="prev">← Précédent</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $currentPage): ?>
            <span class="current" aria-current="page"><?= $i ?></span>
        <?php else: ?>
            <a href="<?= $i === 1 ? '/' : '/page/' . $i . '/' ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($currentPage < $totalPages): ?>
        <a href="/page/<?= $currentPage + 1 ?>/" rel="next">Suivant →</a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
