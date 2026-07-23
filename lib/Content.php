<?php

require_once __DIR__ . '/Markdown.php';
require_once __DIR__ . '/HtmlToMarkdown.php';

class Content implements ContentInterface
{
    private array $config;
    private array $postsCache = [];
    private array $pagesCache = [];
    private array $mediaCache = [];

    // Manifest persistant : slug → id pour chaque type
    private array  $manifest = [];
    private bool   $manifestDirty = false;
    private string $manifestPath;

    // Valeurs de départ pour chaque espace d'IDs
    private const ID_START = [
        'posts'      => 1,
        'pages'      => 1000,
        'categories' => 2000,
        'tags'       => 3000,
        'media'      => 5000,
    ];

    // Clés frontmatter "connues" — tout le reste est collecté dans meta
    private const KNOWN_POST_KEYS = [
        'title', 'slug', 'date', 'status', 'author', 'excerpt',
        'seo_description', 'categories', 'tags', 'featured_image', 'featured_image_alt', 'translations',
    ];
    private const KNOWN_PAGE_KEYS = [
        'title', 'slug', 'date', 'status', 'author', 'excerpt',
        'seo_description', 'featured_image', 'featured_image_alt', 'menu_order', 'parent',
    ];

    public function __construct(array $config)
    {
        $this->config       = $config;
        $this->manifestPath = BASE_PATH . '/content/ids.json';
        $this->loadManifest();
    }

    // -------------------------------------------------------------------------
    // MANIFEST  (persistance des IDs par slug)
    // -------------------------------------------------------------------------

    private function loadManifest(): void
    {
        if (file_exists($this->manifestPath)) {
            $decoded = json_decode(file_get_contents($this->manifestPath), true);
            if (is_array($decoded)) {
                $this->manifest = $decoded;
                return;
            }
        }
        // Structure vide initiale
        $this->manifest = ['posts' => [], 'pages' => [], 'categories' => [], 'tags' => [], 'media' => []];
    }

    private function saveManifest(): void
    {
        if (!$this->manifestDirty) return;
        file_put_contents(
            $this->manifestPath,
            json_encode($this->manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
        $this->manifestDirty = false;
    }

    /**
     * Retourne l'ID stable pour un slug dans un espace donné.
     * Si le slug est inconnu, attribue le prochain ID disponible et marque le manifest comme modifié.
     */
    private function resolveId(string $type, string $slug): int
    {
        if (isset($this->manifest[$type][$slug])) {
            return (int)$this->manifest[$type][$slug];
        }

        // Prochain ID = max des IDs existants pour ce type + 1, ou valeur de départ
        $existing = array_values($this->manifest[$type] ?? []);
        $next = empty($existing)
            ? self::ID_START[$type]
            : max($existing) + 1;

        $this->manifest[$type][$slug] = $next;
        $this->manifestDirty = true;
        $this->saveManifest();

        return $next;
    }

    // -------------------------------------------------------------------------
    // POSTS
    // -------------------------------------------------------------------------

    public function getPosts(array $args = []): array
    {
        $posts = $this->loadPosts();

        $status = $args['status'] ?? 'publish';
        if ($status !== 'any') {
            $posts = array_filter($posts, fn($p) => $p['status'] === $status);
        }
        if ($status === 'publish') {
            $now   = date('Y-m-d\TH:i:s');
            $posts = array_filter($posts, fn($p) => ($p['date'] ?? '') <= $now);
        }

        if (!empty($args['category_slug'])) {
            $slug = $args['category_slug'];
            $posts = array_filter($posts, function ($p) use ($slug) {
                $cats = array_map([Markdown::class, 'slugify'], $p['categories']);
                return in_array($slug, $cats);
            });
        }

        if (!empty($args['tag_slug'])) {
            $slug = $args['tag_slug'];
            $posts = array_filter($posts, function ($p) use ($slug) {
                $tags = array_map([Markdown::class, 'slugify'], $p['tags']);
                return in_array($slug, $tags);
            });
        }

        if (!empty($args['location_page_id'])) {
            $locId = (int)$args['location_page_id'];
            $posts = array_filter($posts, fn($p) =>
                (int)($p['meta']['location_page_id'] ?? 0) === $locId
            );
        }

        usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));

        $per_page = (int)($args['per_page'] ?? $this->config['posts_per_page']);
        $page     = max(1, (int)($args['page'] ?? 1));
        $offset   = ($page - 1) * $per_page;

        return array_values(array_slice($posts, $offset, $per_page));
    }

