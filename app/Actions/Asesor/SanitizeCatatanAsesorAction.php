<?php

namespace App\Actions\Asesor;

use DOMDocument;
use DOMDocumentFragment;
use DOMNode;

class SanitizeCatatanAsesorAction
{
    private const ALLOWED_TAGS = [
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        'ol',
        'ul',
        'li',
    ];

    public function execute(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);
        if ($html === '') {
            return null;
        }

        $source = new DOMDocument('1.0', 'UTF-8');

        $prev = libxml_use_internal_errors(true);
        $loaded = $source->loadHTML(
            '<?xml encoding="utf-8" ?><!DOCTYPE html><html><body>' . $html . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if (!$loaded) {
            $fallback = strip_tags($html, '<p><br><strong><b><em><i><u><ol><ul><li>');
            return $this->normalize($fallback);
        }

        $target = new DOMDocument('1.0', 'UTF-8');
        $root = $target->createElement('div');
        $target->appendChild($root);

        $body = $source->getElementsByTagName('body')->item(0);
        if ($body) {
            foreach ($body->childNodes as $child) {
                $sanitized = $this->sanitizeNode($child, $target);
                $this->appendIfNotEmpty($root, $sanitized);
            }
        }

        $result = '';
        foreach ($root->childNodes as $node) {
            $result .= $target->saveHTML($node);
        }

        return $this->normalize($result);
    }

    private function sanitizeNode(DOMNode $node, DOMDocument $target): ?DOMNode
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return $target->createTextNode($node->textContent ?? '');
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return null;
        }

        $tag = strtolower($node->nodeName);

        if (!in_array($tag, self::ALLOWED_TAGS, true)) {
            $fragment = $target->createDocumentFragment();

            foreach ($node->childNodes as $child) {
                $sanitizedChild = $this->sanitizeNode($child, $target);
                $this->appendIfNotEmpty($fragment, $sanitizedChild);
            }

            return $fragment->hasChildNodes() ? $fragment : null;
        }

        $element = $target->createElement($tag);

        foreach ($node->childNodes as $child) {
            $sanitizedChild = $this->sanitizeNode($child, $target);
            $this->appendIfNotEmpty($element, $sanitizedChild);
        }

        return $element;
    }

    private function normalize(string $html): ?string
    {
        $html = trim($html);
        if ($html === '') {
            return null;
        }

        $plain = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
        if (trim($plain) === '') {
            return null;
        }

        return $html;
    }

    private function appendIfNotEmpty(DOMNode $parent, ?DOMNode $child): void
    {
        if ($child === null) {
            return;
        }

        if ($child instanceof DOMDocumentFragment && !$child->hasChildNodes()) {
            return;
        }

        $parent->appendChild($child);
    }
}
