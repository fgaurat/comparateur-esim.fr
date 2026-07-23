<?php

class Api
{
    private ContentInterface $content;
    private array   $config;
    private Auth    $auth;
    private string  $method;
    private float   $startTime;
    private string  $requestPath = '';

    public function __construct(ContentInterface $content, array $config, Auth $auth)
    {
        $this->content   = $content;
        $this->config    = $config;
        $this->auth      = $auth;
        $this->method    = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->startTime = microtime(true);
    }

    public function handle(string $path): void
    {
        $path              = '/' . trim($path, '/');
        $this->requestPath = $path;

        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('X-WP-API: 1');
        header('Link: <' . $this->config['site_url'] . '/wp-json/>; rel="https://api.w.org/"');

        if ($this->method === 'OPTIONS') {
            http_response_code(200);
            $this->log(200);
            exit;
        }

        try {
            $this->dispatch($path);
        } catch (\Throwable $e) {
            http_response_code(500);
            $this->jsonResponse([
                'code'    => 'internal_error',
                'message' => $e->getMessage(),
                'data'    => ['status' => 500, 'file' => $e->getFile(), 'line' => $e->getLine()],
            ]);
        }
    }

    private function dispatch(string $path): void
    {

        $perPage = (int)($_GET['per_page'] ?? $this->config['posts_per_page']);
        $page    = max(1, (int)($_GET['page'] ?? 1));

        // ── API root ──────────────────────────────────────────────────────────
        if ($path === '/' || $path === '') {
            $this->jsonResponse($this->apiRoot());
            return;
        }

        // ── Posts collection ──────────────────────────────────────────────────
        if ($path === '/wp/v2/posts') {
            match ($this->method) {
                'GET'  => $this->listPosts($perPage, $page),
                'POST' => $this->requireAuth() ?: $this->createPost(),
                default => $this->methodNotAllowed(),
            };
            return;
        }

        // ── Single post ───────────────────────────────────────────────────────
        if (preg_match('#^/wp/v2/posts/(\d+|[a-z0-9-]+)$#', $path, $m)) {
            match ($this->method) {
                'GET'            => $this->showPost($m[1]),
                'PUT', 'PATCH'   => $this->requireAuth() ?: $this->updatePost($m[1]),
                'DELETE'         => $this->requireAuth() ?: $this->deletePost($m[1]),
                default          => $this->methodNotAllowed(),
            };
            return;
        }

        // ── Pages collection ──────────────────────────────────────────────────
        if ($path === '/wp/v2/pages') {
            match ($this->method) {
                'GET'  => $this->listPages(),
                'POST' => $this->requireAuth() ?: $this->createPage(),
                default => $this->methodNotAllowed(),
            };
            return;
        }

        // ── Single page ───────────────────────────────────────────────────────
        if (preg_match('#^/wp/v2/pages/(\d+|[a-z0-9-]+)$#', $path, $m)) {
            match ($this->method) {
                'GET'          => $this->showPage($m[1]),
                'PUT', 'PATCH' => $this->requireAuth() ?: $this->updatePage($m[1]),
                'DELETE'       => $this->requireAuth() ?: $this->deletePage($m[1]),
                default        => $this->methodNotAllowed(),
            };
            return;
        }

        // ── Media collection ──────────────────────────────────────────────────
        if ($path === '/wp/v2/media') {
            match ($this->method) {
                'GET'  => $this->listMedia(),
                'POST' => $this->requireAuth() ?: $this->uploadMedia(),
                default => $this->methodNotAllowed(),
            };
            return;
        }

        // ── Single media ──────────────────────────────────────────────────────
        if (preg_match('#^/wp/v2/media/(\d+|[a-z0-9-]+)$#', $path, $m)) {
            match ($this->method) {
                'GET'    => $this->showMedia($m[1]),
                'DELETE' => $this->requireAuth() ?: $this->deleteMedia($m[1]),
                default  => $this->methodNotAllowed(),
            };
            return;
        }

        // ── Taxonomies (lecture seule) ─────────────────────────────────────────
        if ($path === '/wp/v2/categories') {
            $this->jsonResponse($this->content->getCategories());
            return;
        }

        if ($path === '/wp/v2/tags') {
            $this->jsonResponse($this->content->getTags());
            return;
        }

        // ── Users (stub) ──────────────────────────────────────────────────────
        if ($path === '/wp/v2/users') {
            $this->jsonResponse([[
                'id'     => 1,
                'name'   => $this->config['author'],
                'slug'   => Markdown::slugify($this->config['author']),
                'link'   => $this->config['site_url'] . '/author/' . Markdown::slugify($this->config['author']) . '/',
                '_links' => [],
            ]]);
            return;
        }

        // ── oEmbed ────────────────────────────────────────────────────────────
        if ($path === '/oembed/1.0/embed') {
            $url = $_GET['url'] ?? $this->config['site_url'] . '/';
            $this->jsonResponse([
                'version'       => '1.0',
                'type'          => 'rich',
                'provider_name' => 'WordPress',
                'provider_url'  => $this->config['site_url'],
                'title'         => $this->config['site_name'],
                'html'          => '<blockquote><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($this->config['site_name']) . '</a></blockquote>',
                'width'         => 600,
                'height'        => 338,
                'author_name'   => $this->config['author'],
                'author_url'    => $this->config['site_url'],
            ]);
            return;
        }

        // ── Route inconnue ────────────────────────────────────────────────────
        http_response_code(404);
        $this->jsonResponse([
            'code'    => 'rest_no_route',
            'message' => 'No route was found matching the URL and request method.',
            'data'    => ['status' => 404],
        ]);
    }

