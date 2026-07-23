<!DOCTYPE html>
<html lang="<?= htmlspecialchars(strtolower(substr($config['language'] ?? 'fr', 0, 2))) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?= $seo->metaTags($seoData) ?>
<?= $seo->jsonLd($seoData) ?>
<?= $seo->hreflangLinks($seoData) ?>
    <link rel="alternate" type="application/rss+xml"
          title="<?= htmlspecialchars($config['site_name']) ?> — Flux RSS"
          href="<?= htmlspecialchars($config['site_url']) ?>/feed/">
    <meta name="generator" content="WordPress 6.7.2">
    <meta name="theme-color" content="<?= htmlspecialchars($config['theme_color'] ?? '#2563eb') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════
           DESIGN SYSTEM
        ═══════════════════════════════════════ */
        :root {
            --primary:       <?= htmlspecialchars($config['theme_color'] ?? '#2563eb') ?>;
            --primary-dark:  color-mix(in srgb, var(--primary) 76%, #000);
            --primary-light: color-mix(in srgb, var(--primary) 11%, #fff);
            --primary-glow:  color-mix(in srgb, var(--primary) 28%, transparent);
            --text:          #0f172a;
            --text-muted:    #64748b;
            --border:        #e2e8f0;
            --bg:            #ffffff;
            --bg-alt:        #f8fafc;
            --dark:          #0f172a;
            --dark-2:        #1e293b;
            --shadow-sm:     0 1px 3px rgba(0,0,0,.06);
            --shadow:        0 4px 6px -1px rgba(0,0,0,.07), 0 2px 4px -1px rgba(0,0,0,.05);
            --shadow-lg:     0 10px 15px -3px rgba(0,0,0,.08), 0 4px 6px -2px rgba(0,0,0,.04);
            --shadow-xl:     0 20px 25px -5px rgba(0,0,0,.1), 0 10px 10px -5px rgba(0,0,0,.04);
            --radius-sm:     6px;
            --radius:        12px;
            --radius-lg:     18px;
            --radius-xl:     28px;
            --max-w:         1200px;
            --ease:          cubic-bezier(.4, 0, .2, 1);
            --t:             .25s var(--ease);
            --t-lg:          .4s var(--ease);
        }

        /* ── Reset ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text); background: var(--bg);
            line-height: 1.75;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        a { color: var(--primary); text-decoration: none; }
        img { max-width: 100%; height: auto; display: block; }
        button { font-family: inherit; cursor: pointer; }
        ul { list-style: none; }

        /* ── Utilities ── */
        .container    { max-width: var(--max-w); margin: 0 auto; padding: 0 1.5rem; }
        .sr-only      { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

        /* ── Section headers ── */
        .section-label {
            display: inline-block; font-size: .72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .1em;
            color: var(--primary); background: var(--primary-light);
            padding: .3rem .9rem; border-radius: 100px; margin-bottom: 1rem;
        }
        .section-header { margin-bottom: 3rem; }
        .section-header h2 {
            font-size: clamp(1.6rem, 3vw, 2.4rem); font-weight: 800;
            letter-spacing: -.03em; line-height: 1.2; margin-bottom: .6rem;
        }
        .section-header p { font-size: 1.05rem; color: var(--text-muted); }
        .section-header.center { text-align: center; }
        .section-header.center p { max-width: 560px; margin: 0 auto; }

        /* ── Scroll reveal ── */
        .reveal {
            opacity: 0; transform: translateY(24px);
            transition: opacity .65s var(--ease), transform .65s var(--ease);
        }
        .reveal.visible      { opacity: 1; transform: none; }
        .reveal-d1           { transition-delay: .1s; }
        .reveal-d2           { transition-delay: .2s; }
        .reveal-d3           { transition-delay: .3s; }
        .reveal-d4           { transition-delay: .4s; }

        /* ── Buttons ── */
        .btn {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .8rem 1.8rem; border-radius: 100px;
            font-size: .9rem; font-weight: 600; line-height: 1; border: 2px solid transparent;
            transition: background var(--t), transform var(--t), box-shadow var(--t),
                        color var(--t), border-color var(--t);
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover {
            background: var(--primary-dark); transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--primary-glow); text-decoration: none; color: #fff;
        }
        .btn-outline-light {
            background: transparent; color: #fff;
            border-color: rgba(255,255,255,.35);
        }
        .btn-outline-light:hover {
            background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.8);
            transform: translateY(-2px); text-decoration: none; color: #fff;
        }

        /* ── Tags ── */
        .tag {
            display: inline-block; background: var(--bg-alt); border: 1px solid var(--border);
            padding: .2rem .7rem; border-radius: 100px;
            font-size: .75rem; font-weight: 500; color: var(--text-muted);
            transition: all var(--t);
        }
        .tag:hover { background: var(--primary-light); color: var(--primary); border-color: transparent; text-decoration: none; }

        /* ── Breadcrumb ── */
        .breadcrumb {
            font-size: .8rem; color: var(--text-muted);
            display: flex; gap: .4rem; flex-wrap: wrap; align-items: center;
            margin-bottom: 1.5rem;
        }
        .breadcrumb a { color: var(--text-muted); transition: color var(--t); }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb [aria-hidden] { color: var(--border); }

        /* ── Pagination ── */
        .pagination { display: flex; gap: .5rem; margin: 4rem 0; justify-content: center; flex-wrap: wrap; }
        .pagination a, .pagination span {
            padding: .5rem 1rem; border: 1px solid var(--border);
            border-radius: var(--radius-sm); font-size: .875rem; color: var(--text);
            transition: all var(--t);
        }
        .pagination a:hover { background: var(--primary); color: #fff; border-color: var(--primary); text-decoration: none; }
        .pagination .current { background: var(--primary); color: #fff; border-color: var(--primary); }

        /* ═══════════════════════════════════════
           HEADER
        ═══════════════════════════════════════ */
        .site-header {
            position: sticky; top: 0; z-index: 200;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid var(--border);
            transition: box-shadow var(--t-lg);
        }
        .site-header.scrolled { box-shadow: var(--shadow); }
        .header-inner {
            display: flex; align-items: center; gap: 1rem;
            padding: .9rem 1.5rem; max-width: var(--max-w); margin: 0 auto;
        }
        .site-logo {
            font-size: 1.2rem; font-weight: 800; color: var(--text);
            letter-spacing: -.03em; white-space: nowrap; flex-shrink: 0;
            transition: color var(--t);
        }
        .site-logo:hover { color: var(--primary); text-decoration: none; }
        .main-nav { display: flex; align-items: center; gap: .25rem; flex: 1; flex-wrap: wrap; }
        .main-nav a {
            padding: .4rem .8rem; border-radius: var(--radius-sm);
            font-size: .875rem; font-weight: 500; color: var(--text-muted);
            transition: background var(--t), color var(--t); white-space: nowrap;
        }
        .main-nav a:hover, .main-nav a[aria-current] {
            background: var(--primary-light); color: var(--primary); text-decoration: none;
        }
        .header-cta {
            display: inline-flex; align-items: center; gap: .4rem;
            background: var(--primary); color: #fff !important;
            padding: .5rem 1.2rem; border-radius: 100px;
            font-size: .875rem; font-weight: 600; white-space: nowrap; flex-shrink: 0;
            transition: background var(--t), transform var(--t), box-shadow var(--t);
        }
        .header-cta:hover {
            background: var(--primary-dark); transform: translateY(-1px);
            box-shadow: 0 4px 12px var(--primary-glow); text-decoration: none;
        }
        .nav-toggle {
            display: none; background: none; border: none;
            padding: .4rem; margin-left: auto; flex-shrink: 0;
            flex-direction: column; gap: 5px;
        }
        .nav-toggle span {
            display: block; width: 22px; height: 2px; background: var(--text);
            border-radius: 2px; transition: transform var(--t), opacity var(--t);
        }
        .nav-toggle[aria-expanded="true"] span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .nav-toggle[aria-expanded="true"] span:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .nav-toggle[aria-expanded="true"] span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        /* ═══════════════════════════════════════
           HERO  (home.php)
        ═══════════════════════════════════════ */
        .hero {
            min-height: 88vh; display: flex; align-items: center;
            background: linear-gradient(
                135deg,
                var(--dark) 0%,
                var(--dark-2) 50%,
                color-mix(in srgb, var(--primary) 38%, var(--dark)) 100%
            );
            position: relative; overflow: hidden; padding: 6rem 1.5rem;
        }
        .hero::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(
                ellipse 65% 90% at 85% 45%,
                color-mix(in srgb, var(--primary) 22%, transparent),
                transparent
            );
        }

        /* ── Hero : fond image ── */
        .hero--img {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .hero--img::before { background: rgba(0, 0, 0, .52); }

        /* ── Hero : fond vidéo ── */
        .hero__video {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: 0; pointer-events: none;
        }
        .hero--video::before { background: rgba(0, 0, 0, .52); z-index: 1; }
        .hero--video .hero-inner { z-index: 2; }
        .hero::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0;
            height: 100px; pointer-events: none;
            background: linear-gradient(to bottom, transparent, var(--bg));
        }
        .hero-inner { position: relative; z-index: 1; max-width: var(--max-w); margin: 0 auto; width: 100%; }
        .hero-content { max-width: 680px; }

        @keyframes hero-enter {
            from { opacity: 0; transform: translateY(22px); }
            to   { opacity: 1; transform: none; }
        }
        .hero-content { animation: hero-enter .9s .1s both var(--ease); }

        .hero-badge {
            display: inline-flex; align-items: center; gap: .5rem;
            background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
            color: rgba(255,255,255,.9); font-size: .78rem; font-weight: 600;
            padding: .35rem 1rem; border-radius: 100px; margin-bottom: 1.5rem;
        }
        .hero-badge::before {
            content: ''; display: block; width: 6px; height: 6px;
            border-radius: 50%; background: var(--primary); flex-shrink: 0;
        }
        .hero h1 {
            font-size: clamp(2.2rem, 5.5vw, 4.2rem);
            font-weight: 800; letter-spacing: -.04em; line-height: 1.08;
            color: #fff; margin-bottom: 1.25rem;
        }
        .hero-desc {
            font-size: clamp(1rem, 2vw, 1.2rem); color: rgba(255,255,255,.68);
            margin-bottom: 2.5rem; max-width: 540px; line-height: 1.7;
        }
        .hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 3rem; }
        .hero-trust   { display: flex; gap: 2rem; flex-wrap: wrap; list-style: none; }
        .trust-item   { display: flex; align-items: center; gap: .5rem; font-size: .85rem; color: rgba(255,255,255,.65); }
        .trust-check  {
            display: flex; align-items: center; justify-content: center;
            width: 18px; height: 18px; border-radius: 50%;
            background: var(--primary); color: #fff; font-size: .6rem; flex-shrink: 0;
        }

        /* ═══════════════════════════════════════
           FEATURES SECTION  (home.php)
        ═══════════════════════════════════════ */
        .features-section { padding: 6rem 0; background: var(--bg-alt); }
        .features-grid    { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .feature-card {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: var(--radius-lg); padding: 2rem;
            transition: transform var(--t), box-shadow var(--t), border-color var(--t);
        }
        .feature-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--primary-light); }
        .feature-icon {
            width: 52px; height: 52px; border-radius: var(--radius);
            background: var(--primary-light); color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 1.25rem; font-style: normal;
        }
        .feature-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: .6rem; letter-spacing: -.02em; }
        .feature-card p   { font-size: .9rem; color: var(--text-muted); line-height: 1.7; }

        /* ═══════════════════════════════════════
           POSTS SECTION  (home.php)
        ═══════════════════════════════════════ */
        .posts-section { padding: 6rem 0; }
        .posts-grid    { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .post-card {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: var(--radius-lg); overflow: hidden;
            display: flex; flex-direction: column;
            transition: transform var(--t), box-shadow var(--t);
        }
        .post-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
        .post-card__thumb-wrap { aspect-ratio: 16/9; overflow: hidden; background: var(--bg-alt); }
        .post-card__thumb      { width: 100%; height: 100%; object-fit: cover; transition: transform .5s var(--ease); }
        .post-card:hover .post-card__thumb { transform: scale(1.05); }
        .post-card__thumb-placeholder {
            width: 100%; height: 100%;
            background: linear-gradient(135deg, var(--primary-light), var(--bg-alt));
        }
        .post-card__body { padding: 1.5rem; display: flex; flex-direction: column; flex: 1; }
        .post-card__meta { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .75rem; }
        .post-card__date { font-size: .75rem; color: var(--text-muted); }
        .post-card h2    { font-size: 1.05rem; font-weight: 700; line-height: 1.35; margin-bottom: .6rem; letter-spacing: -.02em; }
        .post-card h2 a  { color: var(--text); transition: color var(--t); }
        .post-card h2 a:hover  { color: var(--primary); }
        .post-card__excerpt    { font-size: .875rem; color: var(--text-muted); line-height: 1.65; margin-bottom: 1rem; flex: 1; }
        .post-card__link       { font-size: .8rem; font-weight: 600; color: var(--primary); display: inline-flex; align-items: center; gap: .3rem; transition: gap var(--t); }
        .post-card__link:hover { gap: .6rem; }

        /* ═══════════════════════════════════════
           CTA SECTION  (home.php)
        ═══════════════════════════════════════ */
        .cta-section { padding: 7rem 0; background: var(--dark); position: relative; overflow: hidden; }
        .cta-section::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(
                ellipse 80% 120% at 50% 130%,
                color-mix(in srgb, var(--primary) 22%, transparent),
                transparent
            );
        }
        .cta-inner { position: relative; text-align: center; }
        .cta-inner h2 {
            font-size: clamp(1.7rem, 3.5vw, 2.8rem); font-weight: 800;
            color: #fff; letter-spacing: -.04em; line-height: 1.15; margin-bottom: 1rem;
        }
        .cta-inner p     { color: rgba(255,255,255,.62); font-size: 1.05rem; margin-bottom: 2.5rem; max-width: 500px; margin-left: auto; margin-right: auto; }
        .cta-actions     { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }

        /* ═══════════════════════════════════════
           SINGLE POST
        ═══════════════════════════════════════ */
        .post-hero {
            background: var(--dark); padding: 5rem 1.5rem 4rem;
            position: relative; overflow: hidden;
        }
        .post-hero::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(
                ellipse 50% 80% at 80% 40%,
                color-mix(in srgb, var(--primary) 15%, transparent),
                transparent
            );
        }
        .post-hero::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0;
            height: 80px; background: linear-gradient(to bottom, transparent, var(--bg));
        }
        .post-hero-inner { position: relative; z-index: 1; max-width: 800px; margin: 0 auto; }
        .post-hero-inner .breadcrumb a   { color: rgba(255,255,255,.45); }
        .post-hero-inner .breadcrumb a:hover { color: rgba(255,255,255,.9); }
        .post-hero-inner .breadcrumb     { color: rgba(255,255,255,.5); }
        .post-hero-inner .breadcrumb [aria-hidden] { color: rgba(255,255,255,.2); }
        .post-hero-meta  { display: flex; gap: .75rem; flex-wrap: wrap; align-items: center; margin-bottom: 1rem; }
        .post-hero-meta time { font-size: .8rem; color: rgba(255,255,255,.45); }
        .post-hero-meta .author { font-size: .8rem; color: rgba(255,255,255,.45); }
        .post-hero-meta .tag   { background: rgba(255,255,255,.1); border-color: rgba(255,255,255,.15); color: rgba(255,255,255,.75); }
        .post-hero-meta .tag:hover { background: var(--primary); border-color: var(--primary); color: #fff; }
        .post-hero-inner h1 {
            font-size: clamp(1.8rem, 4vw, 3.2rem); font-weight: 800;
            color: #fff; letter-spacing: -.04em; line-height: 1.12;
        }
        .post-body        { max-width: 800px; margin: 0 auto; padding: 3rem 1.5rem 5rem; }
        .post-featured    { width: 100%; max-height: 480px; object-fit: cover; border-radius: var(--radius-lg); margin-bottom: 3rem; box-shadow: var(--shadow-xl); }
        .post-content     { font-size: 1.05rem; line-height: 1.85; }
        .post-content h2, .post-content h3, .post-content h4 { font-weight: 700; letter-spacing: -.03em; line-height: 1.25; margin: 2.5rem 0 1rem; }
        .post-content h2  { font-size: 1.7rem; }
        .post-content h3  { font-size: 1.35rem; }
        .post-content h4  { font-size: 1.1rem; }
        .post-content p   { margin-bottom: 1.5rem; }
        .post-content ul, .post-content ol { margin: 0 0 1.5rem 1.75rem; }
        .post-content li  { margin-bottom: .4rem; }
        .post-content a   { color: var(--primary); text-decoration: underline; }
        .post-content blockquote {
            border-left: 4px solid var(--primary); padding: 1rem 1.5rem;
            background: var(--primary-light); margin: 2rem 0;
            border-radius: 0 var(--radius) var(--radius) 0;
        }
        .post-content pre {
            background: var(--dark); color: #e2e8f0; padding: 1.5rem;
            border-radius: var(--radius); overflow-x: auto; margin: 2rem 0;
            font-size: .875rem; line-height: 1.65;
        }
        .post-content code      { font-family: 'Fira Code', 'Cascadia Code', monospace; font-size: .9em; }
        .post-content p code, .post-content li code {
            background: var(--bg-alt); padding: .1rem .4rem;
            border-radius: 4px; border: 1px solid var(--border);
        }
        .post-content img       { border-radius: var(--radius); margin: 2rem 0; }
        .post-content hr        { border: none; border-top: 1px solid var(--border); margin: 2.5rem 0; }
        .post-content table     { width: 100%; border-collapse: collapse; margin: 2rem 0; font-size: .9rem; }
        .post-content th, .post-content td { padding: .75rem 1rem; border: 1px solid var(--border); text-align: left; }
        .post-content th        { background: var(--bg-alt); font-weight: 600; }
        .post-content tr:nth-child(even) td { background: var(--bg-alt); }
        .post-taxonomy          { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--border); }

        /* ═══════════════════════════════════════
           PAGE (statique)
        ═══════════════════════════════════════ */
        .page-header { padding: 4rem 1.5rem 2.5rem; background: var(--bg-alt); border-bottom: 1px solid var(--border); }
        .page-header-inner { max-width: 800px; margin: 0 auto; }
        .page-header h1 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; letter-spacing: -.04em; }
        .page-body { max-width: 800px; margin: 0 auto; padding: 3rem 1.5rem 5rem; }

        /* ═══════════════════════════════════════
           ARCHIVE (zone géo)
        ═══════════════════════════════════════ */
        .archive-header { padding: 4rem 1.5rem 3rem; background: var(--bg-alt); border-bottom: 1px solid var(--border); }
        .archive-header-inner { max-width: var(--max-w); margin: 0 auto; }
        .archive-header h1 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; letter-spacing: -.04em; }
        .archive-body  { max-width: var(--max-w); margin: 0 auto; padding: 3rem 1.5rem 5rem; }
        .archive-desc  { max-width: 720px; margin-bottom: 3rem; }
        .archive-desc h2, .archive-desc h3 { font-weight: 700; margin: 2rem 0 .75rem; }
        .archive-desc p { margin-bottom: 1.25rem; color: var(--text-muted); line-height: 1.8; }
        .archive-section-title { font-size: 1.35rem; font-weight: 700; letter-spacing: -.03em; margin-bottom: 1.5rem; padding-bottom: .75rem; border-bottom: 2px solid var(--border); }
        .zones-grid    { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 3rem; }
        .zone-card {
            background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);
            padding: 1.25rem 1.5rem; font-weight: 600; color: var(--text);
            transition: border-color var(--t), color var(--t), transform var(--t);
            display: flex; align-items: center; justify-content: space-between;
        }
        .zone-card:hover { border-color: var(--primary); color: var(--primary); transform: translateX(4px); text-decoration: none; }
        .zone-card::after { content: '→'; font-size: 1rem; transition: transform var(--t); }
        .zone-card:hover::after { transform: translateX(4px); }
        .artisans-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .artisan-card  {
            background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-lg);
            padding: 1.5rem; transition: transform var(--t), box-shadow var(--t), border-color var(--t);
        }
        .artisan-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--primary-light); }
        .artisan-card h3       { font-size: 1rem; font-weight: 700; margin-bottom: .5rem; }
        .artisan-card h3 a     { color: var(--text); transition: color var(--t); }
        .artisan-card h3 a:hover { color: var(--primary); }
        .artisan-card p        { font-size: .875rem; color: var(--text-muted); margin-bottom: 1rem; line-height: 1.65; }
        .artisan-card__link    { font-size: .8rem; font-weight: 600; color: var(--primary); display: inline-flex; align-items: center; gap: .3rem; transition: gap var(--t); }
        .artisan-card__link:hover { gap: .6rem; }

        /* ═══════════════════════════════════════
           FOOTER
        ═══════════════════════════════════════ */
        .site-footer { background: var(--dark); color: #94a3b8; padding: 5rem 1.5rem 2rem; }
        .footer-grid {
            max-width: var(--max-w); margin: 0 auto;
            display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 4rem;
            margin-bottom: 4rem;
        }
        .footer-brand-name { font-size: 1.2rem; font-weight: 800; color: #fff; letter-spacing: -.03em; margin-bottom: 1rem; }
        .footer-brand p    { font-size: .875rem; line-height: 1.7; max-width: 280px; }
        .footer-col h3     { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #fff; margin-bottom: 1rem; }
        .footer-col a, .footer-col address { display: block; font-size: .875rem; color: #94a3b8; margin-bottom: .6rem; font-style: normal; line-height: 1.6; }
        .footer-col a:hover { color: #fff; text-decoration: none; }
        .footer-bottom {
            max-width: var(--max-w); margin: 0 auto;
            padding-top: 2rem; border-top: 1px solid #1e293b;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 1rem; font-size: .8rem;
        }
        .footer-bottom-links { display: flex; gap: 1.5rem; }
        .footer-bottom-links a { color: #64748b; }
        .footer-bottom-links a:hover { color: #fff; }

        /* ═══════════════════════════════════════
           404
        ═══════════════════════════════════════ */
        .error-page { display: flex; align-items: center; justify-content: center; min-height: 70vh; text-align: center; padding: 4rem 1.5rem; }
        .error-code { font-size: clamp(6rem, 15vw, 10rem); font-weight: 900; line-height: 1; color: var(--border); letter-spacing: -.05em; margin-bottom: 1rem; }
        .error-page h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 1rem; }
        .error-page p  { color: var(--text-muted); margin-bottom: 2rem; max-width: 400px; }

        /* ═══════════════════════════════════════
           RESPONSIVE
        ═══════════════════════════════════════ */
        @media (max-width: 1024px) {
            .features-grid, .posts-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .footer-brand { grid-column: 1 / -1; }
            .footer-brand p { max-width: 100%; }
        }

        @media (max-width: 768px) {
            .main-nav {
                display: none; position: fixed;
                top: 61px; left: 0; right: 0;
                background: var(--bg); border-bottom: 1px solid var(--border);
                padding: 1.5rem; flex-direction: column; align-items: flex-start;
                box-shadow: var(--shadow-lg); z-index: 199;
            }
            .main-nav.open { display: flex; }
            .nav-toggle    { display: flex; }
            .header-cta    { display: none; }
            .features-grid, .posts-grid { grid-template-columns: 1fr; }
            .footer-grid   { grid-template-columns: 1fr; gap: 2.5rem; }
            .footer-brand  { grid-column: auto; }
            .footer-bottom { flex-direction: column; align-items: flex-start; }
            .hero-trust    { gap: 1rem; }
            .cta-actions, .hero-actions { flex-direction: column; align-items: flex-start; }
            .cta-actions   { align-items: center; }
        }

        /* ── Accessibility ── */
        :focus-visible { outline: 2px solid var(--primary); outline-offset: 3px; }
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

<header class="site-header" id="site-header">
    <div class="header-inner">
        <a href="/" class="site-logo" rel="home">
            <?= htmlspecialchars($config['site_name']) ?>
        </a>
        <nav class="main-nav" id="main-nav" aria-label="Navigation principale">
            <a href="/" <?= ($bodyClass ?? '') === 'home' ? 'aria-current="page"' : '' ?>>Accueil</a>
            <?php foreach ($pages as $p): ?>
                <a href="/<?= htmlspecialchars($p['slug']) ?>/"
                   <?= isset($currentSlug) && $currentSlug === $p['slug'] ? 'aria-current="page"' : '' ?>>
                    <?= htmlspecialchars($p['title']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php if (!empty($config['phone'])): ?>
        <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', $config['phone'])) ?>"
           class="header-cta">
            <?= htmlspecialchars($config['phone']) ?>
        </a>
        <?php endif; ?>
        <button class="nav-toggle" id="nav-toggle" aria-label="Ouvrir le menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<main class="site-main" id="main" tabindex="-1">
    <?= $mainContent ?? '' ?>
</main>

<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <p class="footer-brand-name"><?= htmlspecialchars($config['site_name']) ?></p>
            <p><?= htmlspecialchars($config['site_description']) ?></p>
        </div>
        <div class="footer-col">
            <h3>Navigation</h3>
            <a href="/">Accueil</a>
            <?php foreach ($pages as $p): ?>
                <a href="/<?= htmlspecialchars($p['slug']) ?>/"><?= htmlspecialchars($p['title']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="footer-col">
            <h3>Contact</h3>
            <?php if (!empty($config['phone'])): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', $config['phone'])) ?>">
                    <?= htmlspecialchars($config['phone']) ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($config['email'])): ?>
                <a href="mailto:<?= htmlspecialchars($config['email']) ?>">
                    <?= htmlspecialchars($config['email']) ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($config['address'])): ?>
                <address><?= nl2br(htmlspecialchars($config['address'])) ?></address>
            <?php endif; ?>
            <a href="/feed/">Flux RSS</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($config['site_name']) ?> &mdash; Tous droits r&eacute;serv&eacute;s</p>
        <nav class="footer-bottom-links" aria-label="Liens secondaires">
            <a href="/sitemap.xml">Sitemap</a>
            <a href="/feed/">RSS</a>
        </nav>
    </div>
</footer>

<script>
(function () {
    // ── Header shadow on scroll ──
    var header = document.getElementById('site-header');
    window.addEventListener('scroll', function () {
        header.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });

    // ── Mobile nav toggle ──
    var toggle = document.getElementById('nav-toggle');
    var nav    = document.getElementById('main-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
        });
        document.addEventListener('click', function (e) {
            if (!header.contains(e.target) && nav.classList.contains('open')) {
                nav.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── Scroll reveal ──
    var reveals = document.querySelectorAll('.reveal');
    if (reveals.length) {
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
            reveals.forEach(function (el) { observer.observe(el); });
        } else {
            reveals.forEach(function (el) { el.classList.add('visible'); });
        }
    }
}());
</script>

</body>
</html>
