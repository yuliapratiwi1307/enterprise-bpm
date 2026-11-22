<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class ProjectDescriptionRichText
{
    private const PREFIX = 'SMSRICH1:';

    public static function isRich(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    public static function html(?string $value): string
    {
        if (! self::isRich($value)) {
            return nl2br(e($value ?? ''), false);
        }
        $data = json_decode(substr($value, strlen(self::PREFIX)), true);
        if (! is_array($data) || ($data['v'] ?? null) !== 1 || ! is_string($data['html'] ?? null)) {
            return '';
        }

        return self::sanitize($data['html']);
    }

    public static function valid(?string $value): bool
    {
        if (! self::isRich($value)) {
            return mb_strlen($value ?? '') <= 10000;
        }
        $data = json_decode(substr($value, strlen(self::PREFIX)), true);
        if (! is_array($data) || ($data['v'] ?? null) !== 1 || ! is_string($data['html'] ?? null)) {
            return false;
        }
        $plain = html_entity_decode(strip_tags($data['html']), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_strlen($plain) <= 10000 && strlen($value) <= 10000;
    }

    private static function sanitize(string $source): string
    {
        if (! class_exists(DOMDocument::class)) {
            return nl2br(e(strip_tags($source)), false);
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        $prior = libxml_use_internal_errors(true);
        try {
            $doc->loadHTML('<?xml encoding="UTF-8"><div id="sms-rich-root">'.$source.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prior);
        }
        $root = $doc->getElementById('sms-rich-root');
        if (! $root) {
            return e(strip_tags($source));
        }
        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= self::node($child);
        }

        return $output;
    }

    private static function node(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return e($node->nodeValue ?? '');
        }
        if (! $node instanceof DOMElement) {
            return '';
        }
        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'svg', 'math', 'object', 'template', 'form'], true)) {
            return '';
        }
        if ($tag === 'br') {
            return '<br>';
        }
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= self::node($child);
        }
        $allowed = ['p', 'div', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'blockquote', 'h3', 'a'];
        if (! in_array($tag, $allowed, true)) {
            return $inner;
        }
        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            if (! preg_match('~^https://[^\s<>"\']+$~i', $href)) {
                return $inner;
            }

            return '<a href="'.e($href).'" rel="noopener noreferrer" target="_blank">'.$inner.'</a>';
        }

        return '<'.$tag.'>'.$inner.'</'.$tag.'>';
    }
}
