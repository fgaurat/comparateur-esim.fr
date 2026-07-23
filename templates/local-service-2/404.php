<?php
/** @var array   $config  */
/** @var Seo     $seo     */
/** @var array   $seoData */
/** @var Content $content */
$pages = $content->getPages();
ob_start();
?>

<div class="min-h-[70vh] flex items-center justify-center text-center px-4 py-20">
    <div>
        <div class="text-8xl md:text-9xl font-black text-gray-200 leading-none select-none mb-6" aria-hidden="true">404</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-3">Page introuvable</h1>
        <p class="text-gray-500 mb-8 max-w-sm mx-auto">
            La page que vous cherchez n&rsquo;existe pas ou a &eacute;t&eacute; d&eacute;plac&eacute;e.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-3">
            <a href="/"
               class="inline-flex items-center justify-center px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition-colors">
                &larr; Retour &agrave; l&rsquo;accueil
            </a>
            <a href="<?= htmlspecialchars($config['cta_url'] ?? '/contact/') ?>"
               class="inline-flex items-center justify-center px-6 py-3 border border-gray-200 text-gray-700 font-semibold rounded-xl hover:border-primary-500 hover:text-primary-600 transition-colors">
                <?= htmlspecialchars($config['cta_text'] ?? 'Demander un devis') ?>
            </a>
        </div>
    </div>
</div>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
