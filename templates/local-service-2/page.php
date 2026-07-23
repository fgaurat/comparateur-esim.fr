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

<!-- En-tête page -->
<div class="bg-gray-50 border-b border-gray-200 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4" aria-label="Fil d'Ariane">
            <a href="/" class="hover:text-primary-600 transition-colors">Accueil</a>
            <span aria-hidden="true" class="text-gray-300">›</span>
            <span class="text-gray-900" aria-current="page"><?= htmlspecialchars($page['title']) ?></span>
        </nav>
        <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900">
            <?= htmlspecialchars($page['title']) ?>
        </h1>
    </div>
</div>

<!-- Corps de la page -->
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
     itemscope itemtype="https://schema.org/WebPage">

    <?php
    $featuredSrc = !empty($page['featured_image_url'])
        ? (str_starts_with($page['featured_image_url'], 'http')
            ? $page['featured_image_url']
            : $config['site_url'] . $page['featured_image_url'])
        : null;
    if ($featuredSrc): ?>
    <img src="<?= htmlspecialchars($featuredSrc) ?>"
         alt="<?= htmlspecialchars($page['featured_image_alt'] ?? '') ?>"
         class="w-full rounded-2xl shadow-lg mb-10 max-h-[480px] object-cover"
         itemprop="image" width="800" height="450"
         onerror="this.onerror=null;this.src='<?= htmlspecialchars($_ph) ?>'">
    <?php endif; ?>

    <div class="prose text-gray-800" itemprop="description">
        <?= $page['content'] ?>
    </div>

</div>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