    // =========================================================================
    // HANDLERS GET
    // =========================================================================

    private function listPosts(int $perPage, int $page): void
    {
        $args = ['per_page' => $perPage, 'page' => $page];
        if (!empty($_GET['category_slug'])) $args['category_slug'] = $_GET['category_slug'];
        if (!empty($_GET['tag_slug']))      $args['tag_slug']       = $_GET['tag_slug'];
        if (isset($_GET['location_page_id'])) $args['location_page_id'] = (int)$_GET['location_page_id'];

        $posts = $this->content->getPosts($args);
        $total = $this->content->getPostsCount($args);
        $pages = max(1, (int)ceil($total / $perPage));
        header('X-WP-Total: ' . $total);
        header('X-WP-TotalPages: ' . $pages);
        $this->jsonResponse(array_map([$this, 'formatPost'], $posts));
    }

    private function showPost(string $idOrSlug): void
    {
        $post = $this->content->getPost($idOrSlug);
        if (!$post) { $this->notFound('post'); return; }
        $this->jsonResponse($this->formatPost($post));
    }

    private function listPages(): void
    {
        if (isset($_GET['parent'])) {
            $parentId = (int)$_GET['parent'];
            $pages    = $this->content->getChildPages($parentId);
            $this->jsonResponse(array_map([$this, 'formatPage'], $pages));
            return;
        }
        $pages = $this->content->getPages();
        $this->jsonResponse(array_map([$this, 'formatPage'], $pages));
    }

    private function showPage(string $idOrSlug): void
    {
        $page = $this->content->getPage($idOrSlug);
        if (!$page) { $this->notFound('page'); return; }
        $this->jsonResponse($this->formatPage($page));
    }

    private function listMedia(): void
    {
        $this->jsonResponse(array_map([$this, 'formatMedia'], $this->content->getMedia()));
    }

    private function showMedia(string $idOrSlug): void
    {
        $item = $this->content->getMediaItem($idOrSlug);
        if (!$item) { $this->notFound('attachment'); return; }
        $this->jsonResponse($this->formatMedia($item));
    }

    // =========================================================================
    // HANDLERS POST (création)
    // =========================================================================