    public function getPostsCount(array $args = []): int
    {
        $posts = $this->loadPosts();
        $status = $args['status'] ?? 'publish';
        if ($status !== 'any') {
            $posts = array_filter($posts, fn($p) => $p['status'] === $status);
        }
        if ($status === 'publish') {
            $now   = date('Y-m-d\TH:i:s');
            $posts = array_filter($posts, fn($p) => ($p['date'] ?? '') <= $now);
        }
        return count($posts);
    }

    public function getPost(string $idOrSlug): ?array
    {
        foreach ($this->loadPosts() as $post) {
            if ((string)$post['id'] === (string)$idOrSlug || $post['slug'] === $idOrSlug) {
                return $post;
            }
        }
        return null;
    }

    private function loadPosts(): array
    {
        if (!empty($this->postsCache)) return $this->postsCache;

        $dir = BASE_PATH . '/content/posts';
        if (!is_dir($dir)) return [];

        // Construire l'index page_id → full_path
        $pagesIndex = [];
        foreach ($this->loadPages() as $p) {
            $path = rtrim(str_replace($this->config['site_url'] . '/', '', $p['link']), '/');
            $pagesIndex[(int)$p['id']] = $path;
        }

        $files = glob($dir . '/*.md') ?: [];
        $posts = [];

        foreach ($files as $file) {
            [$meta, $html] = Markdown::parseFile($file);
            $slug = $meta['slug'] ?? pathinfo($file, PATHINFO_FILENAME);
            $id   = $this->resolveId('posts', $slug);

            $featuredMedia   = 0;
            $featuredImageUrl = '';
            if (!empty($meta['featured_image'])) {
                $featuredMedia   = $this->resolveId('media', pathinfo($meta['featured_image'], PATHINFO_FILENAME));
                $featuredImageUrl = $meta['featured_image'];
            }

            $date          = $meta['date'] ?? date('Y-m-d', filemtime($file));
            $dateFormatted = date('Y-m-d\TH:i:s', strtotime($date));

            $knownMeta     = array_diff_key($meta, array_flip(self::KNOWN_POST_KEYS));
            $locationPageId = (int)($knownMeta['location_page_id'] ?? 0);
            $link = $locationPageId > 0 && isset($pagesIndex[$locationPageId])
                ? $this->config['site_url'] . '/' . $pagesIndex[$locationPageId] . '/' . $slug . '/'
                : $this->config['site_url'] . '/' . $slug . '/';

            $posts[] = [
                'id'                 => $id,
                'slug'               => $slug,
                'status'             => $meta['status'] ?? 'publish',
                'type'               => 'post',
                'date'               => $dateFormatted,
                'modified'           => $dateFormatted,
                'title'              => $meta['title'] ?? $slug,
                'content'            => $html,
                'excerpt'            => $meta['excerpt'] ?? Markdown::excerpt($html),
                'author'             => $meta['author'] ?? $this->config['author'],
                'categories'         => isset($meta['categories']) ? (array)$meta['categories'] : [],
                'tags'               => isset($meta['tags']) ? (array)$meta['tags'] : [],
                'featured_media'     => $featuredMedia,
                'featured_image_url' => $featuredImageUrl,
                'featured_image_alt' => $meta['featured_image_alt'] ?? ($meta['title'] ?? $slug),
                'seo_description'    => $meta['seo_description'] ?? Markdown::excerpt($html, 25),
                'link'               => $link,
                'translations'       => isset($meta['translations']) && is_array($meta['translations']) ? $meta['translations'] : [],
                'meta'               => $knownMeta,
            ];
        }

        $this->postsCache = $posts;
        return $posts;
    }

