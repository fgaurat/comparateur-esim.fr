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
?>

<!-- En-tête archive -->
<div class="bg-gradient-to-br from-primary-900 via-primary-800 to-primary-700 py-16 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <nav class="flex items-center gap-2 text-sm text-primary-200 mb-4" aria-label="Fil d'Ariane">
            <a href="/" class="hover:text-white transition-colors">Accueil</a>
            <span aria-hidden="true" class="text-primary-400">›</span>
            <span class="text-white" aria-current="page"><?= htmlspecialchars($archivePage['title']) ?></span>
        </nav>
        <h1 class="text-3xl md:text-4xl font-extrabold text-white mb-3">
            <?= htmlspecialchars($archivePage['title']) ?>
        </h1>
        <?php if (!empty($archivePage['excerpt'])): ?>
        <p class="text-primary-200 max-w-2xl"><?= htmlspecialchars($archivePage['excerpt']) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <?php if (!empty($archivePage['content'])): ?>
    <div class="prose text-gray-700 max-w-2xl mb-12 reveal">
        <?= $archivePage['content'] ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($childPages)): ?>
    <section class="mb-12">
        <h2 class="text-xl font-bold text-gray-900 mb-6 pb-3 border-b-2 border-gray-200 reveal">Sous-zones</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($childPages as $i => $child): ?>
            <a href="<?= htmlspecialchars($child['link']) ?>"
               class="group bg-white border border-gray-200 rounded-xl px-5 py-4 font-semibold text-gray-800 hover:border-primary-500 hover:text-primary-600 hover:translate-x-1 transition-all flex items-center justify-between reveal reveal-d<?= ($i % 3) + 1 ?>">
                <?= htmlspecialchars($child['title']) ?>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-primary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($localPosts)): ?>
    <section>
        <h2 class="text-xl font-bold text-gray-900 mb-6 pb-3 border-b-2 border-gray-200 reveal">Professionnels dans cette zone</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($localPosts as $i => $post): ?>
            <div class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md hover:border-primary-200 transition-all reveal reveal-d<?= ($i % 3) + 1 ?>">
                <h3 class="font-bold text-gray-900 mb-2">
                    <a href="/<?= htmlspecialchars($post['slug']) ?>/"
                       class="hover:text-primary-600 transition-colors">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                </h3>
                <?php if (!empty($post['excerpt'])): ?>
                <p class="text-sm text-gray-600 mb-4 line-clamp-3"><?= htmlspecialchars($post['excerpt']) ?></p>
                <?php endif; ?>
                <a href="/<?= htmlspecialchars($post['slug']) ?>/"
                   class="inline-flex items-center text-sm font-semibold text-primary-600 hover:text-primary-700 gap-1 transition-colors"
                   aria-label="Voir le profil : <?= htmlspecialchars($post['title']) ?>">
                    Voir le profil
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (empty($archivePage['content']) && empty($childPages) && empty($localPosts)): ?>
    <div class="text-center py-16 text-gray-500">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        </svg>
        <p>Aucun résultat dans cette zone pour le moment.</p>
    </div>
    <?php endif; ?>

</div>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
