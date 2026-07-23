<?php
/** @var array   $posts       */
/** @var array   $config      */
/** @var Seo     $seo         */
/** @var array   $seoData     */
/** @var Content $content     */
/** @var int     $currentPage */
/** @var int     $totalPages  */
$pages = $content->getPages();

$_hex = ltrim($config['theme_color'] ?? '#2563eb', '#');
$_ph  = 'https://placehold.eolem.com/600x338.webp'
      . '?gradient=linear&bg=' . $_hex . '&bg2=1e293b&angle=135&fg=ffffff&fit=stable';

// ── Données de config avec valeurs par défaut ─────────────────────────────────
$_rating       = $config['rating']        ?? '4.9/5';
$_rating_count = $config['rating_count']  ?? '';
$_cta_url      = $config['cta_url']       ?? '/contact/';
$_cta_text     = $config['cta_text']      ?? 'Demander un devis gratuit';
$_cta_text2    = $config['cta_text2']     ?? 'Explorer par région';
$_cta_url2     = $config['cta_url2']      ?? '#regions';
$_hero_bg      = $config['hero_bg_image'] ?? null;

$_service_types  = $config['service_types']  ?? [];
$_services       = $config['services']       ?? [];
$_popular_cities = $config['popular_cities'] ?? [];
$_regions        = $config['regions']        ?? [];
$_advantages     = $config['advantages']     ?? [];
$_faqs           = $config['faqs']           ?? [];

// Widget devis
$_devis_widget_id   = $config['devis_widget_id']   ?? null;
$_devis_partner_id  = $config['devis_partner_id']  ?? null;
$_devis_category_id = $config['devis_category_id'] ?? null;
$_devis_title       = $config['devis_title']       ?? 'Demandez vos devis';
$_devis_subtitle    = $config['devis_subtitle']    ?? 'Recevez jusqu\'à 3 devis gratuits de professionnels qualifiés près de chez vous';
$_devis_link        = $config['devis_link']        ?? $_cta_url;

$_search_enabled = $config['search_enabled'] ?? false;

ob_start();
?>

<?php if ($currentPage === 1): ?>