    // -------------------------------------------------------------------------
    // PAGES
    // -------------------------------------------------------------------------

    public function getPages(array $args = []): array
    {
        $pages = $this->loadPages();
        $status = $args['status'] ?? 'publish';
        if ($status !== 'any') {
            $pages = array_filter($pages, fn($p) => $p['status'] === $status);
        }
        usort($pages, fn($a, $b) => ($a['menu_order'] ?? 0) - ($b['menu_order'] ?? 0));
        return array_values($pages);
    }

    public function getPage(string $idOrSlug): ?array
    {
        foreach ($this->loadPages() as $page) {
            if ((string)$page['id'] === (string)$idOrSlug || $page['slug'] === $idOrSlug) {
                return $page;
            }
        }
        return null;
    }

    public function getPageByPath(string $path): ?array
    {
        $segments = array_values(array_filter(explode('/', $path)));
        if (empty($segments)) return null;

        $parentId = 0;
        $found    = null;
        foreach ($segments as $seg) {
            $found = null;
            foreach ($this->loadPages() as $p) {
                if ($p['slug'] === $seg && (int)$p['parent_id'] === $parentId) {
                    $found    = $p;
                    $parentId = $p['id'];
                    break;
                }
            }
            if (!$found) return null;
        }
        return $found;
    }

    public function getChildPages(int $parentId, array $args = []): array
    {
        $pages = array_filter(
            $this->loadPages(),
            fn($p) => (int)$p['parent_id'] === $parentId
        );
        $status = $args['status'] ?? 'any';
        if ($status !== 'any') {
            $pages = array_filter($pages, fn($p) => $p['status'] === $status);
        }
        usort($pages, fn($a, $b) => ($a['menu_order'] ?? 0) - ($b['menu_order'] ?? 0));
        return array_values($pages);
    }

    private function loadPages(): array
    {
        if (!empty($this->pagesCache)) return $this->pagesCache;

        $dir = BASE_PATH . '/content/pages';
        if (!is_dir($dir)) return [];

        $files = glob($dir . '/*.md') ?: [];
        $byId  = [];

        // Passe 1 : charger toutes les pages sans calculer les links
        foreach ($files as $file) {
            [$meta, $html] = Markdown::parseFile($file);
            $slug = $meta['slug'] ?? pathinfo($file, PATHINFO_FILENAME);
            $id   = $this->resolveId('pages', $slug);

            $date          = $meta['date'] ?? date('Y-m-d', filemtime($file));
            $dateFormatted = date('Y-m-d\TH:i:s', strtotime($date));

            $featuredMedia   = 0;
            $featuredImageUrl = '';
            if (!empty($meta['featured_image'])) {
                $featuredMedia   = $this->resolveId('media', pathinfo($meta['featured_image'], PATHINFO_FILENAME));
                $featuredImageUrl = $meta['featured_image'];
            }

            $byId[$id] = [
                'id'                 => $id,
                'slug'               => $slug,
                'parent_id'          => (int)($meta['parent'] ?? 0),
                'status'             => $meta['status'] ?? 'publish',
                'type'               => 'page',
                'date'               => $dateFormatted,
                'modified'           => $dateFormatted,
                'title'              => $meta['title'] ?? $slug,
                'content'            => $html,
                'excerpt'            => $meta['excerpt'] ?? Markdown::excerpt($html),
                'author'             => $meta['author'] ?? $this->config['author'],
                'featured_media'     => $featuredMedia,
                'featured_image_url' => $featuredImageUrl,
                'featured_image_alt' => $meta['featured_image_alt'] ?? ($meta['title'] ?? $slug),
                'seo_description'    => $meta['seo_description'] ?? Markdown::excerpt($html, 25),
                'parent'             => (int)($meta['parent'] ?? 0),
                'menu_order'         => (int)($meta['menu_order'] ?? 0),
                'link'               => null,
                'translations'       => isset($meta['translations']) && is_array($meta['translations']) ? $meta['translations'] : [],
                'meta'               => array_diff_key($meta, array_flip(self::KNOWN_PAGE_KEYS)),
            ];
        }

        // Passe 2 : calculer les links via l'arbre parent
        foreach ($byId as $id => $page) {
            $path = $this->buildPagePath($id, $byId);
            $byId[$id]['link'] = $this->config['site_url'] . '/' . $path . '/';
        }

        $this->pagesCache = array_values($byId);
        return $this->pagesCache;
    }

