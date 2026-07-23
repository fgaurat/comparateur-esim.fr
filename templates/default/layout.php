<!DOCTYPE html>
<html lang="<?= htmlspecialchars(strtolower(substr($config['language'], 0, 2))) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?= $seo->metaTags($seoData) ?>
<?= $seo->jsonLd($seoData) ?>
<?= $seo->hreflangLinks($seoData) ?>    <link rel="alternate" type="application/rss+xml"
          title="<?= htmlspecialchars($config['site_name']) ?> — Flux RSS"
          href="<?= htmlspecialchars($config['site_url']) ?>/feed/">
    <meta name="generator" content="WordPress 6.7.2">
    <meta name="theme-color" content="<?= htmlspecialchars($config['theme_color']) ?>">
    <link rel="alternate" type="application/json+oembed"
          href="<?= htmlspecialchars($config['site_url']) ?>/wp-json/oembed/1.0/embed"
          title="oEmbed (JSON)">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:   #2563eb;
            --primary-d: #1d4ed8;
            --text:      #1e293b;
            --muted:     #64748b;
            --border:    #e2e8f0;
            --bg:        #ffffff;
            --bg-alt:    #f8fafc;
            --max-w:     760px;
            --radius:    8px;
        }

        html { scroll-behavior: smooth; }
        body { font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: var(--text); background: var(--bg); line-height: 1.75; }
        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }
        img { max-width: 100%; height: auto; display: block; }

        /* ── Header ── */
        .site-header { background: var(--bg); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }
        .site-header .inner { max-width: 1100px; margin: auto; padding: .9rem 1.5rem; display: flex; align-items: center; gap: 2rem; flex-wrap: wrap; }
        .site-title a { font-size: 1.2rem; font-weight: 700; color: var(--text); }
        .site-title a:hover { text-decoration: none; color: var(--primary); }
        .main-nav { display: flex; gap: 1.25rem; flex-wrap: wrap; }
        .main-nav a { color: var(--muted); font-size: .9rem; font-weight: 500; }
        .main-nav a:hover, .main-nav a[aria-current] { color: var(--primary); text-decoration: none; }
        .nav-api-link { margin-left: auto; background: var(--bg-alt); border: 1px solid var(--border); border-radius: 5px; padding: .25rem .75rem; font-size: .8rem; color: var(--muted) !important; font-family: monospace; }
        .nav-api-link:hover { background: var(--primary); color: #fff !important; border-color: var(--primary); text-decoration: none; }

        /* ── Main ── */
        .site-main { max-width: var(--max-w); margin: 3rem auto; padding: 0 1.5rem; }

        /* ── Post card (liste) ── */
        .post-card { border-bottom: 1px solid var(--border); padding-bottom: 2.5rem; margin-bottom: 2.5rem; }
        .post-card:last-child { border-bottom: none; }
        .post-card__thumb { width: 100%; height: 220px; object-fit: cover; border-radius: var(--radius); margin-bottom: 1.25rem; }
        .post-meta { display: flex; gap: .75rem; flex-wrap: wrap; font-size: .8rem; color: var(--muted); margin-bottom: .6rem; align-items: center; }
        .tag { background: var(--bg-alt); border: 1px solid var(--border); padding: .1rem .5rem; border-radius: 4px; font-size: .75rem; color: var(--muted); }
        .tag:hover { background: var(--primary); color: #fff; border-color: var(--primary); text-decoration: none; }
        .post-card h2 { font-size: 1.5rem; line-height: 1.3; margin-bottom: .5rem; }
        .post-card h2 a { color: var(--text); }
        .post-card h2 a:hover { color: var(--primary); text-decoration: none; }
        .post-card__excerpt { color: var(--muted); margin-bottom: 1rem; }
        .read-more { display: inline-block; background: var(--primary); color: #fff !important; padding: .4rem 1rem; border-radius: 5px; font-size: .85rem; font-weight: 500; }
        .read-more:hover { background: var(--primary-d); text-decoration: none; }

        /* ── Single post/page ── */
        .post-header { margin-bottom: 2rem; }
        .post-header h1 { font-size: 2rem; line-height: 1.25; margin-bottom: 1rem; }
        .post-featured { width: 100%; max-height: 420px; object-fit: cover; border-radius: var(--radius); margin: 1.5rem 0 2rem; }

        .post-content h1, .post-content h2, .post-content h3,
        .post-content h4, .post-content h5, .post-content h6 { margin: 1.8rem 0 .6rem; line-height: 1.3; }
        .post-content h2 { font-size: 1.5rem; }
        .post-content h3 { font-size: 1.25rem; }
        .post-content p { margin-bottom: 1.25rem; }
        .post-content ul, .post-content ol { margin: 0 0 1.25rem 1.5rem; }
        .post-content li { margin-bottom: .3rem; }
        .post-content blockquote { border-left: 4px solid var(--primary); padding: .75rem 1.25rem; background: var(--bg-alt); margin: 1.5rem 0; color: var(--muted); border-radius: 0 var(--radius) var(--radius) 0; }
        .post-content pre { background: #1e293b; color: #e2e8f0; padding: 1.25rem; border-radius: var(--radius); overflow-x: auto; margin: 1.5rem 0; font-size: .875rem; line-height: 1.6; }
        .post-content code { font-family: 'Fira Code', 'Cascadia Code', monospace; font-size: .9em; }
        .post-content p code, .post-content li code { background: var(--bg-alt); padding: .1rem .35rem; border-radius: 3px; border: 1px solid var(--border); }
        .post-content img { border-radius: var(--radius); margin: 1.5rem 0; }
        .post-content a { text-decoration: underline; }
        .post-content hr { border: none; border-top: 1px solid var(--border); margin: 2rem 0; }
        .post-content table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; font-size: .9rem; }
        .post-content th, .post-content td { padding: .6rem .9rem; border: 1px solid var(--border); text-align: left; }
        .post-content th { background: var(--bg-alt); font-weight: 600; }
        .post-content tr:nth-child(even) td { background: var(--bg-alt); }

        .post-taxonomy { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); }

        /* ── Breadcrumb ── */
        .breadcrumb { font-size: .8rem; color: var(--muted); margin-bottom: 1.5rem; display: flex; gap: .4rem; flex-wrap: wrap; align-items: center; }
        .breadcrumb a { color: var(--muted); }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb [aria-hidden] { color: var(--border); }

        /* ── Pagination ── */
        .pagination { display: flex; gap: .5rem; margin: 3rem 0; justify-content: center; align-items: center; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: .4rem .85rem; border: 1px solid var(--border); border-radius: 5px; font-size: .875rem; color: var(--text); }
        .pagination a:hover { background: var(--primary); color: #fff; border-color: var(--primary); text-decoration: none; }
        .pagination .current { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* ── Footer ── */
        .site-footer { border-top: 1px solid var(--border); padding: 2.5rem 1.5rem; text-align: center; color: var(--muted); font-size: .85rem; background: var(--bg-alt); }
        .footer-inner { max-width: 1100px; margin: auto; display: flex; flex-direction: column; gap: .75rem; }
        .footer-links { display: flex; gap: 1.25rem; justify-content: center; flex-wrap: wrap; }
        .footer-links a { color: var(--muted); }
        .footer-links a:hover { color: var(--primary); }

        /* ── Responsive ── */
        @media (max-width: 640px) {
            .site-main { margin-top: 1.5rem; }
            .post-header h1 { font-size: 1.5rem; }
            .post-card h2 { font-size: 1.25rem; }
            .nav-api-link { display: none; }
        }

        /* ── Accessibility ── */
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
        :focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        a:focus-visible { text-decoration: none; }
    </style>
</head>
<?php
$extraClasses = '';
if (isset($post) && !empty($post['id'])) {
    $extraClasses = ' postid-' . $post['id'] . ' post-' . $post['slug'];
} elseif (isset($page) && !empty($page['id'])) {
    $extraClasses = ' page-id-' . $page['id'] . ' page-' . $page['slug'];
}
?>
<body class="<?= htmlspecialchars(($bodyClass ?? '') . $extraClasses) ?>">

<a href="#main" class="sr-only">Aller au contenu principal</a>

<header class="site-header">
    <div class="inner">
        <div class="site-title">
            <a href="/" rel="home"><?= htmlspecialchars($config['site_name']) ?></a>
        </div>
        <nav class="main-nav" aria-label="Navigation principale">
            <a href="/" <?= ($bodyClass ?? '') === 'home' ? 'aria-current="page"' : '' ?>>Accueil</a>
            <?php foreach ($pages as $p): ?>
                <a href="/<?= htmlspecialchars($p['slug']) ?>/"
                   <?= isset($currentSlug) && $currentSlug === $p['slug'] ? 'aria-current="page"' : '' ?>>
                    <?= htmlspecialchars($p['title']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <a href="/wp-json/wp/v2/posts" class="nav-api-link" title="API REST WordPress">/wp-json/</a>
    </div>
</header>

<main class="site-main" id="main" tabindex="-1">
    <?= $mainContent ?? '' ?>
</main>

<footer class="site-footer">
    <div class="footer-inner">
        <nav class="footer-links" aria-label="Liens de pied de page">
            <a href="/">Accueil</a>
            <?php foreach ($pages as $p): ?>
                <a href="/<?= htmlspecialchars($p['slug']) ?>/"><?= htmlspecialchars($p['title']) ?></a>
            <?php endforeach; ?>
            <a href="/feed/">RSS</a>
            <a href="/sitemap.xml">Sitemap</a>
        </nav>
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($config['site_name']) ?> — Tous droits réservés</p>
    </div>
</footer>

</body>
</html>
