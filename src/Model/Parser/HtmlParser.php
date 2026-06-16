<?php

namespace App\Model\Parser;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use InvalidArgumentException;

/**
 * Parses HTML pages according to source configuration.
 */
class HtmlParser implements ParserInterface
{
    private const MODE_HANDLERS = [
        'list' => 'parseList',
        'detail' => 'parseDetail',
    ];

    /**
     * Validates parsing mode and calls the related parser method.
     */
    public function parse(string $body, array $source_config = [], string $mode = 'list'): array
    {
        if (!isset(self::MODE_HANDLERS[$mode])) {
            throw new InvalidArgumentException(
                'Unsupported parser mode: ' . $mode . '. Supported modes: ' . implode(', ', array_keys(self::MODE_HANDLERS))
            );
        }

        if (empty($source_config[$mode]) || !is_array($source_config[$mode])) {
            throw new InvalidArgumentException(
                'Parser config for mode "' . $mode . '" was not found in source config.'
            );
        }

        if (trim($body) === '') {
            return []; // Empty response body.
        }

        $handler = self::MODE_HANDLERS[$mode];

        return $this->{$handler}($body, $source_config);
    }

    /**
     * Parses a list page and returns extracted items.
     */
    private function parseList(string $body, array $source_config): array
    {
        $list_config = $source_config['list'];
        $item_config = $list_config['item'] ?? [];
        $fields_config = $list_config['fields'] ?? [];

        if (empty($item_config) || empty($fields_config)) {
            return []; // List item or fields configuration is missing.
        }

        $xpath = $this->createXPath($body);

        $item_nodes = $this->findNodes($xpath, $item_config);

        if (empty($item_nodes)) {
            return []; // No list items found by configured selector.
        }

        $items = [];

        foreach ($item_nodes as $item_node) {
            $item = $this->parseFields(
                $xpath,
                $fields_config,
                $item_node,
                $source_config
            );

            if (!$this->isValidItem($item, $fields_config)) {
                continue; // Skip item without required fields.
            }

            $items[] = $item;
        }

        $max_items = (int) ($source_config['list']['max_items'] ?? 0);

        if ($max_items > 0) {
            $items = array_slice($items, 0, $max_items);
        }

        return $items;
    }

    /**
     * Parses a detail page and returns extracted fields.
     */
    private function parseDetail(string $body, array $source_config): array
    {
        $detail_config = $source_config['detail'];
        $fields_config = $detail_config['fields'] ?? [];

        if (empty($fields_config)) {
            return []; // Detail fields configuration is missing.
        }

        $xpath = $this->createXPath($body);

        return $this->parseFields(
            $xpath,
            $fields_config,
            null,
            $source_config
        );
    }

    /**
     * Parses configured fields inside the given DOM context.
     */
    private function parseFields(DOMXPath $xpath, array $fields_config, ?DOMNode $context_node, array $source_config): array
    {
        $parsed_fields = [];

        foreach ($fields_config as $field_name => $field_config) {
            $parsed_fields[$field_name] = $this->extractField(
                $xpath,
                $field_config,
                $context_node,
                $source_config
            );
        }

        return $parsed_fields;
    }

    /**
     * Extracts one configured field from DOM.
     */
    private function extractField(DOMXPath $xpath, array $field_config, ?DOMNode $context_node, array $source_config): mixed
    {
        $nodes = $this->findNodes($xpath, $field_config, $context_node);

        if (empty($nodes)) {
            return !empty($field_config['multiple']) ? [] : null; // Field node was not found.
        }

        $values = [];

        foreach ($nodes as $node) {
            $target_nodes = [$node];

            if (!empty($field_config['inner_selector'])) {
                $inner_config = [
                    'selector_type' => $field_config['inner_selector_type'] ?? 'tag',
                    'selector' => $field_config['inner_selector'],
                ];

                $target_nodes = $this->findNodes($xpath, $inner_config, $node);

                if (empty($target_nodes)) {
                    continue; // Inner selector did not match any nodes.
                }
            }

            foreach ($target_nodes as $target_node) {
                $value = $this->extractValue(
                    $target_node,
                    $field_config['attribute'] ?? 'text'
                );

                if ($value === null || $value === '') {
                    continue; // Empty value is ignored.
                }

                if (!empty($field_config['resolve_url'])) {
                    $value = $this->resolveUrl(
                        $value,
                        $source_config['base_url'] ?? ''
                    );
                }

                if ($value !== '') {
                    $values[] = $value;
                }

                if (empty($field_config['multiple'])) {
                    break 2; // Single-value field already extracted.
                }
            }
        }

        if (!empty($field_config['multiple'])) {
            $values = array_values(array_unique($values));

            if (($field_config['type'] ?? null) === 'text') {
                $separator = $field_config['separator'] ?? "\n";

                return implode($separator, $values); // Multiple text values are merged.
            }

            return $values; // Multiple non-text values are returned as array.
        }

        return $values[0] ?? null; // Single field value or null.
    }