    private function buildPagePath(int $pageId, array &$byId, array $visited = []): string
    {
        if (!isset($byId[$pageId]) || in_array($pageId, $visited) || count($visited) > 10) {
            return $byId[$pageId]['slug'] ?? '';
        }
        $visited[] = $pageId;
        $page = $byId[$pageId];
        if ((int)$page['parent_id'] === 0) return $page['slug'];
        $parent = $this->buildPagePath((int)$page['parent_id'], $byId, $visited);
        return $parent !== '' ? $parent . '/' . $page['slug'] : $page['slug'];
    }

    // -------------------------------------------------------------------------
    // MEDIA
    // -------------------------------------------------------------------------

    public function getMedia(): array
    {
        if (!empty($this->mediaCache)) return $this->mediaCache;

        $dir = BASE_PATH . '/media';
        if (!is_dir($dir)) return [];

        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $media = [];

        foreach ($extensions as $ext) {
            foreach (glob($dir . '/*.' . $ext) ?: [] as $file) {
                $filename = basename($file);
                $slug     = pathinfo($filename, PATHINFO_FILENAME);
                $id       = $this->resolveId('media', $slug);
                $size = @getimagesize($file);
                $mime = function_exists('mime_content_type') ? mime_content_type($file) : 'image/' . $ext;
                $media[] = [
                    'id'          => $id,
                    'slug'        => pathinfo($filename, PATHINFO_FILENAME),
                    'date'        => date('Y-m-d\TH:i:s', filemtime($file)),
                    'title'       => pathinfo($filename, PATHINFO_FILENAME),
                    'status'      => 'inherit',
                    'type'        => 'attachment',
                    'mime_type'   => $mime,
                    'source_url'  => $this->config['site_url'] . '/media/' . $filename,
                    'media_type'  => 'image',
                    'width'       => $size ? $size[0] : 0,
                    'height'      => $size ? $size[1] : 0,
                    'file'        => $filename,
                    'link'        => $this->config['site_url'] . '/media/' . $filename,
                    'alt_text'    => pathinfo($filename, PATHINFO_FILENAME),
                ];
            }
        }

        $this->mediaCache = $media;
        return $media;
    }

    public function getMediaItem(string $idOrSlug): ?array
    {
        foreach ($this->getMedia() as $item) {
            if ((string)$item['id'] === (string)$idOrSlug || $item['slug'] === $idOrSlug) {
                return $item;
            }
        }
        return null;
    }

    // -------------------------------------------------------------------------
    // TAXONOMY
    // -------------------------------------------------------------------------

    public function getCategories(): array
    {
        $cats = [];
        foreach ($this->loadPosts() as $post) {
            foreach ($post['categories'] as $cat) {
                $slug = Markdown::slugify($cat);
                if (!isset($cats[$slug])) {
                    $cats[$slug] = [
                        'id'          => $this->resolveId('categories', $slug),
                        'name'        => $cat,
                        'slug'        => $slug,
                        'description' => '',
                        'count'       => 1,
                        'link'        => $this->config['site_url'] . '/category/' . $slug . '/',
                    ];
                } else {
                    $cats[$slug]['count']++;
                }
            }
        }
        return array_values($cats);
    }

