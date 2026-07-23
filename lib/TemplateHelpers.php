<?php

/**
 * Helpers disponibles dans tous les templates via la variable $h.
 *
 * ── Récupération de données ──────────────────────────────────────────────────
 *   $h->posts()                        // tous les articles publiés
 *   $h->posts(['per_page' => 5])       // avec options (même API que Content::getPosts)
 *   $h->recentPosts(3)                 // N articles les plus récents
 *   $h->postsByCategory('seo')         // articles d'une catégorie
 *   $h->postsByTag('php')              // articles d'un tag
 *   $h->post('mon-slug')               // un article par slug ou ID
 *   $h->postsCount()                   // nombre total d'articles
 *
 *   $h->pages()                        // toutes les pages publiées
 *   $h->page('about')                  // une page par slug ou ID
 *
 *   $h->categories()                   // toutes les catégories
 *   $h->tags()                         // tous les tags
 *   $h->category('seo')                // une catégorie par slug
 *   $h->tag('php')                     // un tag par slug
 *
 * ── URLs ─────────────────────────────────────────────────────────────────────
 *   $h->siteUrl()                      // URL racine du site
 *   $h->siteUrl('media/image.jpg')     // URL absolue d'un chemin
 *   $h->postUrl($post)                 // lien d'un article
 *   $h->pageUrl($page)                 // lien d'une page statique
 *   $h->categoryUrl('seo')             // archive catégorie
 *   $h->tagUrl('php')                  // archive tag
 *   $h->pageNum(2)                     // URL de pagination (/page/2/)
 *   $h->feedUrl()                      // flux RSS
 *   $h->sitemapUrl()                   // sitemap.xml
 *
 * ── Images ───────────────────────────────────────────────────────────────────
 *   $h->imageUrl($post)                // URL absolue de l'image mise en avant
 *   $h->featuredImage($post)           // balise <img> complète, '' si aucune image
 *   $h->featuredImage($post, ['class' => 'hero', 'width' => 1200])
 *
 * ── Formatage ────────────────────────────────────────────────────────────────
 *   $h->title($post)                   // titre échappé
 *   $h->postDate($post)                // date formatée ("15 janvier 2024")
 *   $h->postDate($post, 'Y-m-d')       // format personnalisé
 *   $h->excerpt($post)                 // extrait court (30 mots par défaut)
 *   $h->excerpt($post, 15)             // extrait de 15 mots
 *   $h->e($string)                     // htmlspecialchars() raccourci
 *
 * ── Navigation ───────────────────────────────────────────────────────────────
 *   $h->pagination($currentPage, $totalPages)           // HTML de pagination
 *   $h->pagination($currentPage, $totalPages, 'pager')  // classe CSS personnalisée
 *
 * ── Configuration ────────────────────────────────────────────────────────────
 *   $h->config('site_name')            // valeur brute de config.php
 *   $h->siteName()                     // nom du site (échappé)
 *   $h->siteDescription()              // description (échappée)
 *   $h->author()                       // auteur par défaut (échappé)
 *   $h->themeUrl()                     // URL racine du thème actif
 */
class TemplateHelpers
{
    public function __construct(
        private readonly ContentInterface $content,
        private readonly array   $config
    ) {}

    // =========================================================================
    // POSTS
    // =========================================================================

    /**
     * Retourne les articles publiés.
     * Accepte les mêmes options que Content::getPosts() :
     *   per_page, page, category_slug, tag_slug
     */
    public function posts(array $args = []): array
    {
        return $this->content->getPosts(array_merge(['per_page' => 9999], $args));
    }

    /** Retourne les N articles les plus récents. */
    public function recentPosts(int $n = 5): array
    {
        return $this->content->getPosts(['per_page' => $n]);
    }

    /** Retourne un article par slug ou ID numérique. */
    public function post(string $idOrSlug): ?array
    {
        return $this->content->getPost($idOrSlug);
    }

    /** Retourne les articles d'une catégorie (par slug). */
    public function postsByCategory(string $slug, int $n = 9999): array
    {
        return $this->content->getPosts(['category_slug' => $slug, 'per_page' => $n]);
    }

    /** Retourne les articles ayant un tag donné (par slug). */
    public function postsByTag(string $slug, int $n = 9999): array
    {
        return $this->content->getPosts(['tag_slug' => $slug, 'per_page' => $n]);
    }