    /**
     * Finds DOM nodes by selector configuration.
     */
    private function findNodes(DOMXPath $xpath, array $selector_config, ?DOMNode $context_node = null): array
    {
        $selector_type = $selector_config['selector_type'] ?? null;
        $selector = $selector_config['selector'] ?? null;

        if (!is_string($selector_type) || !is_string($selector) || trim($selector) === '') {
            return []; // Selector configuration is invalid.
        }

        $query = $this->buildXPathQuery($selector_type, $selector);

        if ($query === null) {
            return []; // Selector type is not supported.
        }

        $node_list = $context_node
            ? $xpath->query($query, $context_node)
            : $xpath->query($query);

        if ($node_list === false || $node_list->length === 0) {
            return []; // No nodes matched the selector.
        }

        $nodes = [];

        foreach ($node_list as $node) {
            $nodes[] = $node;
        }

        return $this->filterNodes($nodes, $selector_config);
    }

    /**
     * Extracts text content or attribute value from a DOM node.
     */
    private function extractValue(DOMNode $node, string $attribute): ?string
    {
        if ($attribute === 'text') {
            return $this->cleanText($node->textContent ?? '');
        }

        if (!$node instanceof DOMElement) {
            return null; // Only DOMElement can contain attributes.
        }

        if (!$node->hasAttribute($attribute)) {
            return null; // Requested attribute does not exist.
        }

        return $this->cleanText($node->getAttribute($attribute));
    }

    /**
     * Converts relative URLs to absolute URLs.
     */
    private function resolveUrl(string $url, string $base_url): string
    {
        $url = trim($url);

        if ($url === '') {
            return ''; // Empty URL cannot be resolved.
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url; // URL is already absolute.
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url; // Protocol-relative URL.
        }

        if ($base_url === '') {
            return $url; // Base URL is not configured.
        }

        if (str_starts_with($url, '/')) {
            return rtrim($base_url, '/') . $url; // Root-relative URL.
        }

        return rtrim($base_url, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Normalizes extracted text.
     */
    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }

    /**
     * Checks that all required fields are present and not empty.
     */
    private function isValidItem(array $item, array $fields_config): bool
    {
        foreach ($fields_config as $field_name => $field_config) {
            if (empty($field_config['required'])) {
                continue; // Optional field can be empty.
            }

            if (!array_key_exists($field_name, $item)) {
                return false; // Required field is missing.
            }

            $value = $item[$field_name];

            if (is_array($value) && empty($value)) {
                return false; // Required array field is empty.
            }

            if (!is_array($value) && trim((string) $value) === '') {
                return false; // Required scalar field is empty.
            }
        }

        return true;
    }

    /**
     * Creates DOMXPath instance from raw HTML.
     */
    private function createXPath(string $body): DOMXPath
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $body);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    /**
     * Builds XPath query by selector type.
     */
    private function buildXPathQuery(string $selector_type, string $selector): ?string
    {
        $selector = trim($selector);

        return match ($selector_type) {
            'self' => '.',
            'class' => ".//*[contains(concat(' ', normalize-space(@class), ' '), ' {$selector} ')]",
            'id' => ".//*[@id='{$selector}']",
            'tag' => ".//{$selector}",
            default => null,
        };
    }

    /**
     * Filters nodes by configured attribute conditions.
     */
    private function filterNodes(array $nodes, array $selector_config): array
    {
        if (empty($selector_config['attribute_contains']) || !is_array($selector_config['attribute_contains'])) {
            return $nodes; // No additional attribute filters configured.
        }

        $filtered_nodes = [];

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue; // Attribute filters can be applied only to elements.
            }

            $is_valid = true;

            foreach ($selector_config['attribute_contains'] as $attribute => $needle) {
                if (!is_string($attribute) || !is_string($needle)) {
                    continue;
                }

                if (!$node->hasAttribute($attribute)) {
                    $is_valid = false;
                    break;
                }

                $attribute_value = $node->getAttribute($attribute);

                if (!str_contains($attribute_value, $needle)) {
                    $is_valid = false;
                    break;
                }
            }

            if ($is_valid) {
                $filtered_nodes[] = $node;
            }
        }

        return $filtered_nodes;
    }
}