    public function getTags(): array
    {
        $tags = [];
        foreach ($this->loadPosts() as $post) {
            foreach ($post['tags'] as $tag) {
                $slug = Markdown::slugify($tag);
                if (!isset($tags[$slug])) {
                    $tags[$slug] = [
                        'id'          => $this->resolveId('tags', $slug),
                        'name'        => $tag,
                        'slug'        => $slug,
                        'description' => '',
                        'count'       => 1,
                        'link'        => $this->config['site_url'] . '/tag/' . $slug . '/',
                    ];
                } else {
                    $tags[$slug]['count']++;
                }
            }
        }
        return array_values($tags);
    }

    // -------------------------------------------------------------------------
    // WRITE OPERATIONS
    // -------------------------------------------------------------------------

    public function clearCache(): void
    {
        $this->postsCache = [];
        $this->pagesCache = [];
        $this->mediaCache = [];
    }

    // ── Posts ────────────────────────────────────────────────────────────────

    public function createPost(array $data): ?array
    {
        $title   = $data['title'] ?? 'Sans titre';
        $slug    = isset($data['slug']) && $data['slug'] !== ''
            ? $this->sanitizePathSlug($data['slug'])
            : $this->generateUniqueSlug($title, 'posts');

        $htmlContent = $data['content'] ?? '';
        $markdown    = $htmlContent !== '' ? HtmlToMarkdown::convert($htmlContent) : '';

        $meta = $this->buildPostMeta($data, $slug, $title);

        $this->writeMd('posts', $slug, $meta, $markdown);
        $this->resolveId('posts', $slug);
        $this->clearCache();

        return $this->getPost($slug);
    }

    public function updatePost(string $idOrSlug, array $data): ?array
    {
        $existing = $this->getPost($idOrSlug);
        if (!$existing) return null;

        $slug = $existing['slug'];
        $file = BASE_PATH . '/content/posts/' . $this->slugToFilename($slug) . '.md';

        [$oldMeta] = Markdown::parseFile($file);

        // Merge : on ne remplace que les champs fournis
        if (isset($data['title']))   $oldMeta['title']           = $data['title'];
        if (isset($data['excerpt'])) $oldMeta['excerpt']         = $data['excerpt'];
        if (isset($data['status']))  $oldMeta['status']          = $data['status'];
        if (isset($data['date']))    $oldMeta['date']            = substr($data['date'], 0, 10);
        if (isset($data['author']))  $oldMeta['author']          = $data['author'];
        if (isset($data['seo_description'])) $oldMeta['seo_description'] = $data['seo_description'];

        if (isset($data['categories'])) {
            $oldMeta['categories'] = $this->resolveTermNames($data['categories'], 'categories');
        }
        if (isset($data['tags'])) {
            $oldMeta['tags'] = $this->resolveTermNames($data['tags'], 'tags');
        }
        if (isset($data['featured_media'])) {
            $path = $this->mediaPathById((int)$data['featured_media']);
            $oldMeta['featured_image']     = $path ?? '';
            $oldMeta['featured_image_alt'] = $data['featured_image_alt'] ?? ($oldMeta['featured_image_alt'] ?? '');
        }

        if (isset($data['meta'])) {
            // Supprimer les anciennes clés meta du frontmatter
            foreach (array_keys($oldMeta) as $k) {
                if (!in_array($k, self::KNOWN_POST_KEYS)) {
                    unset($oldMeta[$k]);
                }
            }
            // Écrire les nouvelles
            foreach ($data['meta'] as $k => $v) {
                if (!in_array($k, self::KNOWN_POST_KEYS)) {
                    $oldMeta[$k] = $v;
                }
            }
        }

        $htmlContent = $data['content'] ?? null;
        if ($htmlContent !== null) {
            $markdown = $htmlContent !== '' ? HtmlToMarkdown::convert($htmlContent) : '';
        } else {
            // Réutilise le corps Markdown existant
            [, $existingHtml] = Markdown::parseFile($file);
            $markdown = ''; // on réécrit depuis le fichier
            // Lire le raw body (après le frontmatter)
            $raw = file_get_contents($file);
            if (preg_match('/^---\s*\n.*?\n---\s*\n(.*)/s', ltrim($raw), $m)) {
                $markdown = $m[1];
            }
        }

        $this->writeMd('posts', $slug, $oldMeta, $markdown, true);
        $this->clearCache();

        return $this->getPost($slug);
    }

