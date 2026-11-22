<?php

namespace App\Support;

/** Restricted HTML fragment renderer for Marketing SJR briefs.
 * HTML is rebuilt from an allowlist. All user attributes are discarded except
 * validated http(s) href and a strictly validated solid text color.
 */
final class SjrBriefHtml
{
    public static function clean(string $html): string
    {
        $html = preg_replace('~<(script|style|iframe|object|svg|math|template|noscript)\b[^>]*>.*?</\1\s*>~is', '', $html) ?? '';
        $tokens = preg_split('~(<[^>]*>)~s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $allowed = ['p', 'div', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h3', 'blockquote', 'pre', 'code', 'a', 'span', 'font', 's', 'strike'];
        $out = '';
        $stack = [];
        foreach ($tokens ?: [] as $token) {
            if ($token === '') {
                continue;
            }
            if ($token[0] !== '<') {
                $out .= htmlspecialchars(html_entity_decode($token, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                continue;
            }
            if (! preg_match('~^<\s*(/?)\s*([a-z][a-z0-9]*)\b([^>]*)>\s*$~is', $token, $m)) {
                continue;
            }
            $tag = strtolower($m[2]);
            if (! in_array($tag, $allowed, true)) {
                continue;
            }
            if ($m[1] === '/') {
                if ($tag !== 'br') {
                    for ($k = count($stack) - 1; $k >= 0; $k--) {
                        if ($stack[$k][0] !== $tag) {
                            continue;
                        }
                        while (count($stack) > $k) {
                            $out .= '</'.array_pop($stack)[1].'>';
                        }
                        break;
                    }
                }

                continue;
            }
            if ($tag === 'br') {
                $out .= '<br>';

                continue;
            }
            $attributes = $m[3];
            if ($tag === 'a') {
                $url = self::attribute($attributes, 'href');
                if ($url !== null && preg_match('~^https?://[^\s<>"\x00-\x1f]+$~i', $url) && strlen($url) <= 2048) {
                    $out .= '<a href="'.htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" target="_blank" rel="noopener noreferrer">';
                    $stack[] = ['a', 'a'];
                } else {
                    $out .= '<span>';
                    $stack[] = ['a', 'span']; // invalid links are never anchors
                }

                continue;
            }
            if ($tag === 'span' || $tag === 'font') {
                $color = self::attribute($attributes, 'color');
                $style = self::attribute($attributes, 'style') ?? '';
                if (preg_match('~(?:^|;)\s*color\s*:\s*(#[0-9a-f]{3,8}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\))~i', $style, $c)) {
                    $color = $c[1];
                }
                if ($color !== null && preg_match('~^#[a-f0-9]{3}([a-f0-9]{3})?$~i', $color)) {
                    $out .= '<span style="color:'.strtolower($color).'">';
                } elseif ($color !== null && preg_match('~^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$~i', $color, $c) && max((int) $c[1], (int) $c[2], (int) $c[3]) <= 255) {
                    $out .= '<span style="color:rgb('.(int) $c[1].','.(int) $c[2].','.(int) $c[3].')">';
                } else {
                    $out .= '<span>';
                }
                $stack[] = [$tag, 'span'];

                continue;
            }
            $out .= '<'.$tag.'>';
            $stack[] = [$tag, $tag];
        }
        while ($stack) {
            $out .= '</'.array_pop($stack)[1].'>';
        }

        // Normalize invalid mismatched user nesting in browsers' HTML parser when rendered;
        // only whitelisted tags, safe text, URL and color ever leave this method.
        return $out;
    }

    private static function attribute(string $raw, string $name): ?string
    {
        if (! preg_match('~(?:^|\s)'.preg_quote($name, '~').'\s*=\s*(?:"([^"]*)"|\x27([^\x27]*)\x27|([^\s>]+))~i', $raw, $m)) {
            return null;
        }

        return trim(html_entity_decode($m[1] !== '' ? $m[1] : (($m[2] ?? '') !== '' ? $m[2] : ($m[3] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
