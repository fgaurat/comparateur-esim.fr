<?php

/**
 * Convertit du HTML en GitHub Flavored Markdown
 * Spec : https://github.github.com/gfm/
 *
 * Supporte :
 *   - Headings, paragraphs, blockquotes, horizontal rules
 *   - Bold, italic, strikethrough (GFM), inline code, links, images
 *   - Fenced code blocks avec détection du langage
 *   - Unordered / ordered lists, imbrication
 *   - Task lists GFM : <input type="checkbox">
 *   - Tables GFM (pipe tables)
 *   - Éléments conteneur (div, section, figure, …)
 */
class HtmlToMarkdown
{
    public static function convert(string $html): string
    {
        if (trim($html) === '') return '';

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) return strip_tags($html);

        $md = self::convertChildren($body, []);
        return self::cleanUp($md);
    }

    // -------------------------------------------------------------------------
    // Parcours du DOM
    // -------------------------------------------------------------------------

    private static function convertChildren(DOMNode $parent, array $ctx): string
    {
        $out = '';
        foreach ($parent->childNodes as $child) {
            $out .= self::convertNode($child, $ctx);
        }
        return $out;
    }

    private static function convertNode(DOMNode $node, array $ctx): string
    {
        // Nœud texte
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = $node->nodeValue;
            if (!($ctx['inline'] ?? false)) {
                $text = trim($text);
                return $text !== '' ? $text : '';
            }
            // En contexte inline, on normalise les espaces multiples mais on conserve
            return preg_replace('/[ \t]+/', ' ', $text);
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) return '';

        $tag = strtolower($node->nodeName);

        return match (true) {
            // ── Titres ────────────────────────────────────────────────────
            in_array($tag, ['h1','h2','h3','h4','h5','h6'])
                => self::heading($node, $tag, $ctx),

            // ── Paragraphes ───────────────────────────────────────────────
            $tag === 'p'
                => self::paragraph($node, $ctx),

            // ── Blockquote ────────────────────────────────────────────────
            $tag === 'blockquote'
                => self::blockquote($node, $ctx),

            // ── Code block ────────────────────────────────────────────────
            $tag === 'pre'
                => self::codeBlock($node),

            // ── Règle horizontale ─────────────────────────────────────────
            $tag === 'hr'
                => "---\n\n",

            // ── Saut de ligne ─────────────────────────────────────────────
            $tag === 'br'
                => "\n",

            // ── Listes ────────────────────────────────────────────────────
            $tag === 'ul'
                => self::list($node, 'ul', $ctx) . "\n",
            $tag === 'ol'
                => self::list($node, 'ol', $ctx) . "\n",

            // ── Table (GFM) ───────────────────────────────────────────────
            $tag === 'table'
                => self::table($node) . "\n",

            // ── Éléments inline ───────────────────────────────────────────
            in_array($tag, ['strong', 'b'])
                => self::wrap($node, '**', $ctx),
            in_array($tag, ['em', 'i'])
                => self::wrap($node, '*', $ctx),
            in_array($tag, ['del', 's', 'strike'])
                => self::wrap($node, '~~', $ctx),
            $tag === 'code' && (!$node->parentNode || strtolower($node->parentNode->nodeName) !== 'pre')
                => self::inlineCode($node),
            $tag === 'a'
                => self::link($node, $ctx),
            $tag === 'img'
                => self::img($node),
            $tag === 'sup'
                => '^' . trim(self::convertChildren($node, ['inline' => true])) . '^',
            $tag === 'sub'
                => '~' . trim(self::convertChildren($node, ['inline' => true])) . '~',
            $tag === 'mark'
                => '==' . trim(self::convertChildren($node, ['inline' => true])) . '==',
            in_array($tag, ['kbd', 'var', 'samp'])
                => '`' . $node->textContent . '`',

            // ── Éléments conteneurs transparents ──────────────────────────
            in_array($tag, ['span','label','abbr','cite','q','small','u','ins','time','bdo','bdi'])
                => self::convertChildren($node, array_merge($ctx, ['inline' => true])),

            // ── Légende de figure ─────────────────────────────────────────
            $tag === 'figcaption'
                => '*' . trim(self::convertChildren($node, ['inline' => true])) . "*\n\n",

            // ── Éléments conteneurs bloc ──────────────────────────────────
            in_array($tag, ['div','section','article','header','footer','main','aside','nav','figure','details','summary'])
                => self::blockContainer($node, $ctx),

            // ── Éléments à ignorer ────────────────────────────────────────
            in_array($tag, ['script','style','head','meta','link','noscript','template','svg'])
                => '',

            // ── Fallback : on traverse ────────────────────────────────────
            default
                => self::convertChildren($node, $ctx),
        };
    }

    // -------------------------------------------------------------------------
    // Blocs
    // -------------------------------------------------------------------------

    private static function heading(DOMNode $node, string $tag, array $ctx): string
    {
        $level = (int)substr($tag, 1);
        $inner = trim(self::convertChildren($node, ['inline' => true]));
        if ($inner === '') return '';
        return str_repeat('#', $level) . ' ' . $inner . "\n\n";
    }

    private static function paragraph(DOMNode $node, array $ctx): string
    {
        $inner = trim(self::convertChildren($node, ['inline' => true]));
        return $inner !== '' ? $inner . "\n\n" : '';
    }

    private static function blockquote(DOMNode $node, array $ctx): string
    {
        $inner = trim(self::convertChildren($node, $ctx));
        if ($inner === '') return '';
        $lines = explode("\n", $inner);
        $quoted = implode("\n", array_map(fn($l) => '> ' . $l, $lines));
        return $quoted . "\n\n";
    }

    private static function codeBlock(DOMNode $node): string
    {
        // Cherche <code> enfant pour le langage
        $codeNode = null;
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && strtolower($child->nodeName) === 'code') {
                $codeNode = $child;
                break;
            }
        }

        $lang = '';
        if ($codeNode) {
            $class = $codeNode->getAttribute('class') ?? '';
            if (preg_match('/(?:language|lang)-(\w+)/', $class, $m)) {
                $lang = $m[1];
            }
            $code = $codeNode->textContent;
        } else {
            $code = $node->textContent;
        }

        // Utilise assez de backticks si le contenu en contient
        $fence = preg_match('/```/', $code) ? '````' : '```';
        return $fence . $lang . "\n" . rtrim($code) . "\n" . $fence . "\n\n";
    }

    private static function blockContainer(DOMNode $node, array $ctx): string
    {
        $inner = self::convertChildren($node, $ctx);
        if (trim($inner) === '') return '';
        // Assure une séparation de bloc
        $inner = rtrim($inner);
        return $inner . "\n\n";
    }

    // -------------------------------------------------------------------------
    // Listes
    // -------------------------------------------------------------------------

    private static function list(DOMNode $node, string $type, array $ctx): string
    {
        $depth   = $ctx['list_depth'] ?? 0;
        $indent  = str_repeat('  ', $depth);
        $counter = 1;
        $result  = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            if (strtolower($child->nodeName) !== 'li') continue;

            $marker = $type === 'ul' ? '-' : ($counter . '.');

            // Détecte task list checkbox
            $checkbox = '';
            foreach ($child->childNodes as $liChild) {
                if ($liChild->nodeType === XML_ELEMENT_NODE
                    && strtolower($liChild->nodeName) === 'input'
                    && strtolower((string)$liChild->getAttribute('type')) === 'checkbox') {
                    $checkbox = $liChild->hasAttribute('checked') ? '[x] ' : '[ ] ';
                    break;
                }
            }

            // Sépare contenu inline et listes imbriquées
            $inlineContent = '';
            $nestedLists   = '';

            foreach ($child->childNodes as $liChild) {
                if ($liChild->nodeType === XML_ELEMENT_NODE) {
                    $liTag = strtolower($liChild->nodeName);
                    if (in_array($liTag, ['ul', 'ol'])) {
                        $nestedLists .= "\n" . rtrim(self::list($liChild, $liTag, array_merge($ctx, ['list_depth' => $depth + 1])));
                        continue;
                    }
                    if ($liTag === 'input' && strtolower((string)$liChild->getAttribute('type')) === 'checkbox') {
                        continue; // déjà géré
                    }
                }
                $inlineContent .= $liChild->nodeType === XML_TEXT_NODE
                    ? $liChild->nodeValue
                    : self::convertNode($liChild, ['inline' => true]);
            }

            $inlineContent = trim($inlineContent);
            $result .= $indent . $marker . ' ' . $checkbox . $inlineContent . $nestedLists . "\n";
            $counter++;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Tables (GFM pipe tables)
    // -------------------------------------------------------------------------

    private static function table(DOMNode $node): string
    {
        $rows = [];
        $headerFlag = false;
        self::collectRows($node, $rows, $headerFlag);

        if (empty($rows)) return '';

        $colCount = max(array_map(fn($r) => count($r['cells']), $rows));

        // Compléter les lignes courtes
        foreach ($rows as &$row) {
            while (count($row['cells']) < $colCount) {
                $row['cells'][] = '';
            }
        }
        unset($row);

        $lines       = [];
        $sepInserted = false;

        foreach ($rows as $row) {
            $cells = array_map(
                fn($c) => str_replace('|', '\\|', trim($c)),
                $row['cells']
            );
            $lines[] = '| ' . implode(' | ', $cells) . ' |';

            // Séparateur après la première ligne d'en-tête (ou après la première ligne si pas de thead)
            if (!$sepInserted && ($row['is_header'] || count($lines) === 1)) {
                $sep = array_fill(0, $colCount, '--------');
                $lines[] = '| ' . implode(' | ', $sep) . ' |';
                $sepInserted = true;
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private static function collectRows(DOMNode $node, array &$rows, bool &$inHeader): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) continue;
            $tag = strtolower($child->nodeName);

            if ($tag === 'thead')      { $inHeader = true;  self::collectRows($child, $rows, $inHeader); $inHeader = false; }
            elseif ($tag === 'tbody')  { $inHeader = false; self::collectRows($child, $rows, $inHeader); }
            elseif ($tag === 'tfoot')  { $inHeader = false; self::collectRows($child, $rows, $inHeader); }
            elseif ($tag === 'tr') {
                $cells    = [];
                $isHeader = $inHeader;
                foreach ($child->childNodes as $cell) {
                    if ($cell->nodeType !== XML_ELEMENT_NODE) continue;
                    $ct = strtolower($cell->nodeName);
                    if (in_array($ct, ['td', 'th'])) {
                        if ($ct === 'th') $isHeader = true;
                        $cells[] = trim(self::convertChildren($cell, ['inline' => true]));
                    }
                }
                if (!empty($cells)) {
                    $rows[] = ['cells' => $cells, 'is_header' => $isHeader];
                }
            } else {
                self::collectRows($child, $rows, $inHeader);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Inline
    // -------------------------------------------------------------------------

    private static function wrap(DOMNode $node, string $marker, array $ctx): string
    {
        $inner = trim(self::convertChildren($node, array_merge($ctx, ['inline' => true])));
        return $inner !== '' ? $marker . $inner . $marker : '';
    }

    private static function inlineCode(DOMNode $node): string
    {
        $text = $node->textContent;
        // Choisit le bon nombre de backticks
        if (str_contains($text, '`')) {
            $max = 0;
            preg_match_all('/`+/', $text, $m);
            foreach ($m[0] as $run) $max = max($max, strlen($run));
            $ticks = str_repeat('`', $max + 1);
            return $ticks . ' ' . $text . ' ' . $ticks;
        }
        return '`' . $text . '`';
    }

    private static function link(DOMNode $node, array $ctx): string
    {
        $href  = $node->getAttribute('href') ?? '';
        $title = $node->getAttribute('title') ?? '';
        $inner = trim(self::convertChildren($node, array_merge($ctx, ['inline' => true])));

        if ($inner === '') $inner = $href;
        if ($href === '')  return $inner;

        $titlePart = $title !== '' ? ' "' . str_replace('"', '\\"', $title) . '"' : '';
        return '[' . $inner . '](' . $href . $titlePart . ')';
    }

    private static function img(DOMNode $node): string
    {
        $src   = $node->getAttribute('src')   ?? '';
        $alt   = $node->getAttribute('alt')   ?? '';
        $title = $node->getAttribute('title') ?? '';

        $titlePart = $title !== '' ? ' "' . str_replace('"', '\\"', $title) . '"' : '';
        return '![' . $alt . '](' . $src . $titlePart . ')';
    }

    // -------------------------------------------------------------------------
    // Nettoyage final
    // -------------------------------------------------------------------------

    private static function cleanUp(string $md): string
    {
        // Plus de 2 sauts de ligne consécutifs → 2
        $md = preg_replace('/\n{3,}/', "\n\n", $md);
        return trim($md);
    }
}