    public function deletePost(string $idOrSlug, bool $force = false): ?array
    {
        $post = $this->getPost($idOrSlug);
        if (!$post) return null;

        $slug = $post['slug'];

        if ($force) {
            unlink(BASE_PATH . '/content/posts/' . $this->slugToFilename($slug) . '.md');
            // Retire du manifest
            unset($this->manifest['posts'][$slug]);
            $this->manifestDirty = true;
            $this->saveManifest();
        } else {
            // Soft delete : status → trash
            $this->updatePost($slug, ['status' => 'trash']);
            $post['status'] = 'trash';
        }

        $this->clearCache();
        return $post;
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public function createPage(array $data): ?array
    {
        $title = $data['title'] ?? 'Sans titre';
        $slug  = isset($data['slug']) && $data['slug'] !== ''
            ? $this->sanitizePathSlug($data['slug'])
            : $this->generateUniqueSlug($title, 'pages');

        $htmlContent = $data['content'] ?? '';
        $markdown    = $htmlContent !== '' ? HtmlToMarkdown::convert($htmlContent) : '';

        $meta = [
            'title'      => $title,
            'slug'       => $slug,
            'date'       => isset($data['date']) ? substr($data['date'], 0, 10) : date('Y-m-d'),
            'author'     => $data['author'] ?? $this->config['author'],
            'status'     => $data['status'] ?? 'draft',
            'menu_order' => (int)($data['menu_order'] ?? 0),
        ];

        if (!empty($data['excerpt']))         $meta['excerpt']         = $data['excerpt'];
        if (!empty($data['seo_description'])) $meta['seo_description'] = $data['seo_description'];

        if (!empty($data['featured_media'])) {
            $path = $this->mediaPathById((int)$data['featured_media']);
            if ($path) {
                $meta['featured_image']     = $path;
                $meta['featured_image_alt'] = $data['featured_image_alt'] ?? $title;
            }
        }

        if (!empty($data['meta'])) {
            foreach ($data['meta'] as $k => $v) {
                if (!in_array($k, self::KNOWN_PAGE_KEYS)) {
                    $meta[$k] = $v;
                }
            }
        }

        $this->writeMd('pages', $slug, $meta, $markdown);
        $this->resolveId('pages', $slug);
        $this->clearCache();

        return $this->getPage($slug);
    }

    public function updatePage(string $idOrSlug, array $data): ?array
    {
        $existing = $this->getPage($idOrSlug);
        if (!$existing) return null;

        $slug = $existing['slug'];
        $file = BASE_PATH . '/content/pages/' . $this->slugToFilename($slug) . '.md';
        [$oldMeta] = Markdown::parseFile($file);

        if (isset($data['title']))       $oldMeta['title']           = $data['title'];
        if (isset($data['excerpt']))     $oldMeta['excerpt']         = $data['excerpt'];
        if (isset($data['status']))      $oldMeta['status']          = $data['status'];
        if (isset($data['date']))        $oldMeta['date']            = substr($data['date'], 0, 10);
        if (isset($data['author']))      $oldMeta['author']          = $data['author'];
        if (isset($data['menu_order']))  $oldMeta['menu_order']      = (int)$data['menu_order'];
        if (isset($data['seo_description'])) $oldMeta['seo_description'] = $data['seo_description'];

        if (isset($data['featured_media'])) {
            $path = $this->mediaPathById((int)$data['featured_media']);
            $oldMeta['featured_image']     = $path ?? '';
            $oldMeta['featured_image_alt'] = $data['featured_image_alt'] ?? ($oldMeta['featured_image_alt'] ?? '');
        }

        if (isset($data['meta'])) {
            // Supprimer les anciennes clés meta du frontmatter
            foreach (array_keys($oldMeta) as $k) {
                if (!in_array($k, self::KNOWN_PAGE_KEYS)) {
                    unset($oldMeta[$k]);
                }
            }
            // Écrire les nouvelles
            foreach ($data['meta'] as $k => $v) {
                if (!in_array($k, self::KNOWN_PAGE_KEYS)) {
                    $oldMeta[$k] = $v;
                }
            }
        }

        $htmlContent = $data['content'] ?? null;
        if ($htmlContent !== null) {
            $markdown = $htmlContent !== '' ? HtmlToMarkdown::convert($htmlContent) : '';
        } else {
            $raw = file_get_contents($file);
            $markdown = '';
            if (preg_match('/^---\s*\n.*?\n---\s*\n(.*)/s', ltrim($raw), $m)) {
                $markdown = $m[1];
            }
        }

        $this->writeMd('pages', $slug, $oldMeta, $markdown, true);
        $this->clearCache();

        return $this->getPage($slug);
    }

    public function deletePage(string $idOrSlug, bool $force = false): ?array
    {
        $page = $this->getPage($idOrSlug);
        if (!$page) return null;

        $slug = $page['slug'];

        if ($force) {
            unlink(BASE_PATH . '/content/pages/' . $this->slugToFilename($slug) . '.md');
            unset($this->manifest['pages'][$slug]);
            $this->manifestDirty = true;
            $this->saveManifest();
        } else {
            $this->updatePage($slug, ['status' => 'trash']);
            $page['status'] = 'trash';
        }

        $this->clearCache();
        return $page;
    }

    // -------------------------------------------------------------------------
    // HELPERS D'ÉCRITURE
    // -------------------------------------------------------------------------

    /**
     * Écrit un fichier .md (frontmatter + body Markdown)
     */
    private function writeMd(string $type, string $slug, array $meta, string $body, bool $rawBody = false): void
    {
        $dir  = BASE_PATH . '/content/' . $type . '/';
        $file = $dir . $this->slugToFilename($slug) . '.md';

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException("Impossible de créer le répertoire : {$dir}");
        }

        $fm      = $this->serializeFrontmatter($meta);
        $content = $fm . ($rawBody ? ltrim($body) : $body);

        if (file_put_contents($file, $content) === false) {
            throw new \RuntimeException("Impossible d'écrire le fichier : {$file}");
        }
    }