<!-- ══════════════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════════════ -->
<section class="relative overflow-hidden">

    <!-- Fond -->
    <div class="absolute inset-0">
        <?php if ($_hero_bg): ?>
        <img src="<?= htmlspecialchars($_hero_bg) ?>"
             alt="<?= htmlspecialchars($config['hero_title'] ?? $config['site_name']) ?>"
             class="w-full h-full object-cover">
        <?php else: ?>
        <div class="w-full h-full" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, <?= htmlspecialchars($config['theme_color'] ?? '#2563eb') ?>66 100%)"></div>
        <?php endif; ?>
        <div class="absolute inset-0 bg-gradient-to-br from-primary-900/90 via-primary-800/85 to-primary-700/80"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28 relative">
        <div class="text-center">

            <!-- Badge -->
            <?php if (!empty($config['hero_badge']) || !empty($_rating)): ?>
            <div class="inline-flex items-center bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium mb-6 backdrop-blur-sm">
                <?php if (!empty($_rating)): ?>
                <span class="text-yellow-300 mr-2">&#9733;</span>
                <?php endif; ?>
                <?= htmlspecialchars($config['hero_badge'] ?? ($_rating . ($_rating_count ? ' sur ' . $_rating_count : ''))) ?>
            </div>
            <?php endif; ?>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-6 leading-tight">
                <?= htmlspecialchars($config['hero_title'] ?? $config['site_name']) ?>
                <?php if (!empty($config['hero_title_highlight'])): ?>
                <br><span class="text-primary-300"><?= htmlspecialchars($config['hero_title_highlight']) ?></span>
                <?php endif; ?>
            </h1>

            <p class="text-xl text-white/90 max-w-2xl mx-auto mb-10">
                <?= htmlspecialchars($config['hero_subtitle'] ?? $config['site_description']) ?>
            </p>

            <?php if ($_search_enabled): ?>
            <!-- Barre de recherche ville -->
            <div class="mb-8">
                <div x-data="{
                    query: '',
                    results: [],
                    loading: false,
                    showResults: false,
                    selectedIndex: -1,
                    async search() {
                        if (this.query.length < 2) { this.results = []; this.showResults = false; return; }
                        this.loading = true; this.showResults = true;
                        try {
                            const r = await fetch('/api/search.php?q=' + encodeURIComponent(this.query));
                            this.results = await r.json();
                        } catch(e) { this.results = []; }
                        this.loading = false;
                    },
                    handleKeydown(e) {
                        if (!this.showResults || !this.results.length) return;
                        if (e.key === 'ArrowDown') { e.preventDefault(); this.selectedIndex = Math.min(this.selectedIndex + 1, this.results.length - 1); }
                        else if (e.key === 'ArrowUp') { e.preventDefault(); this.selectedIndex = Math.max(this.selectedIndex - 1, 0); }
                        else if (e.key === 'Enter' && this.selectedIndex >= 0) { e.preventDefault(); window.location.href = this.results[this.selectedIndex].url; }
                        else if (e.key === 'Escape') { this.showResults = false; this.selectedIndex = -1; }
                    }
                }" @click.away="showResults = false" class="relative w-full max-w-xl mx-auto">
                    <div class="relative">
                        <input type="text"
                               x-model="query"
                               @input.debounce.300ms="search()"
                               @focus="if (results.length > 0) showResults = true"
                               @keydown="handleKeydown"
                               placeholder="<?= htmlspecialchars($config['search_placeholder'] ?? 'Entrez votre ville ou code postal...') ?>"
                               class="w-full px-5 py-4 pr-12 text-lg bg-white border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:ring-4 focus:ring-primary-100 outline-none transition-all shadow-sm">
                        <div class="absolute right-4 top-1/2 -translate-y-1/2">
                            <svg x-show="!loading" class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <svg x-show="loading" x-cloak class="w-6 h-6 text-primary-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    <div x-show="showResults && (results.length > 0 || (query.length >= 2 && !loading))"
                         x-cloak x-transition
                         class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-50 max-h-96 overflow-y-auto">
                        <template x-if="results.length > 0">
                            <ul>
                                <template x-for="(result, index) in results" :key="result.url">
                                    <li>
                                        <button @click="window.location.href = result.url"
                                                @mouseenter="selectedIndex = index"
                                                :class="{ 'bg-primary-50': selectedIndex === index }"
                                                class="w-full flex items-center px-5 py-3 hover:bg-gray-50 transition-colors text-left">
                                            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center mr-4">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-900 truncate" x-text="result.nom"></p>
                                                <p class="text-sm text-gray-500" x-text="result.cp + (result.dept ? ' - ' + result.dept : '')"></p>
                                            </div>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </template>
                        <template x-if="results.length === 0 && query.length >= 2 && !loading">
                            <div class="px-5 py-8 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p>Aucune ville trouvée pour "<span x-text="query"></span>"</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Boutons CTA -->
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="<?= htmlspecialchars($_cta_url) ?>"
                   <?= str_starts_with($_cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?>
                   class="inline-flex items-center justify-center px-8 py-4 bg-white text-primary-600 font-semibold rounded-xl hover:bg-gray-50 transition-colors shadow-lg">
                    <?= htmlspecialchars($_cta_text) ?>
                </a>
                <?php if (!empty($_cta_url2)): ?>
                <a href="<?= htmlspecialchars($_cta_url2) ?>"
                   class="inline-flex items-center justify-center px-8 py-4 bg-white/10 text-white font-semibold rounded-xl hover:bg-white/20 transition-colors backdrop-blur-sm border border-white/20">
                    <?= htmlspecialchars($_cta_text2) ?>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Vague décorative -->
    <div class="absolute bottom-0 left-0 right-0 hero-wave">
        <svg viewBox="0 0 1440 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
            <path d="M0 50L48 45C96 40 192 30 288 35C384 40 480 60 576 65C672 70 768 60 864 50C960 40 1056 30 1152 35C1248 40 1344 60 1392 70L1440 80V100H1392C1344 100 1248 100 1152 100C1056 100 960 100 864 100C768 100 672 100 576 100C480 100 384 100 288 100C192 100 96 100 48 100H0V50Z"/>
        </svg>
    </div>
</section>

<?php if (!empty($_service_types)): ?>
<!-- ══════════════════════════════════════════════════════════════════
     TYPES DE SERVICES
══════════════════════════════════════════════════════════════════ -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                <?= htmlspecialchars($config['service_types_title'] ?? 'Tous nos services') ?>
            </h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                <?= htmlspecialchars($config['service_types_subtitle'] ?? '') ?>
            </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach ($_service_types as $stype): ?>
            <a href="<?= htmlspecialchars($stype['url'] ?? '/#services') ?>"
               class="group bg-white rounded-xl p-6 text-center border border-gray-200 hover:border-primary-500 hover:shadow-lg transition-all reveal">
                <span class="text-4xl block mb-3"><?= $stype['emoji'] ?? '🔧' ?></span>
                <h3 class="font-semibold text-gray-900 group-hover:text-primary-600 transition-colors text-sm">
                    <?= htmlspecialchars($stype['title'] ?? '') ?>
                </h3>
                <?php if (!empty($stype['subtitle'])): ?>
                <p class="text-xs text-gray-500 mt-1 hidden md:block"><?= htmlspecialchars($stype['subtitle']) ?></p>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($_services)): ?>
