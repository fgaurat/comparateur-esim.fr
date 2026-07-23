<?php
/** @var array   $posts       */
/** @var array   $config      */
/** @var Seo     $seo         */
/** @var array   $seoData     */
/** @var Content $content     */
/** @var int     $currentPage */
/** @var int     $totalPages  */
$pages = $content->getPages();

// ── Placeholder images (placehold.eolem.com) ──
$_hex  = ltrim($config['theme_color'] ?? '#2563eb', '#');
$_ph   = 'https://placehold.eolem.com/600x338.webp'
       . '?gradient=linear&bg=' . $_hex . '&bg2=1e293b&angle=135&fg=ffffff&fit=stable';

ob_start();
?>

<?php if ($currentPage === 1): ?>

<!-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ -->
<?php
$_bgVideo = $config['hero_bg_video'] ?? null;
$_bgImage = $config['hero_bg_image'] ?? null;
$_heroClass = $_bgVideo ? ' hero--video' : ($_bgImage ? ' hero--img' : '');
$_heroStyle = $_bgImage && !$_bgVideo
    ? ' style="background-image:url(\'' . htmlspecialchars($_bgImage, ENT_QUOTES) . '\')"'
    : '';
?>
<section class="hero<?= $_heroClass ?>"<?= $_heroStyle ?> aria-label="Présentation">

    <?php if ($_bgVideo): ?>
    <video class="hero__video" autoplay muted loop playsinline>
        <source src="<?= htmlspecialchars($_bgVideo) ?>" type="video/mp4">
    </video>
    <?php endif; ?>

    <div class="hero-inner">
        <div class="hero-content">

            <p class="hero-badge">
                <?= htmlspecialchars($config['hero_badge'] ?? 'Service local de confiance') ?>
            </p>

            <h1><?= htmlspecialchars($config['hero_title'] ?? $config['site_name']) ?></h1>

            <p class="hero-desc">
                <?= htmlspecialchars($config['hero_subtitle'] ?? $config['site_description']) ?>
            </p>

            <div class="hero-actions">
                <a href="<?= htmlspecialchars($config['cta_url'] ?? '/contact/') ?>"
                   class="btn btn-primary">
                    <?= htmlspecialchars($config['cta_text'] ?? 'Demander un devis gratuit') ?>
                </a>
                <?php if (!empty($config['phone'])): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', $config['phone'])) ?>"
                   class="btn btn-outline-light">
                    Appeler maintenant
                </a>
                <?php endif; ?>
            </div>

            <?php
            $trustItems = $config['hero_trust'] ?? [
                'Devis gratuit',
                'Intervention rapide',
                'Artisans certifi&eacute;s',
            ];
            ?>
            <ul class="hero-trust" aria-label="Nos engagements">
                <?php foreach ($trustItems as $item): ?>
                <li class="trust-item">
                    <span class="trust-check" aria-hidden="true">&#10003;</span>
                    <?= htmlspecialchars($item) ?>
                </li>
                <?php endforeach; ?>
            </ul>

        </div>
    </div>
</section>

<!-- ══════════════════════════════════════
     FEATURES