    private function createPost(): void
    {
        $data = $this->parseBody();

        $title = $this->extractText($data['title'] ?? '');
        if ($title === '') {
            $this->unprocessable('Le champ "title" est obligatoire.');
            return;
        }

        $data['title']   = $title;
        $data['content'] = $this->ensureHtml($this->extractText($data['content'] ?? ''));
        $data['excerpt'] = $this->extractText($data['excerpt'] ?? '');

        $post = $this->content->createPost($data);
        if (!$post) {
            http_response_code(500);
            $this->jsonResponse(['code' => 'create_failed', 'message' => 'Impossible de créer le post.']);
            return;
        }
        http_response_code(201);
        $this->jsonResponse($this->formatPost($post));
    }

    private function createPage(): void
    {
        $data = $this->parseBody();

        $title = $this->extractText($data['title'] ?? '');
        if ($title === '') {
            $this->unprocessable('Le champ "title" est obligatoire.');
            return;
        }

        $data['title']   = $title;
        $data['content'] = $this->ensureHtml($this->extractText($data['content'] ?? ''));
        $data['excerpt'] = $this->extractText($data['excerpt'] ?? '');

        $page = $this->content->createPage($data);
        if (!$page) {
            http_response_code(500);
            $this->jsonResponse(['code' => 'create_failed', 'message' => 'Impossible de créer la page.']);
            return;
        }
        http_response_code(201);
        $this->jsonResponse($this->formatPage($page));
    }

    private function uploadMedia(): void
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $mimeToExt = [
            'image/jpeg' => 'jpg', 'image/png' => 'png',
            'image/gif'  => 'gif', 'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];

        // ── Mode A : raw binary (WordPress REST API standard) ─────────────────
        // seo-content envoie : Content-Type: image/jpeg + Content-Disposition: attachment; filename="..."
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $contentDisp = $_SERVER['HTTP_CONTENT_DISPOSITION'] ?? '';
        $rawMime     = strtolower(trim(explode(';', $contentType)[0]));

        if (in_array($rawMime, $allowed) && empty($_FILES['file'])) {
            $rawBody = file_get_contents('php://input');
            if (empty($rawBody)) {
                $this->logUpload('no_body', 'Raw binary vide (Content-Type: ' . $rawMime . ')');
                $this->unprocessable('Corps de requête vide.');
                return;
            }

            // Extraire le nom de fichier depuis Content-Disposition
            $originalName = '';
            if (preg_match('/filename=["\']?([^"\';\s]+)["\']?/i', $contentDisp, $m)) {
                $originalName = basename($m[1]);
            }
            if ($originalName === '') {
                $originalName = 'upload.' . ($mimeToExt[$rawMime] ?? 'bin');
            }

            $ext      = strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) ?: ($mimeToExt[$rawMime] ?? 'bin');
            $slug     = Markdown::slugify(pathinfo($originalName, PATHINFO_FILENAME));
            $filename = ($slug ?: 'upload') . '.' . $ext;
            $dest     = BASE_PATH . '/media/' . $filename;

            // Évite les collisions
            $i = 2; $base = $slug ?: 'upload';
            while (file_exists($dest)) {
                $filename = $base . '-' . $i . '.' . $ext;
                $dest     = BASE_PATH . '/media/' . $filename;
                $i++;
            }

            if (!is_dir(BASE_PATH . '/media')) mkdir(BASE_PATH . '/media', 0755, true);
            if (file_put_contents($dest, $rawBody) === false) {
                $this->logUpload('write_failed', 'file_put_contents échoué vers ' . $dest);
                http_response_code(500);
                $this->jsonResponse(['code' => 'upload_failed', 'message' => 'Impossible d\'écrire le fichier (permissions du dossier media/ ?)']);
                return;
            }

            $mime = $rawMime;
            $this->logUpload('ok_raw', sprintf('Raw binary sauvegardé : %s (%s, %.1f Ko)', $filename, $mime, strlen($rawBody) / 1024));
            $this->respondWithMedia($filename, $mime, $dest);
            return;
        }

