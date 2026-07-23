<?php

class Markdown
{
    /**
     * Parse un fichier .md et retourne [frontmatter array, html string]
     */
    public static function parseFile(string $path): array
    {
        if (!file_exists($path)) {
            return [[], ''];
        }
        $raw = file_get_contents($path);
        return self::parse($raw);
    }

    /**
     * Parse une chaîne markdown et retourne [frontmatter array, html string]
     */
    public static function parse(string $raw): array
    {
        $frontmatter = [];
        $body = $raw;

        if (str_starts_with(ltrim($raw), '---')) {
            if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)/s', ltrim($raw), $m)) {
                $frontmatter = self::parseYaml($m[1]);
                $body = $m[2];
            }
        }

        $html = self::toHtml($body);
        return [$frontmatter, $html];
    }

    /**
     * Parse YAML simple (clé: valeur, listes avec tirets)
     */
    private static function parseYaml(string $yaml): array
    {
        $result = [];
        $lines = explode("\n", $yaml);
        $currentKey = null;
        $inList = false;

        foreach ($lines as $line) {
            if ($inList && preg_match('/^\s+-\s+(.+)$/', $line, $m)) {
                $result[$currentKey][] = trim($m[1]);
                continue;
            }

            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*):\s*(.*)$/', $line, $m)) {
                $key   = $m[1];
                $value = trim($m[2]);
                $inList = false;
                $currentKey = $key;

                if ($value === '') {
                    $result[$key] = [];
                    $inList = true;
                } elseif (preg_match('/^\[(.+)\]$/', $value, $lm)) {
                    $result[$key] = array_map('trim', explode(',', $lm[1]));
                } else {
                    $result[$key] = trim($value, '"\'');
                }
            }
        }

        return $result;
    }

    /**
     * Convertit du Markdown en HTML
     */
    public static function toHtml(string $text): string
    {
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        // Code blocks (``` ... ```)
        $codeBlocks = [];
        $text = preg_replace_callback('/```(\w*)\n(.*?)```/s', function ($m) use (&$codeBlocks) {
            $lang  = $m[1] ? ' class="language-' . htmlspecialchars($m[1]) . '"' : '';
            $code  = htmlspecialchars($m[2]);
            $token = '<!--CODE' . count($codeBlocks) . '-->';
            $codeBlocks[$token] = '<pre><code' . $lang . '>' . $code . '</code></pre>';
            return $token;
        }, $text);

        // Inline code
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) {
            return '<code>' . htmlspecialchars($m[1]) . '</code>';
        }, $text);

        // Blockquotes
        $text = preg_replace_callback('/(?:^> .+\n?)+/m', function ($m) {
            $content = preg_replace('/^> /m', '', $m[0]);
            return '<blockquote>' . trim(self::toHtml($content)) . '</blockquote>' . "\n";
        }, $text);

        // ATX Headers
        $text = preg_replace_callback('/^(#{1,6})\s+(.+)$/m', function ($m) {
            $level = strlen($m[1]);
            $content = self::parseInline(trim($m[2]));
            $id = self::slugify(strip_tags($m[2]));
            return "<h{$level} id=\"{$id}\">{$content}</h{$level}>";
        }, $text);

        // Horizontal rules
        $text = preg_replace('/^(?:---|\*\*\*|___)[ \t]*$/m', '<hr>', $text);

        // Lists (unordered + ordered, avec nesting)
        $text = preg_replace_callback(
            '/(?:^(?:[-*+]|\d+\.)\s.+\n?(?:[ \t]{2,}[^\n]+\n?)*)+/m',
            fn($m) => self::renderList(rtrim($m[0])) . "\n",
            $text
        );

        // Tables (pipe syntax)
        $text = preg_replace_callback('/(?:^\|.+\|\n?)+/m', function ($m) {
            $lines = array_filter(explode("\n", trim($m[0])));
            $html = '<table>';
            $isHeader = true;
            foreach ($lines as $line) {
                if (preg_match('/^\|[-| :]+\|$/', $line)) { $isHeader = false; continue; }
                $cells = array_slice(explode('|', $line), 1, -1);
                $tag = $isHeader ? 'th' : 'td';
                $html .= '<tr>';
                foreach ($cells as $cell) {
                    $html .= "<{$tag}>" . self::parseInline(trim($cell)) . "</{$tag}>";
                }
                $html .= '</tr>';
                if ($isHeader) $isHeader = false;
            }
            $html .= '</table>';
            return $html . "\n";
        }, $text);

        // Paragraphs
        $blocks = preg_split('/\n{2,}/', trim($text));
        $output = '';
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') continue;
            if (preg_match('/^<(h[1-6]|ul|ol|li|blockquote|pre|hr|table)/i', $block)) {
                $output .= $block . "\n";
            } elseif (str_starts_with($block, '<!--CODE')) {
                $output .= $block . "\n";
            } else {
                $output .= '<p>' . self::parseInline($block) . '</p>' . "\n";
            }
        }

        // Restore code blocks
        foreach ($codeBlocks as $token => $html) {
            $output = str_replace($token, $html, $output);
        }

        return $output;
    }

    private static function renderList(string $block): string
    {
        $lines = explode("\n", $block);

        $firstLine = '';
        foreach ($lines as $l) { if (trim($l) !== '') { $firstLine = $l; break; } }
        $ordered = (bool)preg_match('/^\d+\./', $firstLine);
        $tag = $ordered ? 'ol' : 'ul';

        $html = "<{$tag}>";
        $currentItemText = null;
        $subLines = [];

        $flushItem = function () use (&$html, &$currentItemText, &$subLines) {
            if ($currentItemText === null) return;
            $content = self::parseInline(trim($currentItemText));
            if (!empty($subLines)) {
                $subBlock = implode("\n", $subLines);
                if (preg_match('/^(?:[-*+]|\d+\.)\s/', $subBlock)) {
                    $content .= self::renderList($subBlock);
                } else {
                    $content .= ' ' . self::parseInline(trim($subBlock));
                }
            }
            $html .= "<li>{$content}</li>";
            $currentItemText = null;
            $subLines = [];
        };

        foreach ($lines as $line) {
            if (preg_match('/^(?:[-*+]|\d+\.)\s(.+)$/', $line, $m)) {
                $flushItem();
                $currentItemText = $m[1];
            } elseif ($currentItemText !== null && preg_match('/^ {2}(.*)$/', $line, $m)) {
                $subLines[] = $m[1];
            }
        }
        $flushItem();

        $html .= "</{$tag}>";
        return $html;
    }

    private static function parseInline(string $text): string
    {
        // Backslash escaping
        $text = preg_replace_callback(
            '/\\\\([\\\\`*_{}[\]()#+\-.!|])/',
            fn($m) => '&#' . ord($m[1]) . ';',
            $text
        );

        // Images before links
        $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($m) {
            $alt = htmlspecialchars($m[1]);
            $src = htmlspecialchars($m[2]);
            return '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">';
        }, $text);

        // Links
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
            $label = htmlspecialchars($m[1]);
            $href  = htmlspecialchars($m[2]);
            $ext   = str_starts_with($href, 'http') ? ' rel="noopener" target="_blank"' : '';
            return '<a href="' . $href . '"' . $ext . '>' . $label . '</a>';
        }, $text);

        // Bold + italic
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);

        // Strikethrough
        $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);

        // Autolinks (URLs nues)
        $text = preg_replace_callback(
            '/(?<!["\'=>(])(https?:\/\/[^\s<>")\]]+)/',
            function ($m) {
                $url = htmlspecialchars($m[0]);
                return '<a href="' . $url . '" rel="noopener" target="_blank">' . $url . '</a>';
            },
            $text
        );

        // Line breaks
        $text = preg_replace('/  \n/', '<br>', $text);

        return $text;
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        // Translittération des caractères accentués → ASCII
        $map = [
            'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'ae',
            'ç'=>'c',
            'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
            'ð'=>'d','ñ'=>'n',
            'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o','œ'=>'oe',
            'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
            'ý'=>'y','ÿ'=>'y',
            'ß'=>'ss','þ'=>'th',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/\s+/', '-', trim($text));
        return preg_replace('/-+/', '-', $text) ?: 'section';
    }

    /**
     * Extrait les N premiers mots comme excerpt
     */
    public static function excerpt(string $html, int $words = 30): string
    {
        $text = strip_tags($html);
        $wordArray = explode(' ', $text);
        if (count($wordArray) <= $words) return trim($text);
        return implode(' ', array_slice($wordArray, 0, $words)) . '…';
    }
}