    /** Nombre total d'articles publiés. */
    public function postsCount(): int
    {
        return $this->content->getPostsCount();
    }

    // =========================================================================
    // PAGES
    // =========================================================================

    /** Retourne toutes les pages publiées. */
    public function pages(): array
    {
        return $this->content->getPages();
    }

    /** Retourne une page par slug ou ID numérique. */
    public function page(string $idOrSlug): ?array
    {
        return $this->content->getPage($idOrSlug);
    }

    // =========================================================================
    // TAXONOMIES
    // =========================================================================

    /**
     * Retourne toutes les catégories.
     * Chaque entrée contient : id, name, slug, count, link.
     */
    public function categories(): array
    {
        return $this->content->getCategories();
    }

    /**
     * Retourne tous les tags.
     * Chaque entrée contient : id, name, slug, count, link.
     */
    public function tags(): array
    {
        return $this->content->getTags();
    }

    /** Retourne une catégorie par son slug, ou null si introuvable. */
    public function category(string $slug): ?array
    {
        foreach ($this->content->getCategories() as $cat) {
            if ($cat['slug'] === $slug) return $cat;
        }
        return null;
    }

    /** Retourne un tag par son slug, ou null si introuvable. */
    public function tag(string $slug): ?array
    {
        foreach ($this->content->getTags() as $tag) {
            if ($tag['slug'] === $slug) return $tag;
        }
        return null;
    }

    // =========================================================================
    // URLs
    // =========================================================================

    /** URL absolue du site, avec chemin optionnel. */
    public function siteUrl(string $path = ''): string
    {
        return $this->config['site_url'] . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    /** URL d'un article. */
    public function postUrl(array $post): string
    {
        return $post['link'] ?? $this->siteUrl($post['slug'] . '/');
    }

    /** URL d'une page statique. */
    public function pageUrl(array $page): string
    {
        return $page['link'] ?? $this->siteUrl($page['slug'] . '/');
    }

    /** URL de l'archive d'une catégorie. */
    public function categoryUrl(string $slug): string
    {
        return $this->siteUrl('category/' . $slug . '/');
    }

    /** URL de l'archive d'un tag. */
    public function tagUrl(string $slug): string
    {
        return $this->siteUrl('tag/' . $slug . '/');
    }

    /**
     * URL de pagination.
     * pageNum(1) → '/'  |  pageNum(3) → '/page/3/'
     */
    public function pageNum(int $n): string
    {
        return $n <= 1 ? $this->siteUrl() : $this->siteUrl('page/' . $n . '/');
    }

    /** URL du flux RSS. */
    public function feedUrl(): string
    {
        return $this->siteUrl('feed/');
    }

    /** URL du sitemap XML. */
    public function sitemapUrl(): string
    {
        return $this->siteUrl('sitemap.xml');
    }

    /** URL racine du thème actif (ex: pour inclure des assets). */
    public function themeUrl(string $path = ''): string
    {
        $theme = $this->config['theme'] ?? 'default';
        $base  = $this->siteUrl('wp-content/themes/' . $theme);
        return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
    }

    // =========================================================================
    // IMAGES
    // =========================================================================

    /**
     * Retourne l'URL absolue de l'image mise en avant d'un post/page.
     * Retourne '' si aucune image n'est définie.
     */
    public function imageUrl(array $post): string
    {
        $url = $post['featured_image_url'] ?? '';
        if ($url === '') return '';
        return str_starts_with($url, 'http') ? $url : $this->config['site_url'] . $url;
    }

    /**
     * Retourne une balise <img> pour l'image mise en avant, ou '' si aucune.
     *
     * Les attributs par défaut peuvent être surchargés ou complétés :
     *   $h->featuredImage($post, ['class' => 'hero-img', 'width' => 1200, 'height' => 600])
     */
    public function featuredImage(array $post, array $attrs = []): string
    {
        $url = $this->imageUrl($post);
        if ($url === '') return '';

        $attrs = array_merge([
            'src'     => $url,
            'alt'     => $post['featured_image_alt'] ?? '',
            'loading' => 'lazy',
            'class'   => 'post-featured',
        ], $attrs);

        $html = '<img';
        foreach ($attrs as $attr => $val) {
            $html .= ' ' . $attr . '="' . htmlspecialchars((string)$val) . '"';
        }
        return $html . '>';
    }

    // =========================================================================
    // FORMATAGE
    // =========================================================================

    /**
     * Retourne le titre d'un post/page, échappé pour le HTML.
     */
    public function title(array $post): string
    {
        return htmlspecialchars($post['title'] ?? '');
    }

    /**
     * Retourne la date d'un post formatée.
     * Format par défaut : "15 janvier 2024"
     */
    public function postDate(array $post, string $format = 'j F Y'): string
    {
        $ts = strtotime($post['date'] ?? 'now');
        return $ts !== false ? date($format, $ts) : '';
    }

    /**
     * Retourne un extrait tronqué à N mots.
     * Utilise d'abord le champ 'excerpt', puis 'content' si absent.
     */
    public function excerpt(array $post, int $maxWords = 30): string
    {
        $text  = strip_tags($post['excerpt'] !== '' ? $post['excerpt'] : ($post['content'] ?? ''));
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($words) <= $maxWords) return $text;
        return implode(' ', array_slice($words, 0, $maxWords)) . '…';
    }

