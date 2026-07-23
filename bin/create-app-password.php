#!/usr/bin/env php
<?php
/**
 * Génère un mot de passe d'application WordPress et affiche
 * le fragment à coller dans config.php.
 *
 * Usage :
 *   php bin/create-app-password.php <username> [<nom_application>]
 *
 * Exemples :
 *   php bin/create-app-password.php admin
 *   php bin/create-app-password.php admin "Mon Crawler SEO"
 */

// ── Arguments ─────────────────────────────────────────────────────────────────
if ($argc < 2) {
    fwrite(STDERR, "Usage : php bin/create-app-password.php <username> [<nom_application>]\n");
    exit(1);
}

$username = $argv[1];
$appName  = $argv[2] ?? 'Application';

// ── Génération du mot de passe ─────────────────────────────────────────────────
// 24 caractères alphanumériques minuscules (même charset que WordPress)
$charset  = 'abcdefghijklmnopqrstuvwxyz0123456789';
$charLen  = strlen($charset);
$raw      = '';
$bytes    = random_bytes(24);

for ($i = 0; $i < 24; $i++) {
    $raw .= $charset[ord($bytes[$i]) % $charLen];
}

// Format affiché : xxxx xxxx xxxx xxxx xxxx xxxx
$formatted = implode(' ', str_split($raw, 4));

// Hash bcrypt (PASSWORD_DEFAULT = bcrypt en PHP)
$hash = password_hash($raw, PASSWORD_DEFAULT);

// ── Affichage ──────────────────────────────────────────────────────────────────
echo "\n";
echo "✓ Mot de passe d'application généré\n";
echo "  Utilisateur : {$username}\n";
echo "  Application : {$appName}\n";
echo "\n";
echo "  Mot de passe (à copier maintenant, non récupérable) :\n";
echo "  \033[1;32m{$formatted}\033[0m\n";
echo "\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "  Fragment à ajouter dans config.php :\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "\n";
echo "  'users' => [\n";
echo "      '{$username}' => [\n";
echo "          'app_passwords' => [\n";
echo "              ['name' => " . var_export($appName, true) . ", 'hash' => '{$hash}'],\n";
echo "          ],\n";
echo "      ],\n";
echo "  ],\n";
echo "\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "  Utilisation curl :\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "\n";
echo "  curl -X POST http://localhost:8000/wp-json/wp/v2/posts \\\n";
echo "    -u \"{$username}:{$formatted}\" \\\n";
echo "    -H \"Content-Type: application/json\" \\\n";
echo "    -d '{\"title\":\"Test\",\"status\":\"draft\"}'\n";
echo "\n";