        // ── Mode B : multipart/form-data ──────────────────────────────────────
        if (!empty($_FILES['file'])) {

            $file      = $_FILES['file'];
            $uploadErr = $file['error'] ?? UPLOAD_ERR_OK;

        // Vérification du code d'erreur PHP
        if ($uploadErr !== UPLOAD_ERR_OK) {
            $errMessages = [
                UPLOAD_ERR_INI_SIZE   => 'Fichier trop volumineux (upload_max_filesize=' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_FORM_SIZE  => 'Fichier trop volumineux (MAX_FILE_SIZE du formulaire dépassé).',
                UPLOAD_ERR_PARTIAL    => 'Le fichier n\'a été que partiellement uploadé.',
                UPLOAD_ERR_NO_FILE    => 'Aucun fichier n\'a été uploadé.',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant côté serveur.',
                UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier sur le disque.',
                UPLOAD_ERR_EXTENSION  => 'Upload bloqué par une extension PHP.',
            ];
            $msg = $errMessages[$uploadErr] ?? "Erreur d'upload inconnue (code {$uploadErr}).";
            $this->logUpload('php_error_' . $uploadErr, $msg . ' file=' . ($file['name'] ?? '-'));
            $this->unprocessable($msg);
            return;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $mime    = mime_content_type($file['tmp_name']);

        if (!$mime || !in_array($mime, $allowed)) {
            $this->logUpload('mime_rejected', 'MIME non autorisé : ' . ($mime ?: 'inconnu') . ' file=' . $file['name']);
            $this->unprocessable('Type de fichier non autorisé : ' . ($mime ?: 'inconnu') . '. Types acceptés : ' . implode(', ', $allowed));
            return;
        }

            $originalName = $file['name'];
            $tmpPath      = $file['tmp_name'];
            $moveFunc     = 'move_uploaded_file';

        // ── Mode B : corps binaire brut + Content-Disposition (wp-cli, API clients) ──
        } else {
            // Extraction du nom de fichier depuis Content-Disposition
            $disposition = $_SERVER['HTTP_CONTENT_DISPOSITION'] ?? '';
            $originalName = '';
            if (preg_match('/filename=["\']?([^"\';\s]+)["\']?/i', $disposition, $m)) {
                $originalName = basename($m[1]);
            }

            if ($originalName === '') {
                $this->logUpload('no_file', 'Ni $_FILES ni Content-Disposition:filename trouvé (post_max_size=' . ini_get('post_max_size') . ')');
                $this->unprocessable(
                    'Aucun fichier reçu. Deux modes supportés : ' .
                    '(1) multipart/form-data avec champ "file", ' .
                    '(2) corps binaire brut avec header Content-Disposition: attachment; filename="nom.jpg".'
                );
                return;
            }

            $body = file_get_contents('php://input');
            if ($body === false || $body === '') {
                $this->logUpload('empty_body', 'Corps de requête vide pour ' . $originalName);
                $this->unprocessable('Corps de requête vide.');
                return;
            }

            // Écriture dans un fichier temporaire pour vérifier le MIME
            $tmpPath = tempnam(sys_get_temp_dir(), 'up_');
            file_put_contents($tmpPath, $body);

            $mime = mime_content_type($tmpPath);
            if (!$mime || !in_array($mime, $allowed)) {
                unlink($tmpPath);
                $this->logUpload('mime_rejected', 'MIME non autorisé : ' . ($mime ?: 'inconnu') . ' file=' . $originalName);
                $this->unprocessable('Type de fichier non autorisé : ' . ($mime ?: 'inconnu') . '. Types acceptés : ' . implode(', ', $allowed));
                return;
            }

            $moveFunc = 'rename'; // pas move_uploaded_file pour un fichier créé manuellement
        }

        // ── Calcul du nom de destination (commun aux deux modes) ──────────────
        $ext      = pathinfo($originalName, PATHINFO_EXTENSION);
        $slug     = Markdown::slugify(pathinfo($originalName, PATHINFO_FILENAME));
        $filename = $slug . '.' . strtolower($ext);
        $dest     = BASE_PATH . '/media/' . $filename;

        // Évite les collisions
        $i = 2;
        $base = $slug;
        while (file_exists($dest)) {
            $filename = $base . '-' . $i . '.' . strtolower($ext);
            $dest     = BASE_PATH . '/media/' . $filename;
            $i++;
        }

        if (!$moveFunc($tmpPath, $dest)) {
            $this->logUpload('move_failed', 'Déplacement échoué vers ' . $dest . ' (permissions ?)');
            http_response_code(500);
            $this->jsonResponse(['code' => 'upload_failed', 'message' => 'Impossible de sauvegarder le fichier (vérifiez les permissions du dossier media/).']);
            return;
        }

        $this->logUpload('ok_multipart', sprintf('Fichier sauvegardé : %s (%s, %.1f Ko)', $filename, $mime, filesize($dest) / 1024));
        $this->respondWithMedia($filename, $mime, $dest);
    }