    /**
     * Raccourci htmlspecialchars() — utile pour les sorties inline.
     */
    public function e(string $value): string
    {
        return htmlspecialchars($value);
    }

    // =========================================================================
    // NAVIGATION
    // =========================================================================

    /**
     * Retourne le HTML d'un bloc de pagination.
     * Le paramètre $wrapClass permet de personnaliser la classe du <nav>.
     *
     * Ne retourne '' si une seule page.
     */
    public function pagination(int $current, int $total, string $wrapClass = 'pagination'): string
    {
        if ($total <= 1) return '';

        $html  = '<nav class="' . htmlspecialchars($wrapClass) . '" aria-label="Pagination">' . "\n";

        if ($current > 1) {
            $html .= '  <a href="' . $this->pageNum($current - 1) . '" rel="prev">← Précédent</a>' . "\n";
        }

        for ($i = 1; $i <= $total; $i++) {
            if ($i === $current) {
                $html .= '  <span class="current" aria-current="page">' . $i . '</span>' . "\n";
            } else {
                $html .= '  <a href="' . $this->pageNum($i) . '">' . $i . '</a>' . "\n";
            }
        }

        if ($current < $total) {
            $html .= '  <a href="' . $this->pageNum($current + 1) . '" rel="next">Suivant →</a>' . "\n";
        }

        return $html . '</nav>';
    }

    // =========================================================================
    // MULTILINGUE
    // =========================================================================

    /**
     * Retourne les données pour construire un sélecteur de langue.
     *
     * Usage :
     *   foreach ($h->languageSwitcher($post) as $lang) {
     *       // $lang['code']    → 'en'
     *       // $lang['name']    → 'English'
     *       // $lang['url']     → URL de la version traduite (ou homepage)
     *       // $lang['current'] → false (les autres instances)
     *   }
     */
    public function languageSwitcher(array $currentItem = []): array
    {
        $languages    = $this->config['languages'] ?? [];
        $translations = $currentItem['translations'] ?? [];
        $result       = [];

        foreach ($languages as $langCode => $langConfig) {
            $base = rtrim($langConfig['url'], '/');
            $url  = isset($translations[$langCode])
                ? $base . '/' . $translations[$langCode] . '/'
                : $base . '/';
            $result[] = [
                'code'    => $langCode,
                'name'    => $langConfig['name'],
                'url'     => $url,
                'current' => false,
            ];
        }
        return $result;
    }

    /**
     * Retourne la liste complète des langues configurées (y compris la langue courante).
     * La langue courante a 'current' => true et est toujours en première position.
     */
    public function languages(array $currentItem = []): array
    {
        $current = [
            'code'    => substr($this->config['language'] ?? 'fr-FR', 0, 2),
            'name'    => $this->config['site_name'] ?? '',
            'url'     => $this->config['site_url'] . '/',
            'current' => true,
        ];
        return array_merge([$current], $this->languageSwitcher($currentItem));
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /** Retourne une valeur brute de config.php. */
    public function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /** Nom du site, échappé. */
    public function siteName(): string
    {
        return htmlspecialchars($this->config['site_name'] ?? '');
    }

    /** Description du site, échappée. */
    public function siteDescription(): string
    {
        return htmlspecialchars($this->config['site_description'] ?? '');
    }

    /** Auteur par défaut, échappé. */
    public function author(): string
    {
        return htmlspecialchars($this->config['author'] ?? '');
    }
}
