<?php

require_once __DIR__ . '/Markdown.php';

/**
 * Backend SQLite pour le contenu.
 *
 * Schéma des IDs (même espaces que Content/ids.json) :
 *   posts      → 1+
 *   pages      → 1000+
 *   categories → 2000+
 *   tags       → 3000+
 *   media      → 5000+
 */
class ContentSQLite implements ContentInterface
{
    private PDO    $pdo;
    private array  $config;
    private ?array $mediaCache      = null;
    private ?array $pagesPathCache  = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $dbPath = $config['sqlite_path'] ?? BASE_PATH . '/content/db.sqlite';

        $this->pdo = new PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA journal_mode = WAL');
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        $this->initSchema();
    }

    // -------------------------------------------------------------------------
    // SCHÉMA
    // -------------------------------------------------------------------------

    private function initSchema(): void
    {
        $stmts = [
            'CREATE TABLE IF NOT EXISTS id_sequences (
                type    TEXT PRIMARY KEY,
                next_id INTEGER NOT NULL
            )',
            "INSERT OR IGNORE INTO id_sequences VALUES
                ('posts',      1),
                ('pages',      1000),
                ('categories', 2000),
                ('tags',       3000),
                ('media',      5000)",
            'CREATE TABLE IF NOT EXISTS posts (
                id                 INTEGER PRIMARY KEY,
                slug               TEXT    UNIQUE NOT NULL,
                title              TEXT    NOT NULL DEFAULT \'\',
                content_html       TEXT    NOT NULL DEFAULT \'\',
                excerpt            TEXT    NOT NULL DEFAULT \'\',
                date               TEXT    NOT NULL DEFAULT \'\',
                modified           TEXT    NOT NULL DEFAULT \'\',
                status             TEXT    NOT NULL DEFAULT \'draft\',
                author             TEXT    NOT NULL DEFAULT \'\',
                featured_image     TEXT    NOT NULL DEFAULT \'\',
                featured_image_alt TEXT    NOT NULL DEFAULT \'\',
                seo_description    TEXT    NOT NULL DEFAULT \'\',
                translations       TEXT    NOT NULL DEFAULT \'[]\'
            )',
            'CREATE TABLE IF NOT EXISTS pages (
                id                 INTEGER PRIMARY KEY,
                slug               TEXT    UNIQUE NOT NULL,
                title              TEXT    NOT NULL DEFAULT \'\',
                content_html       TEXT    NOT NULL DEFAULT \'\',
                excerpt            TEXT    NOT NULL DEFAULT \'\',
                date               TEXT    NOT NULL DEFAULT \'\',
                modified           TEXT    NOT NULL DEFAULT \'\',
                status             TEXT    NOT NULL DEFAULT \'draft\',
                author             TEXT    NOT NULL DEFAULT \'\',
                featured_image     TEXT    NOT NULL DEFAULT \'\',
                featured_image_alt TEXT    NOT NULL DEFAULT \'\',
                seo_description    TEXT    NOT NULL DEFAULT \'\',
                menu_order         INTEGER NOT NULL DEFAULT 0,
                translations       TEXT    NOT NULL DEFAULT \'[]\'
            )',
            'CREATE TABLE IF NOT EXISTS categories (
                id          INTEGER PRIMARY KEY,
                slug        TEXT    UNIQUE NOT NULL,
                name        TEXT    NOT NULL,
                description TEXT    NOT NULL DEFAULT \'\'
            )',
            'CREATE TABLE IF NOT EXISTS tags (
                id          INTEGER PRIMARY KEY,
                slug        TEXT    UNIQUE NOT NULL,
                name        TEXT    NOT NULL,
                description TEXT    NOT NULL DEFAULT \'\'
            )',
            'CREATE TABLE IF NOT EXISTS post_categories (
                post_id     INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
                category_id INTEGER NOT NULL REFERENCES categories(id),
                PRIMARY KEY (post_id, category_id)
            )',
            'CREATE TABLE IF NOT EXISTS post_tags (
                post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
                tag_id  INTEGER NOT NULL REFERENCES tags(id),
                PRIMARY KEY (post_id, tag_id)
            )',
            'CREATE TABLE IF NOT EXISTS media_slugs (
                id   INTEGER PRIMARY KEY,
                slug TEXT UNIQUE NOT NULL
            )',
        ];

        foreach ($stmts as $sql) {
            $this->pdo->exec($sql);
        }

        // Migration silencieuse : colonne meta (ignorée si elle existe déjà)
        try { $this->pdo->exec("ALTER TABLE posts ADD COLUMN meta TEXT NOT NULL DEFAULT '{}'"); }
        catch (\PDOException $e) {}
        try { $this->pdo->exec("ALTER TABLE pages ADD COLUMN meta TEXT NOT NULL DEFAULT '{}'"); }
        catch (\PDOException $e) {}

        // Migration silencieuse : ajout de parent_id + contrainte UNIQUE(slug, parent_id)
        $cols = $this->pdo->query("PRAGMA table_info(pages)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('parent_id', $cols)) {
            $this->pdo->exec("CREATE TABLE pages_new (
                id                 INTEGER PRIMARY KEY,
                slug               TEXT    NOT NULL,
                parent_id          INTEGER NOT NULL DEFAULT 0,
                title              TEXT    NOT NULL DEFAULT '',
                content_html       TEXT    NOT NULL DEFAULT '',
                excerpt            TEXT    NOT NULL DEFAULT '',
                date               TEXT    NOT NULL DEFAULT '',
                modified           TEXT    NOT NULL DEFAULT '',
                status             TEXT    NOT NULL DEFAULT 'draft',
                author             TEXT    NOT NULL DEFAULT '',
                featured_image     TEXT    NOT NULL DEFAULT '',
                featured_image_alt TEXT    NOT NULL DEFAULT '',
                seo_description    TEXT    NOT NULL DEFAULT '',
                menu_order         INTEGER NOT NULL DEFAULT 0,
                translations       TEXT    NOT NULL DEFAULT '[]',
                meta               TEXT    NOT NULL DEFAULT '{}',
                UNIQUE(slug, parent_id)
            )");
            $this->pdo->exec("INSERT INTO pages_new
                SELECT id, slug, 0, title, content_html, excerpt, date, modified,
                       status, author, featured_image, featured_image_alt,
                       seo_description, menu_order, translations, meta FROM pages");
            $this->pdo->exec("DROP TABLE pages");
            $this->pdo->exec("ALTER TABLE pages_new RENAME TO pages");
        }
    }

    // -------------------------------------------------------------------------
    // SÉQUENCES D'IDS
    // -------------------------------------------------------------------------

    private function nextId(string $type): int
    {
        $stmt = $this->pdo->prepare('SELECT next_id FROM id_sequences WHERE type = ?');
        $stmt->execute([$type]);
        $id = (int)$stmt->fetchColumn();

        $this->pdo->prepare('UPDATE id_sequences SET next_id = next_id + 1 WHERE type = ?')
                  ->execute([$type]);

        return $id;
    }

    // -------------------------------------------------------------------------
    // POSTS
    // -------------------------------------------------------------------------

    public function getPosts(array $args = []): array
    {
        $status  = $args['status']   ?? 'publish';
        $perPage = (int)($args['per_page'] ?? $this->config['posts_per_page']);
        $page    = max(1, (int)($args['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if ($status !== 'any') {
            $where[]  = 'p.status = ?';
            $params[] = $status;
        }
        if ($status === 'publish') {
            $where[] = "p.date <= datetime('now')";
        }

        if (!empty($args['category_slug'])) {
            $where[]  = 'EXISTS (
                SELECT 1 FROM post_categories pc
                JOIN categories c ON c.id = pc.category_id
                WHERE pc.post_id = p.id AND c.slug = ?)';
            $params[] = $args['category_slug'];
        }

        if (!empty($args['tag_slug'])) {
            $where[]  = 'EXISTS (
                SELECT 1 FROM post_tags pt
                JOIN tags t ON t.id = pt.tag_id
                WHERE pt.post_id = p.id AND t.slug = ?)';
            $params[] = $args['tag_slug'];
        }

        if (!empty($args['location_page_id'])) {
            $where[]  = "json_extract(meta, '$.location_page_id') = ?";
            $params[] = (int)$args['location_page_id'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $params[]    = $perPage;
        $params[]    = $offset;

        $stmt = $this->pdo->prepare(
            "SELECT * FROM posts p {$whereClause} ORDER BY date DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);

        return array_map(fn($r) => $this->hydratePost($r), $stmt->fetchAll());
    }

    public function getPostsCount(array $args = []): int
    {
        $status = $args['status'] ?? 'publish';
        $where  = [];
        $params = [];

        if ($status !== 'any') {
            $where[]  = 'status = ?';
            $params[] = $status;
        }
        if ($status === 'publish') {
            $where[] = "date <= datetime('now')";
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM posts {$whereClause}");
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getPost(string $idOrSlug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM posts WHERE id = ? OR slug = ? LIMIT 1'
        );
        $stmt->execute([$idOrSlug, $idOrSlug]);
        $row = $stmt->fetch();

        return $row ? $this->hydratePost($row) : null;
    }

    private function hydratePost(array $row): array
    {
        $id   = (int)$row['id'];
        $cats = $this->getPostTermNames($id, 'categories');
        $tags = $this->getPostTermNames($id, 'tags');

        $featuredMedia = 0;
        if ($row['featured_image'] !== '') {
            $featuredMedia = $this->mediaIdByPath($row['featured_image']);
        }

        return [
            'id'                 => $id,
            'slug'               => $row['slug'],
            'status'             => $row['status'],
            'type'               => 'post',
            'date'               => $row['date'],
            'modified'           => $row['modified'],
            'title'              => $row['title'],
            'content'            => $row['content_html'],
            'excerpt'            => $row['excerpt'],
            'author'             => $row['author'],
            'categories'         => $cats,
            'tags'               => $tags,
            'featured_media'     => $featuredMedia,
            'featured_image_url' => $row['featured_image'],
            'featured_image_alt' => $row['featured_image_alt'],
            'seo_description'    => $row['seo_description'],
            'link'               => $this->resolvePostLink($row),
            'translations'       => json_decode($row['translations'] ?? '[]', true) ?: [],
            'meta'               => json_decode($row['meta'] ?? '{}', true) ?: [],
        ];
    }

    private function resolvePostLink(array $row): string
    {
        $meta   = json_decode($row['meta'] ?? '{}', true) ?: [];
        $locId  = (int)($meta['location_page_id'] ?? 0);
        $paths  = $this->loadPagesPathCache();
        if ($locId > 0 && isset($paths[$locId])) {
            return $this->config['site_url'] . '/' . $paths[$locId] . '/' . $row['slug'] . '/';
        }
        return $this->config['site_url'] . '/' . $row['slug'] . '/';
    }

    private function getPostTermNames(int $postId, string $type): array
    {
        if ($type === 'categories') {
            $stmt = $this->pdo->prepare(
                'SELECT c.name FROM categories c
                 JOIN post_categories pc ON pc.category_id = c.id
                 WHERE pc.post_id = ?'
            );
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT t.name FROM tags t
                 JOIN post_tags pt ON pt.tag_id = t.id
                 WHERE pt.post_id = ?'
            );
        }
        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // -------------------------------------------------------------------------
    // PAGES
    // -------------------------------------------------------------------------

    public function getPages(array $args = []): array
    {
        $status = $args['status'] ?? 'publish';
        $where  = [];
        $params = [];

        if ($status !== 'any') {
            $where[]  = 'status = ?';
            $params[] = $status;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pages {$whereClause} ORDER BY menu_order ASC, date DESC"
        );
        $stmt->execute($params);

        return array_map(fn($r) => $this->hydratePage($r), $stmt->fetchAll());
    }

    public function getPage(string $idOrSlug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM pages WHERE id = ? OR slug = ? LIMIT 1'
        );
        $stmt->execute([$idOrSlug, $idOrSlug]);
        $row = $stmt->fetch();

        return $row ? $this->hydratePage($row) : null;
    }

    public function getPageByPath(string $path): ?array
    {
        $segments = array_values(array_filter(explode('/', $path)));
        if (empty($segments)) return null;

        $parentId = 0;
        $found    = null;
        foreach ($segments as $seg) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM pages WHERE slug = ? AND parent_id = ? LIMIT 1'
            );
            $stmt->execute([$seg, $parentId]);
            $row = $stmt->fetch();
            if (!$row) return null;
            $found    = $this->hydratePage($row);
            $parentId = (int)$row['id'];
        }
        return $found;
    }

    public function getChildPages(int $parentId, array $args = []): array
    {
        $where  = ['parent_id = ?'];
        $params = [$parentId];

        $status = $args['status'] ?? 'any';
        if ($status !== 'any') {
            $where[]  = 'status = ?';
            $params[] = $status;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pages {$whereClause} ORDER BY menu_order ASC, date DESC"
        );
        $stmt->execute($params);
        return array_map(fn($r) => $this->hydratePage($r), $stmt->fetchAll());
    }

    private function hydratePage(array $row): array
    {
        $featuredMedia = 0;
        if ($row['featured_image'] !== '') {
            $featuredMedia = $this->mediaIdByPath($row['featured_image']);
        }

        $path = $this->loadPagesPathCache()[(int)$row['id']] ?? $row['slug'];

        return [
            'id'                 => (int)$row['id'],
            'slug'               => $row['slug'],
            'status'             => $row['status'],
            'type'               => 'page',
            'date'               => $row['date'],
            'modified'           => $row['modified'],
            'title'              => $row['title'],
            'content'            => $row['content_html'],
            'excerpt'            => $row['excerpt'],
            'author'             => $row['author'],
            'featured_media'     => $featuredMedia,
            'featured_image_url' => $row['featured_image'],
            'featured_image_alt' => $row['featured_image_alt'],
            'seo_description'    => $row['seo_description'],
            'parent'             => (int)($row['parent_id'] ?? 0),
            'menu_order'         => (int)$row['menu_order'],
            'link'               => $this->config['site_url'] . '/' . $path . '/',
            'translations'       => json_decode($row['translations'] ?? '[]', true) ?: [],
            'meta'               => json_decode($row['meta'] ?? '{}', true) ?: [],
        ];
    }

    // -------------------------------------------------------------------------
    // MÉDIAS  (scan filesystem — identique à Content.php)
    // -------------------------------------------------------------------------

    public function getMedia(): array
    {
        if ($this->mediaCache !== null) return $this->mediaCache;

        $dir = BASE_PATH . '/media';
        if (!is_dir($dir)) return [];

        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $media      = [];

        foreach ($extensions as $ext) {
            foreach (glob($dir . '/*.' . $ext) ?: [] as $file) {
                $filename = basename($file);
                $slug     = pathinfo($filename, PATHINFO_FILENAME);
                $id       = $this->mediaIdForSlug($slug);
                $size     = @getimagesize($file);
                $mime     = function_exists('mime_content_type')
                    ? mime_content_type($file)
                    : 'image/' . $ext;

                $media[] = [
                    'id'         => $id,
                    'slug'       => $slug,
                    'date'       => date('Y-m-d\TH:i:s', filemtime($file)),
                    'title'      => $slug,
                    'status'     => 'inherit',
                    'type'       => 'attachment',
                    'mime_type'  => $mime,
                    'source_url' => $this->config['site_url'] . '/media/' . $filename,
                    'media_type' => 'image',
                    'width'      => $size ? $size[0] : 0,
                    'height'     => $size ? $size[1] : 0,
                    'file'       => $filename,
                    'link'       => $this->config['site_url'] . '/media/' . $filename,
                    'alt_text'   => $slug,
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

    private function mediaIdForSlug(string $slug): int
    {
        $stmt = $this->pdo->prepare('SELECT id FROM media_slugs WHERE slug = ?');
        $stmt->execute([$slug]);
        $existing = $stmt->fetchColumn();

        if ($existing !== false) return (int)$existing;

        $id = $this->nextId('media');
        $this->pdo->prepare('INSERT OR IGNORE INTO media_slugs (id, slug) VALUES (?, ?)')
                  ->execute([$id, $slug]);

        return $id;
    }

    private function mediaIdByPath(string $path): int
    {
        $slug = pathinfo(basename($path), PATHINFO_FILENAME);
        return $this->mediaIdForSlug($slug);
    }

    // -------------------------------------------------------------------------
    // TAXONOMIE
    // -------------------------------------------------------------------------

    public function getCategories(): array
    {
        $stmt = $this->pdo->query(
            'SELECT c.id, c.slug, c.name, c.description, COUNT(pc.post_id) AS cnt
             FROM categories c
             LEFT JOIN post_categories pc ON pc.category_id = c.id
             GROUP BY c.id
             ORDER BY c.name'
        );

        return array_map(fn($r) => [
            'id'          => (int)$r['id'],
            'name'        => $r['name'],
            'slug'        => $r['slug'],
            'description' => $r['description'],
            'count'       => (int)$r['cnt'],
            'link'        => $this->config['site_url'] . '/category/' . $r['slug'] . '/',
        ], $stmt->fetchAll());
    }

    public function getTags(): array
    {
        $stmt = $this->pdo->query(
            'SELECT t.id, t.slug, t.name, t.description, COUNT(pt.post_id) AS cnt
             FROM tags t
             LEFT JOIN post_tags pt ON pt.tag_id = t.id
             GROUP BY t.id
             ORDER BY t.name'
        );

        return array_map(fn($r) => [
            'id'          => (int)$r['id'],
            'name'        => $r['name'],
            'slug'        => $r['slug'],
            'description' => $r['description'],
            'count'       => (int)$r['cnt'],
            'link'        => $this->config['site_url'] . '/tag/' . $r['slug'] . '/',
        ], $stmt->fetchAll());
    }

    // -------------------------------------------------------------------------
    // ÉCRITURE — POSTS
    // -------------------------------------------------------------------------

    public function createPost(array $data): ?array
    {
        $title = $data['title'] ?? 'Sans titre';
        $slug  = isset($data['slug']) && $data['slug'] !== ''
            ? $this->sanitizePathSlug($data['slug'])
            : $this->generateUniqueSlug($title, 'posts');

        $now  = date('Y-m-d\TH:i:s');
        $date = isset($data['date'])
            ? date('Y-m-d\TH:i:s', strtotime($data['date']))
            : $now;

        $id      = $this->nextId('posts');
        $html    = $data['content']         ?? '';
        $excerpt = $data['excerpt']         ?? Markdown::excerpt($html);
        $seoDesc = $data['seo_description'] ?? Markdown::excerpt($html, 25);
        $author  = $data['author']          ?? $this->config['author'];
        $status  = $data['status']          ?? 'draft';

        [$featuredImage, $featuredImageAlt] = $this->resolveFeaturedImage($data, $title);

        $translations = json_encode($data['translations'] ?? []);
        $meta         = json_encode($data['meta'] ?? []);

        $this->pdo->prepare(
            'INSERT INTO posts
             (id, slug, title, content_html, excerpt, date, modified, status, author,
              featured_image, featured_image_alt, seo_description, translations, meta)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $id, $slug, $title, $html, $excerpt, $date, $now, $status, $author,
            $featuredImage, $featuredImageAlt, $seoDesc, $translations, $meta,
        ]);

        $this->syncTerms($id, $data['categories'] ?? [], $data['tags'] ?? []);

        return $this->getPost($slug);
    }

    public function updatePost(string $idOrSlug, array $data): ?array
    {
        $existing = $this->getPost($idOrSlug);
        if (!$existing) return null;

        $id     = $existing['id'];
        $now    = date('Y-m-d\TH:i:s');
        $fields = ['modified = ?'];
        $params = [$now];

        if (isset($data['title']))           { $fields[] = 'title = ?';              $params[] = $data['title']; }
        if (isset($data['content']))         { $fields[] = 'content_html = ?';       $params[] = $data['content']; }
        if (isset($data['excerpt']))         { $fields[] = 'excerpt = ?';            $params[] = $data['excerpt']; }
        if (isset($data['status']))          { $fields[] = 'status = ?';             $params[] = $data['status']; }
        if (isset($data['date']))            { $fields[] = 'date = ?';               $params[] = date('Y-m-d\TH:i:s', strtotime($data['date'])); }
        if (isset($data['author']))          { $fields[] = 'author = ?';             $params[] = $data['author']; }
        if (isset($data['seo_description'])) { $fields[] = 'seo_description = ?';   $params[] = $data['seo_description']; }
        if (isset($data['translations']))    { $fields[] = 'translations = ?';       $params[] = json_encode($data['translations']); }
        if (isset($data['meta']))            { $fields[] = 'meta = ?';               $params[] = json_encode($data['meta']); }

        if (isset($data['featured_media'])) {
            [$path, $alt] = $this->resolveFeaturedImage($data, $existing['title']);
            $fields[] = 'featured_image = ?';     $params[] = $path;
            $fields[] = 'featured_image_alt = ?'; $params[] = $alt ?: ($existing['featured_image_alt'] ?? '');
        }

        $params[] = $id;
        $this->pdo->prepare('UPDATE posts SET ' . implode(', ', $fields) . ' WHERE id = ?')
                  ->execute($params);

        if (isset($data['categories']) || isset($data['tags'])) {
            $this->syncTerms(
                $id,
                $data['categories'] ?? $existing['categories'],
                $data['tags']       ?? $existing['tags']
            );
        }

        return $this->getPost((string)$id);
    }

    public function deletePost(string $idOrSlug, bool $force = false): ?array
    {
        $post = $this->getPost($idOrSlug);
        if (!$post) return null;

        if ($force) {
            $this->pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$post['id']]);
        } else {
            $this->pdo->prepare("UPDATE posts SET status = 'trash' WHERE id = ?")
                      ->execute([$post['id']]);
            $post['status'] = 'trash';
        }

        return $post;
    }

    // -------------------------------------------------------------------------
    // ÉCRITURE — PAGES
    // -------------------------------------------------------------------------

    public function createPage(array $data): ?array
    {
        $title = $data['title'] ?? 'Sans titre';
        $slug  = isset($data['slug']) && $data['slug'] !== ''
            ? $this->sanitizePathSlug($data['slug'])
            : $this->generateUniqueSlug($title, 'pages');

        $now  = date('Y-m-d\TH:i:s');
        $date = isset($data['date'])
            ? date('Y-m-d\TH:i:s', strtotime($data['date']))
            : $now;

        $id        = $this->nextId('pages');
        $html      = $data['content']         ?? '';
        $excerpt   = $data['excerpt']         ?? Markdown::excerpt($html);
        $seoDesc   = $data['seo_description'] ?? Markdown::excerpt($html, 25);
        $author    = $data['author']          ?? $this->config['author'];
        $status    = $data['status']          ?? 'draft';
        $menuOrder = (int)($data['menu_order'] ?? 0);
        $parentId  = (int)($data['parent'] ?? 0);

        [$featuredImage, $featuredImageAlt] = $this->resolveFeaturedImage($data, $title);
        $meta = json_encode($data['meta'] ?? []);

        $this->pdo->prepare(
            'INSERT INTO pages
             (id, slug, parent_id, title, content_html, excerpt, date, modified, status, author,
              featured_image, featured_image_alt, seo_description, menu_order, meta)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $id, $slug, $parentId, $title, $html, $excerpt, $date, $now, $status, $author,
            $featuredImage, $featuredImageAlt, $seoDesc, $menuOrder, $meta,
        ]);

        $this->pagesPathCache = null;
        return $this->getPage($slug);
    }

    public function updatePage(string $idOrSlug, array $data): ?array
    {
        $existing = $this->getPage($idOrSlug);
        if (!$existing) return null;

        $id     = $existing['id'];
        $now    = date('Y-m-d\TH:i:s');
        $fields = ['modified = ?'];
        $params = [$now];

        if (isset($data['title']))           { $fields[] = 'title = ?';              $params[] = $data['title']; }
        if (isset($data['content']))         { $fields[] = 'content_html = ?';       $params[] = $data['content']; }
        if (isset($data['excerpt']))         { $fields[] = 'excerpt = ?';            $params[] = $data['excerpt']; }
        if (isset($data['status']))          { $fields[] = 'status = ?';             $params[] = $data['status']; }
        if (isset($data['date']))            { $fields[] = 'date = ?';               $params[] = date('Y-m-d\TH:i:s', strtotime($data['date'])); }
        if (isset($data['author']))          { $fields[] = 'author = ?';             $params[] = $data['author']; }
        if (isset($data['menu_order']))      { $fields[] = 'menu_order = ?';         $params[] = (int)$data['menu_order']; }
        if (isset($data['seo_description'])) { $fields[] = 'seo_description = ?';   $params[] = $data['seo_description']; }
        if (isset($data['meta']))            { $fields[] = 'meta = ?';               $params[] = json_encode($data['meta']); }
        if (isset($data['parent']))          { $fields[] = 'parent_id = ?';          $params[] = (int)$data['parent']; }

        if (isset($data['featured_media'])) {
            [$path, $alt] = $this->resolveFeaturedImage($data, $existing['title']);
            $fields[] = 'featured_image = ?';     $params[] = $path;
            $fields[] = 'featured_image_alt = ?'; $params[] = $alt ?: ($existing['featured_image_alt'] ?? '');
        }

        $params[] = $id;
        $this->pdo->prepare('UPDATE pages SET ' . implode(', ', $fields) . ' WHERE id = ?')
                  ->execute($params);

        $this->pagesPathCache = null;
        return $this->getPage((string)$id);
    }

    public function deletePage(string $idOrSlug, bool $force = false): ?array
    {
        $page = $this->getPage($idOrSlug);
        if (!$page) return null;

        if ($force) {
            $this->pdo->prepare('DELETE FROM pages WHERE id = ?')->execute([$page['id']]);
        } else {
            $this->pdo->prepare("UPDATE pages SET status = 'trash' WHERE id = ?")
                      ->execute([$page['id']]);
            $page['status'] = 'trash';
        }

        return $page;
    }

    // -------------------------------------------------------------------------
    // CACHE
    // -------------------------------------------------------------------------

    private function loadPagesPathCache(): array
    {
        if ($this->pagesPathCache !== null) return $this->pagesPathCache;
        $rows  = $this->pdo->query('SELECT id, parent_id, slug FROM pages')->fetchAll();
        $byId  = array_column($rows, null, 'id');
        $cache = [];
        foreach ($rows as $row) {
            $cache[(int)$row['id']] = $this->resolvePath((int)$row['id'], $byId, []);
        }
        $this->pagesPathCache = $cache;
        return $cache;
    }

    private function resolvePath(int $pageId, array &$byId, array $visited): string
    {
        if (!isset($byId[$pageId]) || in_array($pageId, $visited) || count($visited) > 10) {
            return $byId[$pageId]['slug'] ?? '';
        }
        $visited[] = $pageId;
        $row = $byId[$pageId];
        if ((int)$row['parent_id'] === 0) return $row['slug'];
        $parent = $this->resolvePath((int)$row['parent_id'], $byId, $visited);
        return $parent !== '' ? $parent . '/' . $row['slug'] : $row['slug'];
    }

    public function clearCache(): void
    {
        $this->mediaCache     = null;
        $this->pagesPathCache = null;
    }

    // -------------------------------------------------------------------------
    // HELPERS PRIVÉS
    // -------------------------------------------------------------------------

    /**
     * Synchronise les catégories et tags d'un post (delete + re-insert).
     * Accepte aussi bien des noms (string) que des IDs (int/string numérique).
     */
    private function syncTerms(int $postId, array $categories, array $tags): void
    {
        $this->pdo->prepare('DELETE FROM post_categories WHERE post_id = ?')->execute([$postId]);
        $this->pdo->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$postId]);

        foreach ($categories as $cat) {
            $catId = (is_int($cat) || ctype_digit((string)$cat))
                ? (int)$cat
                : $this->resolveOrCreateTerm('categories', (string)$cat);

            if ($catId) {
                $this->pdo->prepare(
                    'INSERT OR IGNORE INTO post_categories (post_id, category_id) VALUES (?, ?)'
                )->execute([$postId, $catId]);
            }
        }

        foreach ($tags as $tag) {
            $tagId = (is_int($tag) || ctype_digit((string)$tag))
                ? (int)$tag
                : $this->resolveOrCreateTerm('tags', (string)$tag);

            if ($tagId) {
                $this->pdo->prepare(
                    'INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)'
                )->execute([$postId, $tagId]);
            }
        }
    }

    /**
     * Trouve ou crée une catégorie / un tag par son nom. Retourne l'ID.
     */
    private function resolveOrCreateTerm(string $type, string $name): int
    {
        $table = $type === 'categories' ? 'categories' : 'tags';
        $slug  = Markdown::slugify($name);

        $stmt = $this->pdo->prepare("SELECT id FROM {$table} WHERE slug = ?");
        $stmt->execute([$slug]);
        $existing = $stmt->fetchColumn();

        if ($existing !== false) return (int)$existing;

        $id = $this->nextId($type);
        $this->pdo->prepare("INSERT INTO {$table} (id, slug, name) VALUES (?, ?, ?)")
                  ->execute([$id, $slug, $name]);

        return $id;
    }

    /**
     * Génère un slug unique en vérifiant la table posts ou pages.
     */
    private function generateUniqueSlug(string $title, string $type): string
    {
        $base  = Markdown::slugify($title) ?: 'sans-titre';
        $table = $type === 'pages' ? 'pages' : 'posts';
        $slug  = $base;
        $i     = 2;

        while (true) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE slug = ?");
            $stmt->execute([$slug]);
            if ((int)$stmt->fetchColumn() === 0) break;
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Résout un ID de média en chemin relatif (/media/fichier.jpg).
     */
    private function mediaPathById(int $id): ?string
    {
        foreach ($this->getMedia() as $item) {
            if ($item['id'] === $id) {
                $url     = $item['source_url'];
                $siteUrl = rtrim($this->config['site_url'], '/');
                return str_starts_with($url, $siteUrl)
                    ? substr($url, strlen($siteUrl))
                    : $url;
            }
        }
        return null;
    }

    /**
     * Extrait featured_image (chemin) et featured_image_alt depuis $data.
     * Retourne ['', ''] si aucune image fournie.
     */
    private function resolveFeaturedImage(array $data, string $fallbackAlt): array
    {
        if (empty($data['featured_media'])) return ['', ''];

        $path = $this->mediaPathById((int)$data['featured_media']);
        if (!$path) return ['', ''];

        $alt = $data['featured_image_alt'] ?? $fallbackAlt;
        return [$path, $alt];
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
