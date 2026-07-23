<?php
/** @var array   $config  */
/** @var Seo     $seo     */
/** @var array   $seoData */
/** @var Content $content */
$pages = $content->getPages();
ob_start();
?>

<section class="error-page">
    <div>
        <div class="error-code" aria-hidden="true">404</div>
        <h1>Page introuvable</h1>
        <p>La page que vous cherchez n&rsquo;existe pas ou a &eacute;t&eacute; d&eacute;plac&eacute;e.</p>
        <a href="/" class="btn btn-primary">&larr; Retour &agrave; l&rsquo;accueil</a>
    </div>
</section>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
