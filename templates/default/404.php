<?php
/** @var array   $config  */
/** @var Seo     $seo     */
/** @var array   $seoData */
/** @var Content $content */
$pages = $content->getPages();
ob_start();
?>

<div style="text-align:center; padding: 4rem 0;">
    <p style="font-size:5rem; line-height:1; margin-bottom:1rem; color:var(--border);">404</p>
    <h1 style="font-size:1.75rem; margin-bottom:1rem;">Page introuvable</h1>
    <p style="color:var(--muted); margin-bottom:2rem;">
        La page que vous cherchez n'existe pas ou a été déplacée.
    </p>
    <a href="/" class="read-more">← Retour à l'accueil</a>
</div>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