    /**
     * Sérialise un tableau en frontmatter YAML
     */
    private function serializeFrontmatter(array $meta): string
    {
        $lines = ['---'];

        foreach ($meta as $key => $value) {
            if ($value === null || $value === '') continue;

            if (is_array($value)) {
                if (empty($value)) continue;
                $lines[] = $key . ':';
                foreach ($value as $item) {
                    $lines[] = '  - ' . $this->yamlScalar((string)$item);
                }
            } elseif (is_bool($value)) {
                $lines[] = $key . ': ' . ($value ? 'true' : 'false');
            } elseif (is_int($value) || is_float($value)) {
                $lines[] = $key . ': ' . $value;
            } else {
                $lines[] = $key . ': ' . $this->yamlScalar((string)$value);
            }
        }

        $lines[] = '---';
        $lines[] = '';
        return implode("\n", $lines) . "\n";
    }

    /**
     * Quote une valeur scalaire YAML si nécessaire
     */
    private function yamlScalar(string $value): string
    {
        // Besoin de guillemets si la valeur contient des caractères spéciaux YAML
        if (preg_match('/[:#\[\]{}&*!|>\'"%@`,]|^[-?]/', $value)
            || in_array(strtolower($value), ['true','false','null','yes','no','on','off'])
            || str_contains($value, "\n")) {
            return '"' . str_replace(['"', '\\'], ['\\"', '\\\\'], $value) . '"';
        }
        return $value;
    }

