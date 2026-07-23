<?php
// ── Générateur de palette Tailwind depuis un hex ─────────────────────────────
function ls2_palette(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $stops = [
        50  => ['w', 0.95], 100 => ['w', 0.90], 200 => ['w', 0.75],
        300 => ['w', 0.58], 400 => ['w', 0.30], 500 => ['w', 0.00],
        600 => ['b', 0.12], 700 => ['b', 0.28], 800 => ['b', 0.48],
        900 => ['b', 0.65], 950 => ['b', 0.80],
    ];
    $out = [];
    foreach ($stops as $name => [$dir, $factor]) {
        if ($factor == 0.0) {
            $out[$name] = '#' . $hex;
        } elseif ($dir === 'w') {
            $out[$name] = sprintf('#%02x%02x%02x',
                (int)round($r + (255 - $r) * $factor),
                (int)round($g + (255 - $g) * $factor),
                (int)round($b + (255 - $b) * $factor)
            );
        } else {
            $out[$name] = sprintf('#%02x%02x%02x',
                (int)round($r * (1 - $factor)),
                (int)round($g * (1 - $factor)),
                (int)round($b * (1 - $factor))
            );
        }
    }
    return $out;
}

$_primaryPalette = ls2_palette($config['theme_color'] ?? '#2563eb');
$_accentPalette  = ls2_palette($config['accent_color'] ?? '#f97316');
$_lang           = strtolower(substr($config['language'] ?? 'fr', 0, 2));

// ── Config header / footer ────────────────────────────────────────────────────
$_nav_cities          = $config['nav_cities']          ?? [];
$_nav_zones_label     = $config['nav_zones_label']     ?? 'Zones';
$_rating              = $config['rating']              ?? '4.9/5';
$_rating_count        = $config['rating_count']        ?? '';
$_cta_url             = $config['cta_url']             ?? '/contact/';
$_cta_text            = $config['cta_text']            ?? 'Demander un devis';
$_footer_nav          = $config['footer_nav_links']    ?? [];
$_footer_services     = $config['footer_services_links'] ?? [];
$_footer_regions      = $config['footer_regions']      ?? [];
$_footer_tags         = $config['footer_service_tags'] ?? [];
$_footer_partners     = $config['footer_partner_sites'] ?? [];
$_legal_url           = $config['legal_url']           ?? '/mentions-legales/';
$_privacy_url         = $config['privacy_url']         ?? '/politique-confidentialite/';
$_contact_url         = $config['contact_url']         ?? '/contact/';

