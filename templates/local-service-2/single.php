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

$_cta_url  = $config['cta_url']  ?? '/contact/';
$_cta_text = $config['cta_text'] ?? 'Demander un devis gratuit';

ob_start();
?>

<!-- En-tête article -->
<div class="bg-gradient-to-br from-primary-900 via-primary-800 to-primary-700 py-16 px-4 sm:px-6 lg:px-8"
     itemscope itemtype="https://schema.org/BlogPosting">

    <div class="max-w-3xl mx-auto">

        <nav class="flex items-center gap-2 text-sm text-primary-200 mb-6 flex-wrap" aria-label="Fil d'Ariane">
            <a href="/" class="hover:text-white transition-colors">Accueil</a>
            <?php if (!empty($post['categories'])): ?>
            <span aria-hidden="true" class="text-primary-400">›</span>
            <a href="/category/<?= htmlspecialchars(Markdown::slugify($post['categories'][0])) ?>/"
               class="hover:text-white transition-colors">
                <?= htmlspecialchars($post['categories'][0]) ?>
            </a>
            <?php endif; ?>
            <span aria-hidden="true" class="text-primary-400">›</span>
            <span class="text-white" aria-current="page"><?= htmlspecialchars($post['title']) ?></span>
        </nav>

        <div class="flex items-center gap-3 flex-wrap mb-4">
            <time class="text-sm text-primary-200"
                  datetime="<?= htmlspecialchars($post['date']) ?>" itemprop="datePublished">
                <?= date('j F Y', strtotime($post['date'])) ?>
            </time>
            <?php if (!empty($post['author'])): ?>
            <span class="text-sm text-primary-300" itemprop="author" itemscope itemtype="https://schema.org/Person">
                Par <span itemprop="name"><?= htmlspecialchars($post['author']) ?></span>
            </span>
            <?php endif; ?>
            <?php foreach ($post['categories'] as $cat): ?>
            <a href="/category/<?= htmlspecialchars(Markdown::slugify($cat)) ?>/"
               class="text-xs bg-white/20 text-white px-3 py-0.5 rounded-full hover:bg-white/30 transition-colors" itemprop="articleSection">
                <?= htmlspecialchars($cat) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <h1 class="text-3xl md:text-4xl font-extrabold text-white leading-tight" itemprop="headline">
            <?= htmlspecialchars($post['title']) ?>
        </h1>

    </div>
</div>

<!-- Corps de l'article -->
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10" itemscope itemtype="https://schema.org/BlogPosting">

    <?php
    $featuredSrc = !empty($post['featured_image_url'])
        ? (str_starts_with($post['featured_image_url'], 'http')
            ? $post['featured_image_url']
            : $config['site_url'] . $post['featured_image_url'])
        : $_ph;
    ?>
    <img src="<?= htmlspecialchars($featuredSrc) ?>"
         alt="<?= htmlspecialchars($post['featured_image_alt'] ?? '') ?>"
         class="w-full rounded-2xl shadow-xl mb-10 max-h-[480px] object-cover"
         itemprop="image" width="800" height="450"
         onerror="this.onerror=null;this.src='<?= htmlspecialchars($_ph) ?>'">

    <div class="prose text-gray-800" itemprop="articleBody">
        <?= $post['content'] ?>
    </div>

    <?php if (!empty($post['tags'])): ?>
    <div class="flex flex-wrap gap-2 mt-10 pt-8 border-t border-gray-200" aria-label="Tags">
        <?php foreach ($post['tags'] as $tag): ?>
        <a href="/tag/<?= htmlspecialchars(Markdown::slugify($tag)) ?>/"
           class="text-xs bg-gray-100 text-gray-600 hover:bg-primary-50 hover:text-primary-600 px-3 py-1 rounded-full transition-colors border border-gray-200">
            #<?= htmlspecialchars($tag) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- CTA fin d'article -->
    <div class="mt-12 bg-gradient-to-br from-primary-600 to-primary-800 rounded-2xl p-8 text-center">
        <h3 class="text-xl font-bold text-white mb-2">
            <?= htmlspecialchars($config['article_cta_title'] ?? 'Besoin d\'un devis ?') ?>
        </h3>
        <p class="text-primary-100 mb-6">
            <?= htmlspecialchars($config['article_cta_subtitle'] ?? 'Demandez un devis gratuit en quelques minutes.') ?>
        </p>
        <a href="<?= htmlspecialchars($_cta_url) ?>"
           <?= str_starts_with($_cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?>
           class="inline-flex items-center px-6 py-3 bg-white text-primary-600 font-bold rounded-xl hover:bg-gray-50 transition-colors shadow-lg">
            <?= htmlspecialchars($_cta_text) ?>
        </a>
    </div>

</div>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