    private function respondWithMedia(string $filename, string $mime, string $dest): void
    {
        $this->content->clearCache();
        $mediaSlug = pathinfo($filename, PATHINFO_FILENAME);
        $item      = $this->content->getMediaItem($mediaSlug);

        if (!$item) {
            $size = @getimagesize($dest);
            $item = [
                'id'         => 0,
                'slug'       => $mediaSlug,
                'date'       => date('Y-m-d\TH:i:s'),
                'title'      => $mediaSlug,
                'status'     => 'inherit',
                'type'       => 'attachment',
                'mime_type'  => $mime,
                'source_url' => $this->config['site_url'] . '/media/' . $filename,
                'media_type' => 'image',
                'width'      => $size ? $size[0] : 0,
                'height'     => $size ? $size[1] : 0,
                'file'       => $filename,
                'link'       => $this->config['site_url'] . '/media/' . $filename,
                'alt_text'   => $mediaSlug,
            ];
        }

        http_response_code(201);
        $this->jsonResponse($this->formatMedia($item));
    }

    // =========================================================================
    // HANDLERS PUT/PATCH (mise à jour)
    // =========================================================================

    private function updatePost(string $idOrSlug): void
    {
        $post = $this->content->getPost($idOrSlug);
        if (!$post) { $this->notFound('post'); return; }

        $data = $this->parseBody();
        if (isset($data['title']))   $data['title']   = $this->extractText($data['title']);
        if (isset($data['content'])) $data['content'] = $this->ensureHtml($this->extractText($data['content']));
        if (isset($data['excerpt'])) $data['excerpt'] = $this->extractText($data['excerpt']);

        $updated = $this->content->updatePost($idOrSlug, $data);
        if (!$updated) {
            http_response_code(500);
            $this->jsonResponse(['code' => 'update_failed', 'message' => 'Impossible de mettre à jour le post.']);
            return;
        }
        $this->jsonResponse($this->formatPost($updated));
    }

    private function updatePage(string $idOrSlug): void
    {
        $page = $this->content->getPage($idOrSlug);
        if (!$page) { $this->notFound('page'); return; }

        $data = $this->parseBody();
        if (isset($data['title']))   $data['title']   = $this->extractText($data['title']);
        if (isset($data['content'])) $data['content'] = $this->ensureHtml($this->extractText($data['content']));
        if (isset($data['excerpt'])) $data['excerpt'] = $this->extractText($data['excerpt']);

        $updated = $this->content->updatePage($idOrSlug, $data);
        if (!$updated) {
            http_response_code(500);
            $this->jsonResponse(['code' => 'update_failed', 'message' => 'Impossible de mettre à jour la page.']);
            return;
        }
        $this->jsonResponse($this->formatPage($updated));
    }

    // =========================================================================
    // HANDLERS DELETE
    // =========================================================================