// ── Extra body classes ────────────────────────────────────────────────────────
$_extraClasses = '';
if (isset($post) && !empty($post['id'])) {
    $_extraClasses = ' postid-' . $post['id'] . ' post-' . $post['slug'];
} elseif (isset($page) && !empty($page['id'])) {
    $_extraClasses = ' page-id-' . $page['id'] . ' page-' . $page['slug'];
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_lang) ?>">
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

    <!-- Tailwind CSS (play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: <?= json_encode($_primaryPalette) ?>,
                        accent:  <?= json_encode($_accentPalette)  ?>
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js + Collapse plugin -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }
        /* Post content typography */
        .prose h2, .prose h3, .prose h4 { font-weight: 700; margin: 2rem 0 .75rem; line-height: 1.25; letter-spacing: -.02em; }
        .prose h2 { font-size: 1.6rem; }
        .prose h3 { font-size: 1.3rem; }
        .prose p  { margin-bottom: 1.25rem; line-height: 1.85; }
        .prose ul, .prose ol { margin: 0 0 1.25rem 1.5rem; }
        .prose li { margin-bottom: .35rem; }
        .prose a  { text-decoration: underline; }
        .prose blockquote {
            border-left: 4px solid currentColor;
            padding: 1rem 1.5rem; margin: 1.5rem 0;
            background: rgba(0,0,0,.04); border-radius: 0 8px 8px 0;
        }
        .prose pre { background: #0f172a; color: #e2e8f0; padding: 1.5rem; border-radius: 8px; overflow-x: auto; margin: 1.5rem 0; font-size: .875rem; }
        .prose code { font-family: 'Fira Code', monospace; font-size: .9em; }
        .prose p code, .prose li code { background: #f1f5f9; padding: .1rem .4rem; border-radius: 4px; border: 1px solid #e2e8f0; }
        .prose img { border-radius: 12px; margin: 1.5rem 0; max-width: 100%; }
        .prose table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; font-size: .9rem; }
        .prose th, .prose td { padding: .75rem 1rem; border: 1px solid #e2e8f0; text-align: left; }
        .prose th { background: #f8fafc; font-weight: 600; }
        /* Scroll reveal */
        .reveal { opacity: 0; transform: translateY(20px); transition: opacity .6s ease, transform .6s ease; }
        .reveal.visible { opacity: 1; transform: none; }
        .reveal-d1 { transition-delay: .1s; }
        .reveal-d2 { transition-delay: .2s; }
        .reveal-d3 { transition-delay: .3s; }
        /* Wave SVG color */
        .hero-wave path { fill: #f9fafb; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased<?= htmlspecialchars(($_bodyClass ?? '') . $_extraClasses) ?>"
      x-data="{ mobileMenu: false }">

<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:px-4 focus:py-2 focus:bg-white focus:text-primary-600 focus:rounded-lg focus:shadow-lg">
    Aller au contenu principal
</a>

<!-- ══════════════════════════════════════════════════════════════════
     HEADER
══════════════════════════════════════════════════════════════════ -->
<header class="bg-white shadow-sm sticky top-0 z-50" id="site-header">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <!-- Logo -->
            <div class="flex items-center">
                <a href="/" class="flex items-center space-x-2.5 group">
                    <div class="w-9 h-9 bg-gradient-to-br from-primary-600 to-accent-500 rounded-lg flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                        <?php if (!empty($config['logo_svg'])): ?>
                            <?= $config['logo_svg'] ?>
                        <?php else: ?>
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3z"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <span class="font-semibold text-base tracking-tight">
                        <span class="text-gray-900"><?= htmlspecialchars(strtoupper($config['site_name'])) ?></span>
                    </span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-6">

                <?php if (!empty($_nav_cities)): ?>
                <!-- Dropdown zones -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false"
                            class="flex items-center space-x-1 text-gray-700 hover:text-primary-600 font-medium">
                        <span><?= htmlspecialchars($_nav_zones_label) ?></span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak x-transition
                         class="absolute top-full left-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-100 py-2 max-h-96 overflow-y-auto z-50">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Villes populaires</div>
                        <?php foreach ($_nav_cities as $city): ?>
                        <a href="<?= htmlspecialchars($city['url'] ?? '#') ?>"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">
                            <svg class="w-4 h-4 mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <?= htmlspecialchars($city['label'] ?? '') ?>
                        </a>
                        <?php endforeach; ?>
                        <div class="border-t border-gray-100 mt-2 pt-2">
                            <a href="/#regions" class="block px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50">
                                Voir toutes les régions &rarr;
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php foreach ($config['nav_links'] ?? [['label' => 'Services', 'url' => '/#services'], ['label' => 'FAQ', 'url' => '/#faq']] as $link): ?>
                <a href="<?= htmlspecialchars($link['url']) ?>"
                   class="text-gray-700 hover:text-primary-600 font-medium transition-colors">
                    <?= htmlspecialchars($link['label']) ?>
                </a>
                <?php endforeach; ?>

                <?php if (!empty($_rating)): ?>
                <div class="flex items-center space-x-1 bg-yellow-50 text-yellow-700 px-3 py-1 rounded-full text-sm">
                    <span class="text-yellow-500">&#9733;</span>
                    <span class="font-semibold"><?= htmlspecialchars($_rating) ?></span>
                </div>
                <?php endif; ?>

                <a href="<?= htmlspecialchars($_cta_url) ?>"
                   <?= str_starts_with($_cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?>
                   class="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-lg font-semibold transition-colors shadow-sm">
                    <?= htmlspecialchars($_cta_text) ?>
                </a>
            </div>

            <!-- Mobile burger -->
            <div class="md:hidden flex items-center">
                <button @click="mobileMenu = !mobileMenu"
                        class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                    <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenu" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile menu -->
    <div x-show="mobileMenu" x-cloak x-transition class="md:hidden border-t border-gray-100">
        <div class="px-4 py-4 space-y-3">
            <?php if (!empty($_nav_cities)): ?>
            <a href="/#regions" class="block py-2 text-gray-700 font-medium">Zones d'intervention</a>
            <?php endif; ?>
            <?php foreach ($config['nav_links'] ?? [['label' => 'Services', 'url' => '/#services'], ['label' => 'FAQ', 'url' => '/#faq']] as $link): ?>
            <a href="<?= htmlspecialchars($link['url']) ?>" class="block py-2 text-gray-700 font-medium">
                <?= htmlspecialchars($link['label']) ?>
            </a>
            <?php endforeach; ?>
            <div class="pt-3 border-t border-gray-100">
                <a href="<?= htmlspecialchars($_cta_url) ?>"
                   <?= str_starts_with($_cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?>
                   class="block w-full text-center bg-primary-600 hover:bg-primary-700 text-white px-5 py-3 rounded-lg font-semibold transition-colors">
                    <?= htmlspecialchars($_cta_text) ?>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- ══════════════════════════════════════════════════════════════════
     MAIN CONTENT
══════════════════════════════════════════════════════════════════ -->
<main id="main" tabindex="-1">
    <?= $mainContent ?? '' ?>
</main>

<!-- ══════════════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════════════ -->
<footer class="bg-gray-900 text-gray-300 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">

            <!-- Colonne marque -->
            <div class="md:col-span-1">
                <a href="/" class="flex items-center space-x-2.5 mb-4 group">
                    <div class="w-9 h-9 bg-gradient-to-br from-primary-600 to-accent-500 rounded-lg flex items-center justify-center">
                        <?php if (!empty($config['logo_svg'])): ?>
                            <?= $config['logo_svg'] ?>
                        <?php else: ?>
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3z"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                    <span class="font-semibold text-base tracking-tight">
                        <span class="text-white"><?= htmlspecialchars(strtoupper($config['site_name'])) ?></span>
                    </span>
                </a>
                <p class="text-sm text-gray-400 mb-4">
                    <?= htmlspecialchars($config['site_description']) ?>
                </p>
                <?php if (!empty($_rating)): ?>
                <div class="flex items-center space-x-1 text-sm">
                    <span class="text-yellow-400">&#9733;</span>
                    <span class="font-semibold text-white"><?= htmlspecialchars($_rating) ?></span>
                    <?php if (!empty($_rating_count)): ?>
                    <span class="text-gray-500">sur <?= htmlspecialchars($_rating_count) ?> avis</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Navigation -->
            <div>
                <h3 class="text-white font-semibold mb-4">Navigation</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="/" class="hover:text-white transition-colors">Accueil</a></li>
                    <?php if (!empty($_nav_cities)): ?>
                    <li><a href="/#regions" class="hover:text-white transition-colors">Toutes les régions</a></li>
                    <?php endif; ?>
                    <?php foreach ($_footer_nav as $link): ?>
                    <li><a href="<?= htmlspecialchars($link['url']) ?>" class="hover:text-white transition-colors"><?= htmlspecialchars($link['label']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="<?= htmlspecialchars($_cta_url) ?>" <?= str_starts_with($_cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?> class="hover:text-white transition-colors"><?= htmlspecialchars($_cta_text) ?></a></li>
                </ul>
            </div>

            <!-- Services -->
            <div>
                <h3 class="text-white font-semibold mb-4">Services</h3>
                <ul class="space-y-2 text-sm">
                    <?php foreach ($_footer_services as $link): ?>
                    <li><a href="<?= htmlspecialchars($link['url']) ?>" class="hover:text-white transition-colors"><?= htmlspecialchars($link['label']) ?></a></li>
                    <?php endforeach; ?>
                    <?php if (empty($_footer_services)): ?>
                    <li><a href="/#services" class="hover:text-white transition-colors">Nos services</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Régions -->
            <div>
                <h3 class="text-white font-semibold mb-4">Régions populaires</h3>
                <ul class="space-y-2 text-sm">
                    <?php foreach ($_footer_regions as $region): ?>
                    <li>
                        <a href="<?= htmlspecialchars($region['url']) ?>" class="hover:text-white transition-colors">
                            <?= htmlspecialchars($region['label']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($_footer_regions)): ?>
                    <li><a href="/feed/" class="hover:text-white transition-colors">Flux RSS</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php if (!empty($_footer_tags)): ?>
        <!-- Tags services -->
        <div class="border-t border-gray-800 mt-8 pt-8">
            <h3 class="text-white font-semibold mb-4">Services</h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($_footer_tags as $tag): ?>
                <span class="text-xs bg-gray-800 text-gray-400 px-3 py-1 rounded-full">
                    <?= htmlspecialchars($tag) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($_footer_partners)): ?>
        <!-- Sites partenaires -->
        <div class="border-t border-gray-800 mt-8 pt-8">
            <h3 class="text-white font-semibold mb-4"><?= htmlspecialchars($config['footer_partners_title'] ?? 'Nos annuaires partenaires') ?></h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($_footer_partners as $partner): ?>
                <a href="<?= htmlspecialchars($partner['url']) ?>"
                   title="<?= htmlspecialchars($partner['title'] ?? '') ?>"
                   target="_blank" rel="noopener"
                   class="text-xs bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700 px-3 py-1.5 rounded-full transition-colors">
                    <?= htmlspecialchars($partner['label']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Barre inférieure -->
        <div class="border-t border-gray-800 mt-8 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                <div class="text-sm text-gray-500">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($config['site_name']) ?>. Tous droits réservés.
                </div>
                <div class="flex items-center space-x-6 text-sm">
                    <a href="<?= htmlspecialchars($_legal_url) ?>" class="text-gray-500 hover:text-white transition-colors">Mentions légales</a>
                    <a href="<?= htmlspecialchars($_privacy_url) ?>" class="text-gray-500 hover:text-white transition-colors">Confidentialité</a>
                    <a href="<?= htmlspecialchars($_contact_url) ?>" class="text-gray-500 hover:text-white transition-colors">Contact</a>
                    <a href="/sitemap.xml" class="text-gray-500 hover:text-white transition-colors">Sitemap</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- ══════════════════════════════════════════════════════════════════
     FLOATING CTA (mobile — barre bas)
══════════════════════════════════════════════════════════════════ -->
<div x-data="{ visible: false }"
     x-show="visible"
     x-cloak
     @scroll.window="visible = window.scrollY > 300"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-0 left-0 right-0 z-50 p-4 bg-gradient-to-t from-white via-white to-transparent md:hidden">
    <a href="<?= htmlspecialchars($_cta_url) ?>"
       <?= str_starts_with($_cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?>
       class="flex items-center justify-center w-full px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-bold rounded-xl shadow-xl hover:from-primary-700 hover:to-primary-800 transition-all">
        <?= htmlspecialchars($_cta_text) ?>
        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
        </svg>
    </a>
</div>

<!-- FLOATING CTA (desktop — bas gauche) -->
<div x-data="{ visible: false }"
     x-show="visible"
     x-cloak
     @scroll.window="visible = window.scrollY > 500"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-90"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-90"
     class="hidden md:block fixed bottom-6 left-6 z-50">
    <a href="<?= htmlspecialchars($_cta_url) ?>"
       <?= str_starts_with($_cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?>
       class="flex items-center px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-full shadow-xl hover:from-primary-700 hover:to-primary-800 transition-all hover:shadow-2xl hover:scale-105">
        <?= htmlspecialchars($_cta_text) ?>
    </a>
</div>

<!-- Back to top -->
<button x-data="{ show: false }"
        x-show="show"
        x-cloak
        @scroll.window="show = window.scrollY > 500"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-6 right-6 bg-primary-600 hover:bg-primary-700 text-white p-3 rounded-full shadow-lg transition-all z-40">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
    </svg>
</button>

<!-- Scroll reveal JS -->
<script>
(function () {
    var reveals = document.querySelectorAll('.reveal');
    if (!reveals.length || !('IntersectionObserver' in window)) {
        reveals.forEach(function (el) { el.classList.add('visible'); });
        return;
    }
    var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
        });
    }, { threshold: 0.07, rootMargin: '0px 0px -30px 0px' });
    reveals.forEach(function (el) { obs.observe(el); });
}());
</script>

</body>
</html>