══════════════════════════════════════ -->
<section class="features-section">
    <div class="container">

        <div class="section-header center reveal">
            <span class="section-label">Nos atouts</span>
            <h2>Pourquoi nous faire confiance ?</h2>
            <p>Des professionnels locaux engag&eacute;s pour la qualit&eacute; de chaque prestation.</p>
        </div>

        <?php
        $features = $config['features'] ?? [
            [
                'icon'  => '&#9733;',
                'title' => 'Expertise reconnue',
                'desc'  => "Des artisans qualifiés avec des années d'expérience dans leur domaine.",
            ],
            [
                'icon'  => '&#9711;',
                'title' => 'Réactivité',
                'desc'  => "Intervention rapide dans les meilleurs délais pour répondre à vos urgences.",
            ],
            [
                'icon'  => '&#10022;',
                'title' => 'Qualité garantie',
                'desc'  => "Chaque prestation est réalisée avec soin selon les normes en vigueur.",
            ],
        ];
        $delays = ['reveal-d1', 'reveal-d2', 'reveal-d3', 'reveal-d4'];
        ?>

        <div class="features-grid">
            <?php foreach ($features as $i => $f): ?>
            <div class="feature-card reveal <?= $delays[$i % 4] ?>">
                <div class="feature-icon" aria-hidden="true"><?= $f['icon'] ?></div>
                <h3><?= htmlspecialchars($f['title']) ?></h3>
                <p><?= htmlspecialchars($f['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<?php endif; /* fin : page 1 uniquement */ ?>

<!-- ══════════════════════════════════════
     ARTICLES RÉCENTS
══════════════════════════════════════ -->
<?php if (!empty($posts)): ?>
<section class="posts-section">
    <div class="container">

        <div class="section-header reveal <?= $currentPage === 1 ? '' : 'center' ?>">
            <?php if ($currentPage === 1): ?>
                <span class="section-label">Nos conseils</span>
                <h2>Derniers articles &amp; guides</h2>
                <p>Retrouvez nos conseils, actualit&eacute;s et guides pratiques.</p>
            <?php else: ?>
                <h2>Articles &mdash; Page <?= $currentPage ?></h2>
            <?php endif; ?>
        </div>

        <div class="posts-grid">
            <?php foreach ($posts as $i => $post): ?>
            <article class="post-card reveal <?= $delays[$i % 4] ?>"
                     itemscope itemtype="https://schema.org/BlogPosting">

                <?php
                $thumbSrc = !empty($post['featured_image_url'])
                    ? (str_starts_with($post['featured_image_url'], 'http')
                        ? $post['featured_image_url']
                        : $config['site_url'] . $post['featured_image_url'])
                    : $_ph;
                ?>
                <a href="/<?= htmlspecialchars($post['slug']) ?>/"
                   class="post-card__thumb-wrap"
                   tabindex="-1" aria-hidden="true">
                    <img
                        class="post-card__thumb"
                        src="<?= htmlspecialchars($thumbSrc) ?>"
                        alt="<?= htmlspecialchars($post['featured_image_alt'] ?? '') ?>"
                        itemprop="image"
                        loading="lazy"
                        width="600" height="338"
                        onerror="this.onerror=null;this.src='<?= htmlspecialchars($_ph) ?>'"
                    >
                </a>

                <div class="post-card__body">
                    <div class="post-card__meta">
                        <time class="post-card__date"
                              datetime="<?= htmlspecialchars($post['date']) ?>"
                              itemprop="datePublished">
                            <?= date('j F Y', strtotime($post['date'])) ?>
                        </time>
                        <?php foreach (array_slice($post['categories'], 0, 2) as $cat): ?>
                            <a href="/category/<?= htmlspecialchars(Markdown::slugify($cat)) ?>/"
                               class="tag" itemprop="articleSection">
                                <?= htmlspecialchars($cat) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <h2 itemprop="headline">
                        <a href="/<?= htmlspecialchars($post['slug']) ?>/" itemprop="url">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h2>

                    <p class="post-card__excerpt" itemprop="description">
                        <?= htmlspecialchars($post['excerpt']) ?>
                    </p>

                    <a href="/<?= htmlspecialchars($post['slug']) ?>/"
                       class="post-card__link"
                       aria-label="Lire : <?= htmlspecialchars($post['title']) ?>">
                        Lire l&rsquo;article &rarr;
                    </a>
                </div>

            </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Pagination des articles">
            <?php if ($currentPage > 1): ?>
                <a href="<?= $currentPage === 2 ? '/' : '/page/' . ($currentPage - 1) . '/' ?>" rel="prev">
                    &larr; Pr&eacute;c&eacute;dent
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $currentPage): ?>
                    <span class="current" aria-current="page"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= $i === 1 ? '/' : '/page/' . $i . '/' ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="/page/<?= $currentPage + 1 ?>/" rel="next">Suivant &rarr;</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

    </div>
</section>

<?php else: ?>
<section class="posts-section">
    <div class="container" style="text-align:center; padding: 3rem 0;">
        <p style="color:var(--text-muted);">Aucun article pour le moment.</p>
    </div>
</section>
<?php endif; ?>

<?php if ($currentPage === 1): ?>

<!-- ══════════════════════════════════════
     CTA
══════════════════════════════════════ -->
<section class="cta-section">
    <div class="container cta-inner">
        <h2 class="reveal">
            <?= htmlspecialchars($config['cta_title'] ?? 'Prêt à nous contacter ?') ?>
        </h2>
        <p class="reveal reveal-d1">
            <?= htmlspecialchars($config['cta_subtitle'] ?? 'Obtenez un devis gratuit en quelques minutes. Nos experts sont disponibles pour vous accompagner.') ?>
        </p>
        <div class="cta-actions reveal reveal-d2">
            <a href="<?= htmlspecialchars($config['cta_url'] ?? '/contact/') ?>"
               class="btn btn-primary">
                <?= htmlspecialchars($config['cta_text'] ?? 'Demander un devis') ?>
            </a>
            <?php if (!empty($config['phone'])): ?>
            <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', $config['phone'])) ?>"
               class="btn btn-outline-light">
                <?= htmlspecialchars($config['phone']) ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php endif; ?>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
