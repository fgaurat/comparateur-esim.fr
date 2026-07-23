<?php
/** @var array        $archivePage */
/** @var array        $childPages  */
/** @var array        $localPosts  */
/** @var array        $config      */
/** @var Seo          $seo         */
/** @var array        $seoData     */
/** @var Content      $content     */
/** @var string       $bodyClass   */
$pages = $content->getPages();
ob_start();
?>

<nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="/">Accueil</a>
    <span aria-hidden="true">›</span>
    <span aria-current="page"><?= htmlspecialchars($archivePage['title']) ?></span>
</nav>

<header class="post-header">
    <h1><?= htmlspecialchars($archivePage['title']) ?></h1>
</header>

<?php if (!empty($archivePage['featured_image_url'])): ?>
<img
    class="post-featured"
    src="<?= htmlspecialchars(str_starts_with($archivePage['featured_image_url'], 'http') ? $archivePage['featured_image_url'] : $config['site_url'] . $archivePage['featured_image_url']) ?>"
    alt="<?= htmlspecialchars($archivePage['featured_image_alt'] ?? '') ?>"
    width="760" height="420"
>
<?php endif; ?>

<?php if (!empty($archivePage['content'])): ?>
<section class="archive-description post-content">
    <?= $archivePage['content'] ?>
</section>
<?php endif; ?>

<?php if (!empty($childPages)): ?>
<section class="archive-subzones">
    <h2>Sous-zones</h2>
    <ul>
        <?php foreach ($childPages as $child): ?>
        <li>
            <a href="<?= htmlspecialchars($child['link']) ?>">
                <?= htmlspecialchars($child['title']) ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if (!empty($localPosts)): ?>
<section class="archive-artisans">
    <h2>Artisans dans cette zone</h2>
    <ul>
        <?php foreach ($localPosts as $post): ?>
        <li class="archive-artisans__item">
            <h3>
                <a href="/<?= htmlspecialchars($post['slug']) ?>/">
                    <?= htmlspecialchars($post['title']) ?>
                </a>
            </h3>
            <?php if (!empty($post['excerpt'])): ?>
            <p class="post-card__excerpt"><?= htmlspecialchars($post['excerpt']) ?></p>
            <?php endif; ?>
            <a href="/<?= htmlspecialchars($post['slug']) ?>/" class="read-more"
               aria-label="Lire «<?= htmlspecialchars($post['title']) ?>»">
                Lire la suite →
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if (empty($archivePage['content']) && empty($childPages) && empty($localPosts)): ?>
<p style="color:var(--muted);text-align:center;padding:3rem 0;">Aucun résultat dans cette zone.</p>
<?php endif; ?>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
