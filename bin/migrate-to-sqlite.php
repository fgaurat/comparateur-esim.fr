#!/usr/bin/env php
<?php
/**
 * Migration Markdown → SQLite
 *
 * Usage :
 *   php bin/migrate-to-sqlite.php
 *
 * Ce script importe tous les fichiers .md de content/posts/ et content/pages/
 * dans la base SQLite définie par config.php → 'sqlite_path'.
 * Les IDs existants dans content/ids.json sont préservés.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$config = require BASE_PATH . '/config.php';

require BASE_PATH . '/lib/Markdown.php';
require BASE_PATH . '/lib/HtmlToMarkdown.php';
require BASE_PATH . '/lib/ContentInterface.php';
require BASE_PATH . '/lib/Content.php';
require BASE_PATH . '/lib/ContentSQLite.php';

// ── Chemin de la base ─────────────────────────────────────────────────────────
$dbPath = $config['sqlite_path'] ?? BASE_PATH . '/content/db.sqlite';

if (file_exists($dbPath)) {
    fwrite(STDERR, "ATTENTION : La base de données existe déjà :\n  {$dbPath}\n");
    fwrite(STDERR, "Voulez-vous l'écraser ? (o/N) : ");
    $answer = trim(fgets(STDIN));
    if (strtolower($answer) !== 'o') {
        echo "Migration annulée.\n";
        exit(0);
    }
    unlink($dbPath);
    echo "Ancienne base supprimée.\n";
}

// ── Lecture du manifest ids.json ─────────────────────────────────────────────
$manifestPath = BASE_PATH . '/content/ids.json';
$manifest     = ['posts' => [], 'pages' => [], 'categories' => [], 'tags' => [], 'media' => []];
if (file_exists($manifestPath)) {
    $decoded = json_decode(file_get_contents($manifestPath), true);
    if (is_array($decoded)) {
        $manifest = array_merge($manifest, $decoded);
    }
}

// ── Chargement du contenu Markdown ────────────────────────────────────────────
// On force posts_per_page très grand pour tout récupérer
$config['posts_per_page'] = 99999;
$mdContent = new Content($config);

$allPosts = $mdContent->getPosts(['status' => 'any', 'per_page' => 99999]);
$allPages = $mdContent->getPages(['status' => 'any']);

// ── Initialisation SQLite ─────────────────────────────────────────────────────
$config['sqlite_path'] = $dbPath;
$sqlite = new ContentSQLite($config);

// On passe par PDO directement pour insérer avec les IDs existants
$pdo = (function () use ($dbPath) {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
})();

// ── Helpers ───────────────────────────────────────────────────────────────────

function resolveOrCreateCategory(PDO $pdo, string $name, array &$manifest): int
{
    $slug = Markdown::slugify($name);
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    $stmt->execute([$slug]);
    $existing = $stmt->fetchColumn();
    if ($existing !== false) return (int)$existing;

    // ID depuis manifest ou nouveau
    $id = isset($manifest['categories'][$slug])
        ? (int)$manifest['categories'][$slug]
        : nextManifestId($manifest, 'categories');

    $pdo->prepare('INSERT OR IGNORE INTO categories (id, slug, name) VALUES (?, ?, ?)')
        ->execute([$id, $slug, $name]);

    $manifest['categories'][$slug] = $id;
    return $id;
}

function resolveOrCreateTag(PDO $pdo, string $name, array &$manifest): int
{
    $slug = Markdown::slugify($name);
    $stmt = $pdo->prepare('SELECT id FROM tags WHERE slug = ?');
    $stmt->execute([$slug]);
    $existing = $stmt->fetchColumn();
    if ($existing !== false) return (int)$existing;

    $id = isset($manifest['tags'][$slug])
        ? (int)$manifest['tags'][$slug]
        : nextManifestId($manifest, 'tags');

    $pdo->prepare('INSERT OR IGNORE INTO tags (id, slug, name) VALUES (?, ?, ?)')
        ->execute([$id, $slug, $name]);

    $manifest['tags'][$slug] = $id;
    return $id;
}

function nextManifestId(array &$manifest, string $type): int
{
    $starts = ['posts' => 1, 'pages' => 1000, 'categories' => 2000, 'tags' => 3000, 'media' => 5000];
    $existing = array_values($manifest[$type] ?? []);
    return empty($existing) ? $starts[$type] : max($existing) + 1;
}

// ── Import posts ──────────────────────────────────────────────────────────────
echo "Import des posts...\n";
$insertPost = $pdo->prepare(
    'INSERT OR IGNORE INTO posts
     (id, slug, title, content_html, excerpt, date, modified, status, author,
      featured_image, featured_image_alt, seo_description, translations, meta)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$countPosts = 0;
foreach ($allPosts as $post) {
    $insertPost->execute([
        $post['id'],
        $post['slug'],
        $post['title'],
        $post['content'],
        $post['excerpt'],
        $post['date'],
        $post['modified'],
        $post['status'],
        $post['author'],
        $post['featured_image_url'],
        $post['featured_image_alt'],
        $post['seo_description'],
        json_encode($post['translations'] ?? []),
        json_encode($post['meta'] ?? []),
    ]);

    // Catégories
    foreach ($post['categories'] as $catName) {
        $catId = resolveOrCreateCategory($pdo, $catName, $manifest);
        $pdo->prepare('INSERT OR IGNORE INTO post_categories (post_id, category_id) VALUES (?, ?)')
            ->execute([$post['id'], $catId]);
    }

    // Tags
    foreach ($post['tags'] as $tagName) {
        $tagId = resolveOrCreateTag($pdo, $tagName, $manifest);
        $pdo->prepare('INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)')
            ->execute([$post['id'], $tagId]);
    }

    echo "  ✓ [{$post['id']}] {$post['slug']}\n";
    $countPosts++;
}

// ── Import pages ──────────────────────────────────────────────────────────────
echo "Import des pages...\n";
$insertPage = $pdo->prepare(
    'INSERT OR IGNORE INTO pages
     (id, slug, title, content_html, excerpt, date, modified, status, author,
      featured_image, featured_image_alt, seo_description, menu_order, meta)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);

$countPages = 0;
foreach ($allPages as $page) {
    $insertPage->execute([
        $page['id'],
        $page['slug'],
        $page['title'],
        $page['content'],
        $page['excerpt'],
        $page['date'],
        $page['modified'],
        $page['status'],
        $page['author'],
        $page['featured_image_url'],
        $page['featured_image_alt'],
        $page['seo_description'],
        $page['menu_order'],
        json_encode($page['meta'] ?? []),
    ]);

    echo "  ✓ [{$page['id']}] {$page['slug']}\n";
    $countPages++;
}

// ── Mise à jour des séquences ─────────────────────────────────────────────────
echo "Mise à jour des séquences d'IDs...\n";

$seqData = [
    'posts'      => ['table' => 'posts',      'start' => 1],
    'pages'      => ['table' => 'pages',      'start' => 1000],
    'categories' => ['table' => 'categories', 'start' => 2000],
    'tags'       => ['table' => 'tags',       'start' => 3000],
];

foreach ($seqData as $type => $info) {
    $maxId = $pdo->query("SELECT MAX(id) FROM {$info['table']}")->fetchColumn();
    $nextId = $maxId ? (int)$maxId + 1 : $info['start'];
    $pdo->prepare('UPDATE id_sequences SET next_id = ? WHERE type = ?')
        ->execute([$nextId, $type]);
    echo "  {$type} : next_id = {$nextId}\n";
}

// ── Résumé ────────────────────────────────────────────────────────────────────
echo "\nMigration terminée :\n";
echo "  Posts  : {$countPosts}\n";
echo "  Pages  : {$countPages}\n";
echo "  Base   : {$dbPath}\n";
echo "\nActivation : dans config.php, ajouter :\n";
echo "  'storage'     => 'sqlite',\n";
echo "  'sqlite_path' => BASE_PATH . '/content/db.sqlite',\n";
