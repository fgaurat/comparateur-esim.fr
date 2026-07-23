#!/usr/bin/env php
<?php
/**
 * Régénération du manifest content/ids.json
 *
 * Usage :
 *   php bin/rebuild-ids.php          # fusionne avec l'existant
 *   php bin/rebuild-ids.php --reset  # repart de zéro (nouveaux IDs)
 *
 * Sans --reset, les IDs déjà attribués sont conservés et seuls les
 * slugs manquants reçoivent un nouvel ID.
 * Avec --reset, le fichier ids.json est supprimé avant la reconstruction.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

$reset = in_array('--reset', $argv ?? [], true);

require BASE_PATH . '/lib/Markdown.php';

$manifestPath = BASE_PATH . '/content/ids.json';

// Plages de départ (doivent rester cohérentes avec Content.php)
$idStart = [
    'posts'      => 1,
    'pages'      => 1000,
    'categories' => 2000,
    'tags'       => 3000,
    'media'      => 5000,
];

// Chargement ou initialisation du manifest
if ($reset) {
    echo "Mode --reset : suppression de l'ancien ids.json\n";
    $manifest = ['posts' => [], 'pages' => [], 'categories' => [], 'tags' => [], 'media' => []];
} elseif (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true) ?? [];
    foreach (array_keys($idStart) as $type) {
        $manifest[$type] ??= [];
    }
} else {
    $manifest = ['posts' => [], 'pages' => [], 'categories' => [], 'tags' => [], 'media' => []];
}

function resolveId(array &$manifest, string $type, string $slug, array $idStart): int
{
    if (isset($manifest[$type][$slug])) {
        return (int)$manifest[$type][$slug];
    }
    $existing = array_values($manifest[$type]);
    $next = empty($existing) ? $idStart[$type] : max($existing) + 1;
    $manifest[$type][$slug] = $next;
    return $next;
}

$counts = ['posts' => 0, 'pages' => 0, 'categories' => 0, 'tags' => 0, 'media' => 0];

// ── Posts ─────────────────────────────────────────────────────────────────────
$postsDir = BASE_PATH . '/content/posts';
if (is_dir($postsDir)) {
    foreach (glob($postsDir . '/*.md') ?: [] as $file) {
        [$meta] = Markdown::parseFile($file);
        $slug = $meta['slug'] ?? pathinfo($file, PATHINFO_FILENAME);
        resolveId($manifest, 'posts', $slug, $idStart);
        $counts['posts']++;

        if (!empty($meta['featured_image'])) {
            $imgSlug = pathinfo($meta['featured_image'], PATHINFO_FILENAME);
            resolveId($manifest, 'media', $imgSlug, $idStart);
        }

        foreach ((array)($meta['categories'] ?? []) as $cat) {
            resolveId($manifest, 'categories', Markdown::slugify($cat), $idStart);
            $counts['categories']++;
        }
        foreach ((array)($meta['tags'] ?? []) as $tag) {
            resolveId($manifest, 'tags', Markdown::slugify($tag), $idStart);
            $counts['tags']++;
        }
    }
}

// ── Pages ─────────────────────────────────────────────────────────────────────
$pagesDir = BASE_PATH . '/content/pages';
if (is_dir($pagesDir)) {
    foreach (glob($pagesDir . '/*.md') ?: [] as $file) {
        [$meta] = Markdown::parseFile($file);
        $slug = $meta['slug'] ?? pathinfo($file, PATHINFO_FILENAME);
        resolveId($manifest, 'pages', $slug, $idStart);
        $counts['pages']++;

        if (!empty($meta['featured_image'])) {
            $imgSlug = pathinfo($meta['featured_image'], PATHINFO_FILENAME);
            resolveId($manifest, 'media', $imgSlug, $idStart);
        }
    }
}

// ── Médias (dossier media/) ───────────────────────────────────────────────────
$mediaDir = BASE_PATH . '/media';
if (is_dir($mediaDir)) {
    foreach (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'mp4', 'mp3'] as $ext) {
        foreach (glob($mediaDir . '/*.' . $ext) ?: [] as $file) {
            resolveId($manifest, 'media', pathinfo($file, PATHINFO_FILENAME), $idStart);
            $counts['media']++;
        }
    }
}

// ── Sauvegarde ────────────────────────────────────────────────────────────────
file_put_contents(
    $manifestPath,
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
);

echo "ids.json régénéré :\n";
echo "  posts      : " . count($manifest['posts'])      . " slugs (scannés : {$counts['posts']})\n";
echo "  pages      : " . count($manifest['pages'])      . " slugs (scannés : {$counts['pages']})\n";
echo "  categories : " . count($manifest['categories']) . " slugs\n";
echo "  tags       : " . count($manifest['tags'])       . " slugs\n";
echo "  media      : " . count($manifest['media'])      . " slugs\n";
