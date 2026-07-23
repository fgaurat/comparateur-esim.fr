<?php
declare(strict_types=1);

class BotDetector
{
    private const DEFAULT_PATTERNS = [
        'Googlebot'   => 'Googlebot',
        'Bingbot'     => 'bingbot',
        'Yandex'      => 'YandexBot',
        'Baidu'       => 'Baiduspider',
        'DuckDuckBot' => 'DuckDuckBot',
        'Semrush'     => 'SemrushBot',
        'Ahrefs'      => 'AhrefsBot',
        'MJ12bot'     => 'MJ12bot',
        'Dotbot'      => 'dotbot',
        'Rogerbot'    => 'rogerbot',
        'Exabot'      => 'Exabot',
        'Applebot'    => 'Applebot',
        'FacebookBot' => 'facebookexternalhit',
        'Twitterbot'  => 'Twitterbot',
        'LinkedInBot' => 'LinkedInBot',
        'Pinterest'   => 'Pinterest',
        'Slurp'       => 'Slurp',
        'ia_archiver' => 'ia_archiver',
        'DataForSeo'  => 'DataForSeoBot',
        'Serpstat'    => 'SerpstatBot',
        'BLEXBot'     => 'BLEXBot',
        'Sogou'       => 'Sogou',
        'Bytespider'  => 'Bytespider',
        'GPTBot'      => 'GPTBot',
        'ClaudeBot'   => 'ClaudeBot',
        'Anthropic'   => 'anthropic-ai',
    ];

    private array $patterns;

    public function __construct(array $config = [])
    {
        $this->patterns = array_merge(self::DEFAULT_PATTERNS, $config['bot_patterns'] ?? []);
    }

    /**
     * Retourne le nom canonique du bot ou null si humain.
     * Pour Googlebot, effectue une double vérification DNS (rDNS + forward).
     */
    public function detect(string $ua, string $ip): ?string
    {
        if ($ua === '') return null;

        foreach ($this->patterns as $botName => $pattern) {
            if (!preg_match('/' . $pattern . '/i', $ua)) continue;

            if ($botName === 'Googlebot') {
                return $this->verifyGooglebot($ip) ? 'Googlebot' : null;
            }

            return $botName;
        }

        return null;
    }

    public function isBot(string $ua, string $ip): bool
    {
        return $this->detect($ua, $ip) !== null;
    }

    /**
     * Vérification officielle Googlebot (double résolution DNS) :
     * 1. rDNS : IP → hostname (doit terminer par .googlebot.com ou .google.com)
     * 2. fDNS : hostname → IP (doit correspondre à l'IP source)
     * Ref: https://developers.google.com/search/docs/crawling-indexing/verifying-googlebot
     */
    private function verifyGooglebot(string $ip): bool
    {
        if ($ip === '') return false;

        $hostname = @gethostbyaddr($ip);
        if ($hostname === false || $hostname === $ip) return false;

        $host = strtolower($hostname);
        if (!str_ends_with($host, '.googlebot.com') && !str_ends_with($host, '.google.com')) {
            return false;
        }

        $resolved = @gethostbyname($hostname);
        return $resolved !== $hostname && $resolved === $ip;
    }
}
