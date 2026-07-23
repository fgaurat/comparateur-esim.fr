<?php
declare(strict_types=1);
session_start();

define('BASE_PATH', __DIR__);
// ── Config ────────────────────────────────────────────────────────────────────
$config = require BASE_PATH . '/config.php';

// ── Libraries ─────────────────────────────────────────────────────────────────
require BASE_PATH . '/lib/Markdown.php';
require BASE_PATH . '/lib/ContentInterface.php';

$_storage = $config['storage'] ?? 'markdown';
if ($_storage === 'sqlite') {
    require BASE_PATH . '/lib/ContentSQLite.php';
} else {
    require BASE_PATH . '/lib/HtmlToMarkdown.php';
    require BASE_PATH . '/lib/Content.php';
}
unset($_storage);

require BASE_PATH . '/lib/Auth.php';
require BASE_PATH . '/lib/Api.php';
require BASE_PATH . '/lib/Seo.php';
require BASE_PATH . '/lib/TemplateHelpers.php';
require BASE_PATH . '/lib/BotDetector.php';
require BASE_PATH . '/lib/Mailer.php';

// ── Theme ─────────────────────────────────────────────────────────────────────
$themeName = $config['theme'] ?? 'default';
$themePath = BASE_PATH . '/templates/' . $themeName;

if (!is_dir($themePath)) {
    throw new RuntimeException("Thème introuvable : \"{$themeName}\" (chemin : {$themePath})");
}

define('THEME_PATH', $themePath);

// ── Services ──────────────────────────────────────────────────────────────────
$content = ($config['storage'] ?? 'markdown') === 'sqlite'
    ? new ContentSQLite($config)
    : new Content($config);
$auth    = new Auth($config);
$seo     = new Seo($config);
$h       = new TemplateHelpers($content, $config);

// ── Bot detection ─────────────────────────────────────────────────────────────
$botDetector = new BotDetector($config);
$botAgent    = $botDetector->detect(
    $_SERVER['HTTP_USER_AGENT'] ?? '',
    $_SERVER['REMOTE_ADDR']     ?? ''
);
$isBot   = $botAgent !== null;
$isHuman = !$isBot;

// ── Request URI ───────────────────────────────────────────────────────────────
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestUri = '/' . ltrim($requestUri, '/');

// ── 1. WordPress REST API ─────────────────────────────────────────────────────
if (str_starts_with($requestUri, '/wp-json')) {
    $apiPath = substr($requestUri, strlen('/wp-json')) ?: '/';
    (new Api($content, $config, $auth))->handle($apiPath);
    exit;
}

// ── 2. sitemap.xml ────────────────────────────────────────────────────────────
if ($requestUri === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex');
    echo $seo->sitemap($content);
    exit;
}

// ── 3. robots.txt ─────────────────────────────────────────────────────────────
if ($requestUri === '/robots.txt') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo $seo->robotsTxt();
    exit;
}

// ── 4. RSS Feed ───────────────────────────────────────────────────────────────
if (in_array($requestUri, ['/feed', '/feed/'])) {
    header('Content-Type: application/rss+xml; charset=UTF-8');
    echo $seo->rssFeed($content);
    exit;
}

// ── 4b. ?p={id} redirect ──────────────────────────────────────────────────────
if ($requestUri === '/' && isset($_GET['p']) && ctype_digit($_GET['p'])) {
    $post = $content->getPost($_GET['p']);
    if ($post && $post['status'] === 'publish') {
        header('Location: ' . $post['link'], true, 301);
        exit;
    }
}

// ── WordPress fingerprint routes ──────────────────────────────────────────────

if ($requestUri === '/wp-login.php') {
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    $redirectTo = htmlspecialchars($_GET['redirect_to'] ?? '/wp-admin/');
    $siteName   = htmlspecialchars($config['site_name']);
    $siteUrl    = htmlspecialchars($config['site_url']);
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en-US">
    <head><meta charset="UTF-8"><title>Log In &lsaquo; {$siteName} &#8212; WordPress</title></head>
    <body class="login login-action-login wp-core-ui">
    <div id="login">
        <h1><a href="{$siteUrl}/">{$siteName}</a></h1>
        <form name="loginform" id="loginform" action="/wp-login.php" method="post">
            <p><label for="user_login">Username or Email Address<br>
            <input type="text" name="log" id="user_login" class="input" value="" size="20" autocomplete="username"></label></p>
            <div class="user-pass-wrap"><label for="user_pass">Password<br>
            <input type="password" name="pwd" id="user_pass" class="input password-input" value="" size="20" autocomplete="current-password"></label></div>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Log In">
                <input type="hidden" name="redirect_to" value="{$redirectTo}">
                <input type="hidden" name="testcookie" value="1">
            </p>
        </form>
    </div>
    </body></html>
    HTML;
    exit;
}

if (in_array($requestUri, ['/wp-admin', '/wp-admin/'])) {
    header('Location: /wp-login.php?redirect_to=' . urlencode('/wp-admin/'), true, 302);
    exit;
}

