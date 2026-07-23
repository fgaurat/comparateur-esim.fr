<?php
/** @var array   $archivePage */
/** @var array   $childPages  */
/** @var array   $localPosts  */
/** @var array   $config      */
/** @var Seo     $seo         */
/** @var array   $seoData     */
/** @var Content $content     */
$pages = $content->getPages();
ob_start();
$delays = ['reveal-d1', 'reveal-d2', 'reveal-d3', 'reveal-d4'];
?>

<div class="archive-header">
    <div class="archive-header-inner">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="/">Accueil</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?= htmlspecialchars($archivePage['title']) ?></span>
        </nav>
        <h1><?= htmlspecialchars($archivePage['title']) ?></h1>
    </div>
</div>

<div class="archive-body">

    <?php if (!empty($archivePage['content'])): ?>
    <div class="archive-desc post-content reveal">
        <?= $archivePage['content'] ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($childPages)): ?>
    <section class="archive-zones">
        <h2 class="archive-section-title reveal">Sous-zones</h2>
        <div class="zones-grid">
            <?php foreach ($childPages as $i => $child): ?>
            <a href="<?= htmlspecialchars($child['link']) ?>"
               class="zone-card reveal <?= $delays[$i % 4] ?>">
                <?= htmlspecialchars($child['title']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($localPosts)): ?>
    <section class="archive-posts">
        <h2 class="archive-section-title reveal">Professionnels dans cette zone</h2>
        <div class="artisans-grid">
            <?php foreach ($localPosts as $i => $post): ?>
            <div class="artisan-card reveal <?= $delays[$i % 4] ?>">
                <h3>
                    <a href="/<?= htmlspecialchars($post['slug']) ?>/">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                </h3>
                <?php if (!empty($post['excerpt'])): ?>
                <p><?= htmlspecialchars($post['excerpt']) ?></p>
                <?php endif; ?>
                <a href="/<?= htmlspecialchars($post['slug']) ?>/"
                   class="artisan-card__link"
                   aria-label="Voir le profil : <?= htmlspecialchars($post['title']) ?>">
                    Voir le profil &rarr;
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (empty($archivePage['content']) && empty($childPages) && empty($localPosts)): ?>
    <p style="text-align:center; padding: 3rem 0; color: var(--text-muted);">
        Aucun r&eacute;sultat dans cette zone pour le moment.
    </p>
    <?php endif; ?>

</div>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
