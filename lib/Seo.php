<?php

class Seo
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function metaTags(array $data): string
    {
        $siteUrl  = $this->config['site_url'];
        $siteName = htmlspecialchars($this->config['site_name']);
        $type     = $data['type'] ?? 'website';
        $isPost   = in_array($type, ['post', 'page']);

        $title       = htmlspecialchars($data['title'] ?? $this->config['site_name']);
        $fullTitle   = $isPost ? $title . ' — ' . $siteName : $siteName;
        $description = htmlspecialchars($data['description'] ?? $this->config['site_description']);
        $canonical   = htmlspecialchars($data['canonical'] ?? $siteUrl . '/');
        $ogType      = $type === 'post' ? 'article' : 'website';
        $image       = $data['image'] ?? '';
        $imageAlt    = htmlspecialchars($data['image_alt'] ?? $fullTitle);

        $html  = "    <title>{$fullTitle}</title>\n";
        $html .= "    <meta name=\"description\" content=\"{$description}\">\n";
        $html .= "    <link rel=\"canonical\" href=\"{$canonical}\">\n";
        $html .= "    <meta name=\"robots\" content=\"index, follow\">\n";

        // Open Graph
        $html .= "    <meta property=\"og:type\" content=\"{$ogType}\">\n";
        $html .= "    <meta property=\"og:title\" content=\"{$fullTitle}\">\n";
        $html .= "    <meta property=\"og:description\" content=\"{$description}\">\n";
        $html .= "    <meta property=\"og:url\" content=\"{$canonical}\">\n";
        $html .= "    <meta property=\"og:site_name\" content=\"{$siteName}\">\n";
        $html .= "    <meta property=\"og:locale\" content=\"" . str_replace('-', '_', $this->config['language']) . "\">\n";

        if ($image) {
            $fullImg = str_starts_with($image, 'http') ? $image : $siteUrl . $image;
            $html .= "    <meta property=\"og:image\" content=\"" . htmlspecialchars($fullImg) . "\">\n";
            $html .= "    <meta property=\"og:image:alt\" content=\"{$imageAlt}\">\n";
        }

        // Article specific
        if ($type === 'post') {
            if (!empty($data['date'])) {
                $html .= "    <meta property=\"article:published_time\" content=\"" . htmlspecialchars($data['date']) . "\">\n";
                $html .= "    <meta property=\"article:modified_time\" content=\"" . htmlspecialchars($data['modified'] ?? $data['date']) . "\">\n";
            }
            if (!empty($data['author'])) {
                $html .= "    <meta property=\"article:author\" content=\"" . htmlspecialchars($data['author']) . "\">\n";
            }
            foreach (($data['tags'] ?? []) as $tag) {
                $html .= "    <meta property=\"article:tag\" content=\"" . htmlspecialchars($tag) . "\">\n";
            }
        }

        // Twitter Card
        $html .= "    <meta name=\"twitter:card\" content=\"" . ($image ? 'summary_large_image' : 'summary') . "\">\n";
        $html .= "    <meta name=\"twitter:title\" content=\"{$fullTitle}\">\n";
        $html .= "    <meta name=\"twitter:description\" content=\"{$description}\">\n";
        if ($image) {
            $fullImg = str_starts_with($image, 'http') ? $image : $siteUrl . $image;
            $html .= "    <meta name=\"twitter:image\" content=\"" . htmlspecialchars($fullImg) . "\">\n";
        }

        return $html;
    }

    public function jsonLd(array $data): string
    {
        $siteUrl  = $this->config['site_url'];
        $siteName = $this->config['site_name'];
        $type     = $data['type'] ?? 'website';

        if ($type === 'post') {
            $schema = [
                '@context'         => 'https://schema.org',
                '@type'            => 'BlogPosting',
                'headline'         => $data['title'] ?? '',
                'description'      => $data['description'] ?? '',
                'url'              => $data['canonical'] ?? $siteUrl,
                'datePublished'    => $data['date'] ?? '',
                'dateModified'     => $data['modified'] ?? $data['date'] ?? '',
                'author'           => [
                    '@type' => 'Person',
                    'name'  => $data['author'] ?? $this->config['author'],
                ],
                'publisher'        => [
                    '@type' => 'Organization',
                    'name'  => $siteName,
                    'url'   => $siteUrl,
                ],
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id'   => $data['canonical'] ?? $siteUrl,
                ],
                'inLanguage'       => $this->config['language'],
            ];
            if (!empty($data['image'])) {
                $fullImg = str_starts_with($data['image'], 'http') ? $data['image'] : $siteUrl . $data['image'];
                $schema['image'] = $fullImg;
            }
            if (!empty($data['categories'])) {
                $schema['articleSection'] = implode(', ', $data['categories']);
            }
            if (!empty($data['tags'])) {
                $schema['keywords'] = implode(', ', $data['tags']);
            }

        } elseif ($type === 'page') {
            $schema = [
                '@context'    => 'https://schema.org',
                '@type'       => 'WebPage',
                'name'        => $data['title'] ?? '',
                'description' => $data['description'] ?? '',
                'url'         => $data['canonical'] ?? $siteUrl,
                'inLanguage'  => $this->config['language'],
            ];
        } else {
            $schema = [
                '@context'    => 'https://schema.org',
                '@type'       => 'WebSite',
                'name'        => $siteName,
                'description' => $this->config['site_description'],
                'url'         => $siteUrl,
                'inLanguage'  => $this->config['language'],
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => $siteUrl . '/?s={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ];
        }

        return '    <script type="application/ld+json">' . "\n"
             . '    ' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
             . "\n    </script>\n";
    }

    public function hreflangLinks(array $seoData): string
    {
        $languages = $this->config['languages'] ?? [];
        if (empty($languages)) return '';

        $currentLang  = $this->config['language'];
        $currentUrl   = $seoData['canonical'] ?? ($this->config['site_url'] . '/');
        $translations = $seoData['translations'] ?? [];

        $html  = "    <link rel=\"alternate\" hreflang=\"" . htmlspecialchars($currentLang)
               . "\" href=\"" . htmlspecialchars($currentUrl) . "\">\n";

        foreach ($languages as $langCode => $langConfig) {
            $base    = rtrim($langConfig['url'], '/');
            $langIso = $langConfig['code'] ?? $langCode;
            $url = isset($translations[$langCode])
                ? $base . '/' . $translations[$langCode] . '/'
                : $base . '/';
            $html .= "    <link rel=\"alternate\" hreflang=\"" . htmlspecialchars($langIso)
                   . "\" href=\"" . htmlspecialchars($url) . "\">\n";
        }

        if (!empty($this->config['hreflang_default'])) {
            $html .= "    <link rel=\"alternate\" hreflang=\"x-default\" href=\""
                   . htmlspecialchars($currentUrl) . "\">\n";
        }

        return $html;
    }

    public function sitemap(ContentInterface $content): string
    {
        $siteUrl   = $this->config['site_url'];
        $languages = $this->config['languages'] ?? [];
        $hasAlts   = !empty($languages);
        $urls      = [];

        $urls[] = ['loc' => $siteUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily', 'translations' => []];

        foreach ($content->getPosts(['per_page' => 9999]) as $post) {
            $urls[] = [
                'loc'          => $post['link'],
                'lastmod'      => substr($post['modified'], 0, 10),
                'priority'     => '0.8',
                'changefreq'   => 'weekly',
                'translations' => $post['translations'] ?? [],
            ];
        }

        foreach ($content->getPages() as $page) {
            $urls[] = [
                'loc'          => $page['link'],
                'lastmod'      => substr($page['modified'], 0, 10),
                'priority'     => '0.7',
                'changefreq'   => 'monthly',
                'translations' => $page['translations'] ?? [],
            ];
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
        if ($hasAlts) {
            $xml .= "\n        xmlns:xhtml=\"http://www.w3.org/1999/xhtml\"";
        }
        $xml .= '>' . "\n";

        $currentLang = $this->config['language'];

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            if (!empty($url['lastmod']))    $xml .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
            if (!empty($url['changefreq'])) $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            if (!empty($url['priority']))   $xml .= "    <priority>{$url['priority']}</priority>\n";

            if ($hasAlts) {
                $translations = $url['translations'] ?? [];
                $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"" . htmlspecialchars($currentLang)
                      . "\" href=\"" . htmlspecialchars($url['loc']) . "\"/>\n";
                foreach ($languages as $langCode => $langConfig) {
                    $base    = rtrim($langConfig['url'], '/');
                    $langIso = $langConfig['code'] ?? $langCode;
                    $altUrl  = isset($translations[$langCode])
                        ? $base . '/' . $translations[$langCode] . '/'
                        : $base . '/';
                    $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"" . htmlspecialchars($langIso)
                          . "\" href=\"" . htmlspecialchars($altUrl) . "\"/>\n";
                }
            }

            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';
        return $xml;
    }

    public function robotsTxt(): string
    {
        $siteUrl = $this->config['site_url'];
        return implode("\n", [
            'User-agent: *',
            'Disallow: /wp-admin/',
            'Allow: /wp-admin/admin-ajax.php',
            '',
            'Sitemap: ' . $siteUrl . '/sitemap.xml',
            '',
        ]);
    }

    public function rssFeed(ContentInterface $content): string
    {
        $siteUrl  = $this->config['site_url'];
        $siteName = htmlspecialchars($this->config['site_name']);
        $siteDesc = htmlspecialchars($this->config['site_description']);
        $now      = date('r');

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . "\n";
        $xml .= "<channel>\n";
        $xml .= "  <title>{$siteName}</title>\n";
        $xml .= "  <link>{$siteUrl}</link>\n";
        $xml .= "  <description>{$siteDesc}</description>\n";
        $xml .= "  <language>" . htmlspecialchars($this->config['language']) . "</language>\n";
        $xml .= "  <lastBuildDate>{$now}</lastBuildDate>\n";
        $xml .= "  <atom:link href=\"" . htmlspecialchars($siteUrl . '/feed/') . "\" rel=\"self\" type=\"application/rss+xml\"/>\n";
        $xml .= "  <generator>https://wordpress.org/?v=6.7.2</generator>\n";

        foreach ($content->getPosts(['per_page' => 20]) as $post) {
            $title   = htmlspecialchars($post['title']);
            $link    = htmlspecialchars($post['link']);
            $excerpt = htmlspecialchars($post['excerpt']);
            $date    = date('r', strtotime($post['date']));

            $xml .= "  <item>\n";
            $xml .= "    <title>{$title}</title>\n";
            $xml .= "    <link>{$link}</link>\n";
            $xml .= "    <description>{$excerpt}</description>\n";
            $xml .= "    <content:encoded><![CDATA[{$post['content']}]]></content:encoded>\n";
            $xml .= "    <pubDate>{$date}</pubDate>\n";
            $xml .= "    <guid isPermaLink=\"true\">{$link}</guid>\n";
            $xml .= "  </item>\n";
        }

        $xml .= "</channel>\n</rss>";
        return $xml;
    }
}