if ($requestUri === '/readme.html') {
    http_response_code(200);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>WordPress &rsaquo; ReadMe</title></head>'
       . '<body><h1>WordPress 6.7.2</h1><p>Semantic Personal Publishing Platform</p>'
       . '<p>WordPress is web software you can use to create a beautiful website or blog. '
       . '<a href="https://wordpress.org/">WordPress.org</a></p></body></html>';
    exit;
}

if ($requestUri === '/license.txt') {
    http_response_code(200);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "WordPress - Web publishing software\n"
       . "Copyright 2011-2024 by the contributors\n\n"
       . "This program is free software; you can redistribute it and/or modify\n"
       . "it under the terms of the GNU General Public License as published by\n"
       . "the Free Software Foundation; either version 2 of the License, or\n"
       . "(at your option) any later version.\n\n"
       . "This program is distributed in the hope that it will be useful,\n"
       . "but WITHOUT ANY WARRANTY; without even the implied warranty of\n"
       . "MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the\n"
       . "GNU General Public License for more details.\n";
    exit;
}

if ($requestUri === '/xmlrpc.php') {
    http_response_code(405);
    header('Content-Type: text/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>'
       . '<methodResponse><fault><value><struct>'
       . '<member><name>faultCode</name><value><int>405</int></value></member>'
       . '<member><name>faultString</name><value><string>XML-RPC services are disabled on this site.</string></value></member>'
       . '</struct></value></fault></methodResponse>';
    exit;
}

if (str_starts_with($requestUri, '/wp-content/uploads/')) {
    $uploadPath = substr($requestUri, strlen('/wp-content/uploads'));
    header('Location: /media' . $uploadPath, true, 301);
    exit;
}

if (preg_match('#^/wp-content/themes/([^/]+)/style\.css$#', $requestUri, $m)) {
    $cssFile = BASE_PATH . '/templates/' . $m[1] . '/style.css';
    if (file_exists($cssFile)) {
        header('Content-Type: text/css; charset=UTF-8');
        readfile($cssFile);
    } else {
        http_response_code(404);
    }
    exit;
}

// ── Link headers pour toutes les pages HTML ────────────────────────────────────
header('Link: <' . $config['site_url'] . '/wp-json/>; rel="https://api.w.org/"');
header('Link: <' . $config['site_url'] . '/wp-json/wp/v2/posts>; rel="https://api.w.org/v2/posts"', false);

// ── 5. Homepage & pagination ──────────────────────────────────────────────────
$perPage = (int)$config['posts_per_page'];

if ($requestUri === '/' || preg_match('#^/page/(\d+)/?$#', $requestUri, $m)) {
    $currentPage = isset($m[1]) ? (int)$m[1] : 1;
    $totalPosts  = $content->getPostsCount();
    $totalPages  = max(1, (int)ceil($totalPosts / $perPage));

    // Redirect /page/1/ → /
    if ($currentPage === 1 && str_contains($requestUri, '/page/')) {
        header('Location: /', true, 301);
        exit;
    }

    $posts = $content->getPosts(['page' => $currentPage, 'per_page' => $perPage]);

    $seoData = [
        'type'        => 'website',
        'title'       => $config['site_name'],
        'description' => $config['site_description'],
        'canonical'   => $config['site_url'] . ($currentPage > 1 ? '/page/' . $currentPage . '/' : '/'),
    ];
    $bodyClass = 'home';

    require THEME_PATH . '/home.php';
    exit;
}

// ── 6. Category archive ───────────────────────────────────────────────────────
if (preg_match('#^/category/([a-z0-9-]+)/?$#', $requestUri, $m)) {
    $catSlug = $m[1];
    $posts   = $content->getPosts(['category_slug' => $catSlug, 'per_page' => $perPage]);
    $currentPage = 1;
    $totalPages  = 1;

    $catName = $catSlug;
    foreach ($content->getCategories() as $c) {
        if ($c['slug'] === $catSlug) { $catName = $c['name']; break; }
    }

    $seoData = [
        'type'        => 'website',
        'title'       => 'Catégorie : ' . $catName,
        'description' => 'Tous les articles dans la catégorie ' . $catName,
        'canonical'   => $config['site_url'] . '/category/' . $catSlug . '/',
    ];
    $bodyClass = 'archive category';

    require THEME_PATH . '/home.php';
    exit;
}

// ── 7. Tag archive ────────────────────────────────────────────────────────────
if (preg_match('#^/tag/([a-z0-9-]+)/?$#', $requestUri, $m)) {
    $tagSlug = $m[1];
    $posts   = $content->getPosts(['tag_slug' => $tagSlug, 'per_page' => $perPage]);
    $currentPage = 1;
    $totalPages  = 1;

    $tagName = $tagSlug;
    foreach ($content->getTags() as $t) {
        if ($t['slug'] === $tagSlug) { $tagName = $t['name']; break; }
    }

    $seoData = [
        'type'        => 'website',
        'title'       => 'Tag : ' . $tagName,
        'description' => 'Tous les articles tagués ' . $tagName,
        'canonical'   => $config['site_url'] . '/tag/' . $tagSlug . '/',
    ];
    $bodyClass = 'archive tag';

    require THEME_PATH . '/home.php';
    exit;
}

// ── 8. Single post, archive géographique ou page ──────────────────────────────
if (preg_match('#^/((?:[a-z0-9_][a-z0-9_-]*)(?:/[a-z0-9_][a-z0-9_-]*)*)/?$#', $requestUri, $m)) {
    $slug = $m[1];

    // Trailing-slash redirect
    if (!str_ends_with($requestUri, '/')) {
        header('Location: /' . $slug . '/', true, 301);
        exit;
    }

    // 1. Post slug exact (rétrocompat slugs multi-segments)
    $post = $content->getPost($slug);
    if ($post && $post['status'] === 'publish') {
        $seoData = [
            'type'         => 'post',
            'title'        => $post['title'],
            'description'  => $post['seo_description'],
            'canonical'    => $post['link'],
            'image'        => $post['featured_image_url'],
            'image_alt'    => $post['featured_image_alt'],
            'date'         => $post['date'],
            'modified'     => $post['modified'],
            'author'       => $post['author'],
            'categories'   => $post['categories'],
            'tags'         => $post['tags'],
            'translations' => $post['translations'] ?? [],
        ];
        $bodyClass   = 'single post';
        $currentSlug = $slug;
        require THEME_PATH . '/single.php';
        exit;
    }

    // 2. Zone géographique (page hiérarchique avec enfants ou posts liés)
    $geoPage = $content->getPageByPath($slug);
    if ($geoPage && $geoPage['status'] === 'publish') {
        $childPages = $content->getChildPages($geoPage['id']);
        $localPosts = $content->getPosts(['location_page_id' => $geoPage['id'], 'per_page' => 999]);
        if (!empty($childPages) || !empty($localPosts)) {
            $archivePage  = $geoPage;
            $archiveTitle = $geoPage['title'];
            $seoData = [
                'type'        => 'website',
                'title'       => $geoPage['title'],
                'description' => $geoPage['seo_description'] ?: $geoPage['excerpt'],
                'canonical'   => $geoPage['link'],
                'image'       => $geoPage['featured_image_url'],
                'image_alt'   => $geoPage['featured_image_alt'],
            ];
            $bodyClass   = 'archive geographic';
            $currentSlug = $slug;
            require THEME_PATH . '/archive.php';
            exit;
        }
    }

    // 3. Artisan sous zone géographique
    $lastSlash = strrpos($slug, '/');
    if ($lastSlash !== false) {
        $pagePath    = substr($slug, 0, $lastSlash);
        $artisanSlug = substr($slug, $lastSlash + 1);
        $locationPage = $content->getPageByPath($pagePath);
        if ($locationPage && $locationPage['status'] === 'publish') {
            $artisan = $content->getPost($artisanSlug);
            if ($artisan && $artisan['status'] === 'publish') {
                $locId = (int)($artisan['meta']['location_page_id'] ?? 0);
                if ($locId === $locationPage['id'] || $locId === 0) {
                    $post = $artisan;
                    $seoData = [
                        'type'         => 'post',
                        'title'        => $post['title'],
                        'description'  => $post['seo_description'],
                        'canonical'    => $post['link'],
                        'image'        => $post['featured_image_url'],
                        'image_alt'    => $post['featured_image_alt'],
                        'date'         => $post['date'],
                        'modified'     => $post['modified'],
                        'author'       => $post['author'],
                        'categories'   => $post['categories'],
                        'tags'         => $post['tags'],
                        'translations' => $post['translations'] ?? [],
                    ];
                    $bodyClass   = 'single post';
                    $currentSlug = $slug;
                    require THEME_PATH . '/single.php';
                    exit;
                }
            }
        }
    }

    // 4. Page slug exact (rétrocompat pages existantes)
    $page = $content->getPage($slug);
    if ($page && $page['status'] === 'publish') {
        $seoData = [
            'type'         => 'page',
            'title'        => $page['title'],
            'description'  => $page['seo_description'],
            'canonical'    => $page['link'],
            'image'        => $page['featured_image_url'],
            'image_alt'    => $page['featured_image_alt'],
            'translations' => $page['translations'] ?? [],
        ];
        $bodyClass   = 'page';
        $currentSlug = $slug;
        require THEME_PATH . '/page.php';
        exit;
    }
}

// ── 9. 404 ────────────────────────────────────────────────────────────────────
http_response_code(404);
$seoData = [
    'type'        => 'website',
    'title'       => 'Page introuvable',
    'description' => 'La page demandée n\'existe pas.',
    'canonical'   => $config['site_url'] . '/',
];
$bodyClass = 'error-404';
require THEME_PATH . '/404.php';