    /**
     * Génère un slug unique en évitant les collisions de fichiers
     */
    private function generateUniqueSlug(string $title, string $type): string
    {
        $base = Markdown::slugify($title) ?: 'sans-titre';
        $dir  = BASE_PATH . '/content/' . $type . '/';
        $slug = $base;
        $i    = 2;

        while (file_exists($dir . $slug . '.md')) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Résout une liste de noms ou d'IDs de termes (categories/tags) en noms
     */
    private function resolveTermNames(array $terms, string $type): array
    {
        if (empty($terms)) return [];

        $known = $type === 'categories' ? $this->getCategories() : $this->getTags();
        $byId  = array_column($known, 'name', 'id');

        $names = [];
        foreach ($terms as $term) {
            if (is_int($term) || ctype_digit((string)$term)) {
                // ID → nom
                $id = (int)$term;
                if (isset($byId[$id])) {
                    $names[] = $byId[$id];
                }
            } else {
                // Nom direct
                $names[] = (string)$term;
            }
        }

        return $names;
    }

    /**
     * Résout un ID de média en chemin relatif (/media/fichier.jpg)
     */
    private function mediaPathById(int $id): ?string
    {
        foreach ($this->getMedia() as $item) {
            if ($item['id'] === $id) {
                // Retourne le chemin relatif depuis la racine du site
                $url = $item['source_url'];
                $siteUrl = rtrim($this->config['site_url'], '/');
                if (str_starts_with($url, $siteUrl)) {
                    return substr($url, strlen($siteUrl));
                }
                return $url;
            }
        }
        return null;
    }

    /**
     * Construit le tableau de métadonnées frontmatter pour un post
     */
    private function buildPostMeta(array $data, string $slug, string $title): array
    {
        $meta = [
            'title'  => $title,
            'slug'   => $slug,
            'date'   => isset($data['date']) ? substr($data['date'], 0, 10) : date('Y-m-d'),
            'author' => $data['author'] ?? $this->config['author'],
            'status' => $data['status'] ?? 'draft',
        ];

        if (!empty($data['excerpt']))         $meta['excerpt']         = $data['excerpt'];
        if (!empty($data['seo_description'])) $meta['seo_description'] = $data['seo_description'];

        if (!empty($data['categories'])) {
            $meta['categories'] = $this->resolveTermNames($data['categories'], 'categories');
        }
        if (!empty($data['tags'])) {
            $meta['tags'] = $this->resolveTermNames($data['tags'], 'tags');
        }
        if (!empty($data['featured_media'])) {
            $path = $this->mediaPathById((int)$data['featured_media']);
            if ($path) {
                $meta['featured_image']     = $path;
                $meta['featured_image_alt'] = $data['featured_image_alt'] ?? $title;
            }
        }

        if (!empty($data['meta'])) {
            foreach ($data['meta'] as $k => $v) {
                if (!in_array($k, self::KNOWN_POST_KEYS)) {
                    $meta[$k] = $v;
                }
            }
        }

        return $meta;
    }

    /**
     * Convertit un slug (éventuellement multi-segments) en nom de fichier sûr.
     * Exemple : 'services/cours' → 'services-cours'
     */
    private function slugToFilename(string $slug): string
    {
        return str_replace('/', '-', $slug);
    }

    /**
     * Sanitise un slug fourni par l'utilisateur en préservant la structure /chemin/.
     * Chaque segment est passé par Markdown::slugify().
     * Exemple : 'Services/Cours d\'Équitation' → 'services/cours-dequitation'
     */
    private function sanitizePathSlug(string $slug): string
    {
        $segments = array_values(array_filter(explode('/', $slug)));
        return implode('/', array_map([Markdown::class, 'slugify'], $segments));
    }
}