<!-- ══════════════════════════════════════════════════════════════════
     SERVICES DÉTAILLÉS
══════════════════════════════════════════════════════════════════ -->
<section id="services" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                <?= htmlspecialchars($config['services_title'] ?? 'Nos services') ?>
            </h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                <?= htmlspecialchars($config['services_subtitle'] ?? '') ?>
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <?php foreach ($_services as $i => $service): ?>
            <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-8 border border-gray-200 reveal reveal-d<?= ($i % 3) + 1 ?>">
                <span class="text-4xl block mb-4"><?= $service['emoji'] ?? '🔧' ?></span>
                <h3 class="text-xl font-bold text-gray-900 mb-3"><?= htmlspecialchars($service['title'] ?? '') ?></h3>
                <p class="text-gray-600 mb-6"><?= htmlspecialchars($service['desc'] ?? '') ?></p>
                <?php if (!empty($service['items'])): ?>
                <ul class="space-y-2">
                    <?php foreach ($service['items'] as $item): ?>
                    <li class="flex items-center text-sm text-gray-700">
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <?= htmlspecialchars($item) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($_popular_cities)): ?>
<!-- ══════════════════════════════════════════════════════════════════
     VILLES POPULAIRES
══════════════════════════════════════════════════════════════════ -->
<section id="regions" class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                <?= htmlspecialchars($config['cities_title'] ?? 'Professionnels dans les villes les plus recherchées') ?>
            </h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                <?= htmlspecialchars($config['cities_subtitle'] ?? '') ?>
            </p>
        </div>

        <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach ($_popular_cities as $i => $city): ?>
            <a href="<?= htmlspecialchars($city['url'] ?? '#') ?>"
               class="group bg-white rounded-xl p-4 border border-gray-200 hover:border-primary-500 hover:shadow-lg transition-all text-center reveal reveal-d<?= ($i % 3) + 1 ?>">
                <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center mx-auto mb-3 group-hover:bg-primary-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                    <?= htmlspecialchars($city['name'] ?? '') ?>
                </h3>
                <?php if (!empty($city['cp'])): ?>
                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($city['cp']) ?></p>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($_regions) || !empty($_cta_url)): ?>
        <div class="text-center mt-8">
            <?php if (!empty($config['cities_no_result'])): ?>
            <p class="text-gray-600 mb-4"><?= htmlspecialchars($config['cities_no_result']) ?></p>
            <?php endif; ?>
            <?php if (!empty($_regions)): ?>
            <div class="flex flex-wrap justify-center gap-4 mb-6">
                <?php foreach ($_regions as $region): ?>
                <a href="<?= htmlspecialchars($region['url']) ?>"
                   class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                    <?= htmlspecialchars($region['label']) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($_cta_url) ?>"
               <?= str_starts_with($_cta_url, 'http') ? 'target="_blank" rel="noopener"' : '' ?>
               class="inline-flex items-center mt-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <?= htmlspecialchars($config['cities_cta'] ?? $_cta_text) ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($_advantages)): ?>
<!-- ══════════════════════════════════════════════════════════════════
     AVANTAGES
