<?php
// Détection HTTPS compatible reverse proxy / Cloudflare / Caddy.
$host = $_SERVER['HTTP_HOST'] ?? null;
$forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
$cfVisitor = (string)($_SERVER['HTTP_CF_VISITOR'] ?? '');
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
    || str_contains($forwardedProto, 'https')
    || str_contains($cfVisitor, '"scheme":"https"')
    || ($host === 'comparateur-esim.fr')
    || ($host === 'www.comparateur-esim.fr');

$base = [
    'site_url'         => $host
        ? ($isHttps ? 'https' : 'http') . '://' . $host
        : 'http://localhost:8005',
    'site_name'        => 'Comparateur eSIM',
    'site_description' => 'Comparez les fournisseurs eSIM de voyage : prix, data, durée et couverture par destination.',
    'language'         => 'fr-FR',
    'author'           => 'Comparateur eSIM',
    'posts_per_page'   => 12,
    'media_url'        => '/media',
    'theme_color'      => '#19d58b',
    'theme'            => 'esim',
    'hero_title'       => 'Trouvez la meilleure eSIM pour votre voyage',
    'hero_subtitle'    => 'Comparez Saily, Airalo, GigSky, Holafly, Ubigi et eSIM4Travel par destination, data, durée et prix.',
    'hero_badge'       => 'Comparateur eSIM indépendant',
    'hero_stats'       => ['198+ destinations', '5+ fournisseurs', '26 000+ forfaits marché'],
    'register_url'     => '/destinations/',
    'register_label'   => 'Comparer les destinations',
    'contact_url'      => '/contact/',
    'contact_label'    => 'Contact',
    'languages' => [],
    'hreflang_default' => false,
    'users'   => [],
    'api_key' => null,
    'api_log' => true,
    'mailer'        => 'smtp',
    'brevo_api_key' => '',
    'bot_patterns' => [],
];
$localFile = __DIR__ . '/config.local.php';
if (file_exists($localFile)) {
    $local = require $localFile;
    $base  = array_merge($base, $local);
}
return $base;