    private function deletePost(string $idOrSlug): void
    {
        $post = $this->content->getPost($idOrSlug);
        if (!$post) { $this->notFound('post'); return; }

        $force   = filter_var($_GET['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $deleted = $this->content->deletePost($idOrSlug, $force);
        $this->jsonResponse($this->formatPost($deleted));
    }

    private function deletePage(string $idOrSlug): void
    {
        $page = $this->content->getPage($idOrSlug);
        if (!$page) { $this->notFound('page'); return; }

        $force   = filter_var($_GET['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $deleted = $this->content->deletePage($idOrSlug, $force);
        $this->jsonResponse($this->formatPage($deleted));
    }

    private function deleteMedia(string $idOrSlug): void
    {
        $item = $this->content->getMediaItem($idOrSlug);
        if (!$item) { $this->notFound('attachment'); return; }

        $force = filter_var($_GET['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (!$force) {
            $this->unprocessable('La suppression de médias nécessite ?force=true.');
            return;
        }

        $file = BASE_PATH . '/media/' . $item['file'];
        if (file_exists($file)) unlink($file);
        $this->content->clearCache();

        $this->jsonResponse($this->formatMedia($item));
    }

    // =========================================================================
    // AUTHENTIFICATION
    // =========================================================================

    /**
     * Vérifie l'authentification si elle est configurée.
     * Retourne true et envoie une 401 si l'auth échoue, null si OK ou non requise.
     */
    private function requireAuth(): ?true
    {
        if (!$this->auth->isRequired()) return null;

        if (!$this->auth->check()) {
            http_response_code(401);
            header('WWW-Authenticate: Basic realm="WordPress API"');
            $this->jsonResponse([
                'code'    => 'rest_forbidden',
                'message' => 'Désolé, vous n\'êtes pas autorisé à effectuer cette action.',
                'data'    => ['status' => 401],
            ]);
            return true;
        }

        return null;
    }

    // =========================================================================
    // FORMATTERS
    // =========================================================================

    private function formatPost(array $post): array
    {
        $siteUrl = $this->config['site_url'];
        return [
            'id'             => $post['id'],
            'date'           => $post['date'],
            'date_gmt'       => $post['date'],
            'guid'           => ['rendered' => $siteUrl . '/?p=' . $post['id']],
            'modified'       => $post['modified'],
            'modified_gmt'   => $post['modified'],
            'slug'           => $post['slug'],
            'status'         => $post['status'],
            'type'           => 'post',
            'link'           => $post['link'],
            'title'          => ['rendered' => htmlspecialchars($post['title'])],
            'content'        => ['rendered' => $post['content'], 'protected' => false],
            'excerpt'        => ['rendered' => '<p>' . htmlspecialchars($post['excerpt']) . '</p>', 'protected' => false],
            'author'         => 1,
            'featured_media' => $post['featured_media'],
            'comment_status' => 'open',
            'ping_status'    => 'open',
            'sticky'         => false,
            'template'       => '',
            'format'         => 'standard',
            'categories'     => $this->categoryIdsFor($post['categories']),
            'tags'           => $this->tagIdsFor($post['tags']),
            'meta'           => $post['meta'] ?? [],
            '_links'         => $this->postLinks($post, $siteUrl),
            '_embedded'      => $this->buildEmbedded($post),
        ];
    }

    private function formatPage(array $page): array
    {
        $siteUrl = $this->config['site_url'];
        return [
            'id'             => $page['id'],
            'date'           => $page['date'],
            'date_gmt'       => $page['date'],
            'guid'           => ['rendered' => $siteUrl . '/?page_id=' . $page['id']],
            'modified'       => $page['modified'],
            'modified_gmt'   => $page['modified'],
            'slug'           => $page['slug'],
            'status'         => $page['status'],
            'type'           => 'page',
            'link'           => $page['link'],
            'title'          => ['rendered' => htmlspecialchars($page['title'])],
            'content'        => ['rendered' => $page['content'], 'protected' => false],
            'excerpt'        => ['rendered' => '<p>' . htmlspecialchars($page['excerpt']) . '</p>', 'protected' => false],
            'author'         => 1,
            'featured_media' => $page['featured_media'],
            'parent'         => $page['parent'] ?? 0,
            'menu_order'     => $page['menu_order'] ?? 0,
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'template'       => '',
            'meta'           => $page['meta'] ?? [],
            '_links'         => [],
            '_embedded'      => $this->buildEmbedded($page),
        ];
    }

    private function formatMedia(array $item): array
    {
        return [
            'id'            => $item['id'],
            'date'          => $item['date'],
            'slug'          => $item['slug'],
            'status'        => $item['status'],
            'type'          => 'attachment',
            'link'          => $item['link'],
            'title'         => ['rendered' => htmlspecialchars($item['title'])],
            'author'        => 1,
            'alt_text'      => $item['alt_text'],
            'media_type'    => $item['media_type'],
            'mime_type'     => $item['mime_type'],
            'media_details' => [
                'width'  => $item['width'],
                'height' => $item['height'],
                'file'   => $item['file'],
                'sizes'  => [
                    'full' => [
                        'source_url' => $item['source_url'],
                        'width'      => $item['width'],
                        'height'     => $item['height'],
                        'mime_type'  => $item['mime_type'],
                    ],
                ],
            ],
            'source_url'    => $item['source_url'],
            '_links'        => [],
        ];
    }

    private function buildEmbedded(array $post): array
    {
        if (empty($post['featured_image_url'])) return [];

        $imgUrl = str_starts_with($post['featured_image_url'], 'http')
            ? $post['featured_image_url']
            : $this->config['site_url'] . $post['featured_image_url'];

        return [
            'wp:featuredmedia' => [[
                'id'            => $post['featured_media'],
                'source_url'    => $imgUrl,
                'alt_text'      => $post['featured_image_alt'] ?? '',
                'media_type'    => 'image',
                'media_details' => ['sizes' => ['full' => ['source_url' => $imgUrl]]],
            ]],
        ];
    }

    private function categoryIdsFor(array $names): array
    {
        $cats = $this->content->getCategories();
        $ids  = [];
        foreach ($names as $name) {
            foreach ($cats as $cat) {
                if ($cat['name'] === $name) { $ids[] = $cat['id']; break; }
            }
        }
        return $ids;
    }

    private function tagIdsFor(array $names): array
    {
        $tags = $this->content->getTags();
        $ids  = [];
        foreach ($names as $name) {
            foreach ($tags as $tag) {
                if ($tag['name'] === $name) { $ids[] = $tag['id']; break; }
            }
        }
        return $ids;
    }

    private function postLinks(array $post, string $siteUrl): array
    {
        $base = $siteUrl . '/wp-json/wp/v2';
        return [
            'self'       => [['href' => $base . '/posts/' . $post['id']]],
            'collection' => [['href' => $base . '/posts']],
            'about'      => [['href' => $base . '/types/post']],
            'author'     => [['href' => $base . '/users/1', 'embeddable' => true]],
        ];
    }

    private function apiRoot(): array
    {
        $base = $this->config['site_url'] . '/wp-json/wp/v2';
        return [
            'name'        => $this->config['site_name'],
            'description' => $this->config['site_description'],
            'url'         => $this->config['site_url'],
            'home'        => $this->config['site_url'],
            'namespaces'  => ['wp/v2'],
            'routes'      => [
                '/wp/v2/posts'      => ['namespace' => 'wp/v2', 'methods' => ['GET', 'POST'],             '_links' => ['self' => $base . '/posts']],
                '/wp/v2/posts/{id}' => ['namespace' => 'wp/v2', 'methods' => ['GET', 'PUT', 'PATCH', 'DELETE']],
                '/wp/v2/pages'      => ['namespace' => 'wp/v2', 'methods' => ['GET', 'POST'],             '_links' => ['self' => $base . '/pages']],
                '/wp/v2/pages/{id}' => ['namespace' => 'wp/v2', 'methods' => ['GET', 'PUT', 'PATCH', 'DELETE']],
                '/wp/v2/media'      => ['namespace' => 'wp/v2', 'methods' => ['GET', 'POST'],             '_links' => ['self' => $base . '/media']],
                '/wp/v2/media/{id}' => ['namespace' => 'wp/v2', 'methods' => ['GET', 'DELETE']],
                '/wp/v2/categories' => ['namespace' => 'wp/v2', 'methods' => ['GET'],                    '_links' => ['self' => $base . '/categories']],
                '/wp/v2/tags'       => ['namespace' => 'wp/v2', 'methods' => ['GET'],                    '_links' => ['self' => $base . '/tags']],
                '/wp/v2/users'      => ['namespace' => 'wp/v2', 'methods' => ['GET'],                    '_links' => ['self' => $base . '/users']],
            ],
        ];
    }

    // =========================================================================
    // UTILITAIRES
    // =========================================================================

    /**
     * Parse le corps JSON de la requête
     */
    private function parseBody(): array
    {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Extrait le texte depuis un champ WordPress (string ou {rendered/raw: "..."})
     */
    private function extractText(mixed $field): string
    {
        if (is_string($field)) return $field;
        if (is_array($field))  return $field['raw'] ?? $field['rendered'] ?? '';
        return '';
    }

    /**
     * Convertit le contenu en HTML si c'est du Markdown brut.
     * Si le contenu contient déjà des balises HTML ouvrantes, il est retourné tel quel.
     */
    private function ensureHtml(string $content): string
    {
        if ($content === '') return '';
        if (!preg_match('/<[a-z][a-z0-9]*[\s>\/]/i', $content)) {
            return Markdown::toHtml($content);
        }
        return $content;
    }

    private function notFound(string $type): void
    {
        http_response_code(404);
        $this->jsonResponse([
            'code'    => 'rest_' . $type . '_invalid_id',
            'message' => 'Invalid ID.',
            'data'    => ['status' => 404],
        ]);
    }

    private function methodNotAllowed(): void
    {
        http_response_code(405);
        $this->jsonResponse([
            'code'    => 'rest_method_not_allowed',
            'message' => 'Method not allowed.',
            'data'    => ['status' => 405],
        ]);
    }

    private function unprocessable(string $message): void
    {
        http_response_code(422);
        $this->jsonResponse([
            'code'    => 'rest_invalid_param',
            'message' => $message,
            'data'    => ['status' => 422],
        ]);
    }

    private function jsonResponse(mixed $data): void
    {
        $status = http_response_code() ?: 200;
        $this->log($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    // =========================================================================
    // LOGS
    // =========================================================================

    /**
     * Log spécifique aux uploads (toujours écrit, quel que soit le résultat final)
     */
    private function logUpload(string $result, string $detail): void
    {
        $logConfig = $this->config['api_log'] ?? false;
        if ($logConfig === false) return;

        $logFile = is_string($logConfig) && $logConfig !== ''
            ? $logConfig
            : BASE_PATH . '/logs/api.log';

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

        $ip   = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '-')[0]);
        $line = sprintf(
            '[%s] UPLOAD %s ip=%s %s',
            date('Y-m-d H:i:s'),
            strtoupper($result),
            $ip,
            $detail
        );

        file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private function log(int $status): void
    {
        $logConfig = $this->config['api_log'] ?? false;
        if ($logConfig === false) return;

        $logFile = is_string($logConfig) && $logConfig !== ''
            ? $logConfig
            : BASE_PATH . '/logs/api.log';

        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $duration = (int)round((microtime(true) - $this->startTime) * 1000);

        // IP : tient compte d'un éventuel proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '-';
        $ip = trim(explode(',', $ip)[0]); // premier IP si liste

        $query = ($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
        $ua    = substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 150);

        // Masque le mot de passe Basic Auth dans les logs
        $auth = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] . ':***' : '-';

        $line = sprintf(
            '[%s] %d %s /wp-json%s%s %dms ip=%s auth=%s ua="%s"',
            date('Y-m-d H:i:s'),
            $status,
            str_pad($this->method, 7),
            $this->requestPath,
            $query,
            $duration,
            $ip,
            $auth,
            $ua
        );

        file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