══════════════════════════════════════════════════════════════════ -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12 reveal">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                <?= htmlspecialchars($config['advantages_title'] ?? 'Pourquoi nous choisir ?') ?>
            </h2>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <?php foreach ($_advantages as $i => $adv): ?>
            <div class="text-center reveal reveal-d<?= ($i % 3) + 1 ?>">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-primary-100 text-primary-600 text-3xl mb-4">
                    <?= $adv['emoji'] ?? '✅' ?>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($adv['title'] ?? '') ?></h3>
                <p class="text-gray-600"><?= htmlspecialchars($adv['desc'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════
     WIDGET DEVIS
══════════════════════════════════════════════════════════════════ -->
<section class="py-16 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <section id="devis" class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-2xl p-6 shadow-xl reveal">
            <div class="text-center mb-4">
                <h2 class="text-2xl font-bold text-white mb-2">
                    <?= htmlspecialchars($_devis_title) ?>
                </h2>
                <p class="text-primary-100"><?= htmlspecialchars($_devis_subtitle) ?></p>
            </div>

            <?php if ($_devis_widget_id && $_devis_partner_id && $_devis_category_id): ?>
            <div class="bg-white text-gray-900 rounded-xl p-2 shadow-inner">
                <div id="v<?= htmlspecialchars($_devis_widget_id) ?>"></div>
                <script>
                    vud_partenaire_id = '<?= htmlspecialchars($_devis_partner_id) ?>';
                    vud_categorie_id  = '<?= htmlspecialchars($_devis_category_id) ?>';
                    var vud_js = document.createElement('script');
                    vud_js.type = 'text/javascript';
                    vud_js.src  = '//www.viteundevis.com/<?= htmlspecialchars($_devis_widget_id) ?>/' + vud_partenaire_id + '/' + vud_categorie_id + '/';
                    var s = document.getElementsByTagName('script')[0];
                    s.parentNode.insertBefore(vud_js, s);
                </script>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-xl p-8 text-center shadow-inner">
                <a href="<?= htmlspecialchars($_devis_link) ?>"
                   <?= str_starts_with($_devis_link, 'http') ? 'target="_blank" rel="noopener"' : '' ?>
                   class="inline-flex items-center justify-center px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl text-lg transition-colors">
                    <?= htmlspecialchars($_cta_text) ?>
                </a>
            </div>
            <?php endif; ?>

            <div class="mt-4 flex flex-wrap justify-center gap-4 text-sm text-primary-100">
                <?php foreach ($config['devis_badges'] ?? ['100% gratuit', 'Sans engagement', 'Réponse rapide'] as $badge): ?>
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span><?= htmlspecialchars($badge) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>

<?php if (!empty($_faqs)): ?>
<!-- ══════════════════════════════════════════════════════════════════
     FAQ
══════════════════════════════════════════════════════════════════ -->
<section id="faq" class="py-16 bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <h2 class="text-2xl font-bold text-gray-900 mb-8 text-center reveal">
            <?= htmlspecialchars($config['faq_title'] ?? 'Questions fréquentes') ?>
        </h2>

        <div class="space-y-4 reveal" x-data="{ openItem: null }">
            <?php foreach ($_faqs as $i => $faq): ?>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button @click="openItem = openItem === <?= $i ?> ? null : <?= $i ?>"
                        class="w-full flex items-center justify-between p-5 text-left hover:bg-gray-50 transition-colors">
                    <span class="font-medium text-gray-900 pr-4"><?= htmlspecialchars($faq['question'] ?? '') ?></span>
                    <svg class="w-5 h-5 text-gray-500 flex-shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': openItem === <?= $i ?> }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openItem === <?= $i ?>"
                     x-cloak
                     x-collapse
                     class="border-t border-gray-100">
                    <p class="p-5 text-gray-600 leading-relaxed">
                        <?= htmlspecialchars($faq['answer'] ?? '') ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php endif; /* fin currentPage === 1 */ ?>

<!-- ══════════════════════════════════════════════════════════════════
     ARTICLES RÉCENTS
══════════════════════════════════════════════════════════════════ -->
<?php if (!empty($posts)): ?>
<section class="py-16 <?= $currentPage === 1 ? 'bg-gray-50' : 'bg-white' ?>">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="<?= $currentPage === 1 ? '' : 'text-center ' ?>mb-12 reveal">
            <?php if ($currentPage === 1): ?>
                <span class="inline-block text-xs font-bold uppercase tracking-widest text-primary-600 bg-primary-50 px-3 py-1 rounded-full mb-3">Nos conseils</span>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Derniers articles &amp; guides</h2>
                <p class="text-gray-600">Retrouvez nos conseils, actualités et guides pratiques.</p>
            <?php else: ?>
                <h2 class="text-3xl font-bold text-gray-900">Articles &mdash; Page <?= $currentPage ?></h2>
            <?php endif; ?>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($posts as $i => $post): ?>
            <?php
            $thumbSrc = !empty($post['featured_image_url'])
                ? (str_starts_with($post['featured_image_url'], 'http')
                    ? $post['featured_image_url']
                    : $config['site_url'] . $post['featured_image_url'])
                : $_ph;
            ?>
            <article class="bg-white rounded-2xl overflow-hidden border border-gray-200 hover:shadow-lg transition-all flex flex-col reveal reveal-d<?= ($i % 3) + 1 ?>"
                     itemscope itemtype="https://schema.org/BlogPosting">
                <a href="/<?= htmlspecialchars($post['slug']) ?>/" class="block aspect-video overflow-hidden" tabindex="-1" aria-hidden="true">
                    <img src="<?= htmlspecialchars($thumbSrc) ?>"
                         alt="<?= htmlspecialchars($post['featured_image_alt'] ?? '') ?>"
                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                         loading="lazy" width="600" height="338" itemprop="image"
                         onerror="this.onerror=null;this.src='<?= htmlspecialchars($_ph) ?>'">
                </a>
                <div class="p-5 flex flex-col flex-1">
                    <div class="flex items-center gap-2 flex-wrap mb-3">
                        <time class="text-xs text-gray-500" datetime="<?= htmlspecialchars($post['date']) ?>" itemprop="datePublished">
                            <?= date('j F Y', strtotime($post['date'])) ?>
                        </time>
                        <?php foreach (array_slice($post['categories'], 0, 2) as $cat): ?>
                        <a href="/category/<?= htmlspecialchars(Markdown::slugify($cat)) ?>/"
                           class="text-xs bg-primary-50 text-primary-600 px-2 py-0.5 rounded-full hover:bg-primary-100 transition-colors" itemprop="articleSection">
                            <?= htmlspecialchars($cat) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <h2 class="text-base font-bold text-gray-900 mb-2 leading-snug" itemprop="headline">
                        <a href="/<?= htmlspecialchars($post['slug']) ?>/" class="hover:text-primary-600 transition-colors" itemprop="url">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h2>
                    <p class="text-sm text-gray-600 line-clamp-3 flex-1 mb-4" itemprop="description">
                        <?= htmlspecialchars($post['excerpt']) ?>
                    </p>
                    <a href="/<?= htmlspecialchars($post['slug']) ?>/"
                       class="text-sm font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1 transition-colors">
                        Lire l'article
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="flex justify-center gap-2 mt-12" aria-label="Pagination des articles">
            <?php if ($currentPage > 1): ?>
            <a href="<?= $currentPage === 2 ? '/' : '/page/' . ($currentPage - 1) . '/' ?>" rel="prev"
               class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-primary-600 hover:text-white hover:border-primary-600 transition-colors">
                &larr; Précédent
            </a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $currentPage): ?>
            <span class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm" aria-current="page"><?= $i ?></span>
            <?php else: ?>
            <a href="<?= $i === 1 ? '/' : '/page/' . $i . '/' ?>"
               class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-primary-600 hover:text-white hover:border-primary-600 transition-colors">
                <?= $i ?>
            </a>
            <?php endif; ?>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
            <a href="/page/<?= $currentPage + 1 ?>/" rel="next"
               class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-primary-600 hover:text-white hover:border-primary-600 transition-colors">
                Suivant &rarr;
            </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

    </div>
</section>
<?php else: ?>
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-gray-500">Aucun article pour le moment.</p>
    </div>
</section>
<?php endif; ?>

<?php
$mainContent = ob_get_clean();
require __DIR__ . '/layout.php';
