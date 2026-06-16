<?php

namespace App\Model\Parser;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use InvalidArgumentException;

class HtmlParser implements ParserInterface
{
    private const MODE_HANDLERS = [
        'list' => 'parseList',
        'detail' => 'parseDetail',
    ];

    public function parse(string $body, array $source_config = [], string $mode = 'list'): array
    // entrypoint метод, валидирует режим парсинга и соответствующий вызывает обработчик
    {
        if (!isset(self::MODE_HANDLERS[$mode])) { // unsupported parser mode
            throw new InvalidArgumentException(
                'Unsupported parser mode: ' . $mode . '. Supported modes: ' . implode(', ', array_keys(self::MODE_HANDLERS))
            );
        }

        if (empty($source_config[$mode]) || !is_array($source_config[$mode])) { // mode config not found
            throw new InvalidArgumentException(
                'Parser config for mode "' . $mode . '" was not found in source config.'
            );
        }

        if (trim($body) === '') { // empty body
            return [];
        }

        $handler = self::MODE_HANDLERS[$mode];

        return $this->{$handler}($body, $source_config);
    }

    private function parseList(string $body, array $source_config): array
    // Парсит список позиций с основной страницы
    {
        $list_config = $source_config['list'];
        $item_config = $list_config['item'] ?? [];
        $fields_config = $list_config['fields'] ?? [];

        if (empty($item_config) || empty($fields_config)) { 
            // invalid list config
            return [];
        }

        $xpath = $this->createXPath($body);

        $item_nodes = $this->findNodes($xpath, $item_config);

        if (empty($item_nodes)) { 
            // no list items found
            return [];
        }

        $items = [];

        foreach ($item_nodes as $item_node) {
            $item = $this->parseFields(
                $xpath,
                $fields_config,
                $item_node,
                $source_config
            );

            if (!$this->isValidItem($item, $fields_config)) { // invalid item
                continue;
            }

            $items[] = $item;
        }

        $max_items = (int) ($source_config['list']['max_items'] ?? 0);

        if ($max_items > 0) {
            $items = array_slice($items, 0, $max_items);
        }

        return $items;
    }

    private function parseDetail(string $body, array $source_config): array
    // Парсит детальную страницу одной ппозиуции(item) и возвращает информацию.
    {
        $detail_config = $source_config['detail'];
        $fields_config = $detail_config['fields'] ?? [];

        if (empty($fields_config)) { 
            // invalid detail config
            return [];
        }

        $xpath = $this->createXPath($body);

        return $this->parseFields(
            $xpath,
            $fields_config,
            null,
            $source_config
        );
    }

    private function parseFields(DOMXPath $xpath, array $fields_config, ?DOMNode $context_node, array $source_config): array 
    // Парсит набор полей из конфига внутри переданного DOM-контекста
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

    private function extractField(DOMXPath $xpath, array $field_config, ?DOMNode $context_node, array $source_config): mixed 
    // Извлекает значение одного поля из DOM по правилам из конфига
    {
        $nodes = $this->findNodes($xpath, $field_config, $context_node);

        if (empty($nodes)) { // field nodes not found
            return !empty($field_config['multiple']) ? [] : null;
        }

        $values = [];

        foreach ($nodes as $node) {
            $target_nodes = [$node];

            if (!empty($field_config['inner_selector'])) { // inner selector exists
                $inner_config = [
                    'selector_type' => $field_config['inner_selector_type'] ?? 'tag',
                    'selector' => $field_config['inner_selector'],
                ];

                $target_nodes = $this->findNodes($xpath, $inner_config, $node);

                if (empty($target_nodes)) { // inner nodes not found
                    continue;
                }
            }

            foreach ($target_nodes as $target_node) {
                $value = $this->extractValue(
                    $target_node,
                    $field_config['attribute'] ?? 'text'
                );

                if ($value === null || $value === '') { // empty field value
                    continue;
                }

                if (!empty($field_config['resolve_url'])) { // resolve relative url
                    $value = $this->resolveUrl(
                        $value,
                        $source_config['base_url'] ?? ''
                    );
                }

                if ($value !== '') {
                    $values[] = $value;
                }

                if (empty($field_config['multiple'])) { // single field value
                    break 2;
                }
            }
        }

        if (!empty($field_config['multiple'])) { // multiple field values
            $values = array_values(array_unique($values));

            if (($field_config['type'] ?? null) === 'text') { // merge text values
                $separator = $field_config['separator'] ?? "\n";

                return implode($separator, $values);
            }

            return $values;
        }

        return $values[0] ?? null;
    }

    private function findNodes(DOMXPath $xpath, array $selector_config, ?DOMNode $context_node = null): array
    //  Ищет DOM-элементы по selector_type и selector из конфига
    {
        $selector_type = $selector_config['selector_type'] ?? null;
        $selector = $selector_config['selector'] ?? null;

        if (!is_string($selector_type) || !is_string($selector) || trim($selector) === '') { // invalid selector config
            return [];
        }

        $query = $this->buildXPathQuery($selector_type, $selector);

        if ($query === null) { // unsupported selector type
            return [];
        }

        $node_list = $context_node
            ? $xpath->query($query, $context_node)
            : $xpath->query($query);

        if ($node_list === false || $node_list->length === 0) { // nodes not found
            return [];
        }

        $nodes = [];

        foreach ($node_list as $node) {
            $nodes[] = $node;
        }

        $nodes = $this->filterNodes($nodes, $selector_config);

        return $nodes;
    }

    private function extractValue(DOMNode $node, string $attribute): ?string
    // Извлекает текст или значение атрибута из DOM-элемента
    {
        if ($attribute === 'text') { // extract text content
            return $this->cleanText($node->textContent ?? '');
        }

        if (!$node instanceof DOMElement) { // node has no attributes
            return null;
        }

        if (!$node->hasAttribute($attribute)) { // attribute not found
            return null;
        }

        return $this->cleanText($node->getAttribute($attribute));
    }

    private function resolveUrl(string $url, string $base_url): string
    // Преобразует относительные ссылки в абсолютные.
    {
        $url = trim($url);

        if ($url === '') { // empty url
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) { // absolute url
            return $url;
        }

        if (str_starts_with($url, '//')) { // protocol-relative url
            return 'https:' . $url;
        }

        if ($base_url === '') { // base url not configured
            return $url;
        }

        if (str_starts_with($url, '/')) { // root-relative url
            return rtrim($base_url, '/') . $url;
        }

        return rtrim($base_url, '/') . '/' . ltrim($url, '/');
    }

    private function cleanText(string $text): string
    // Очищает текст от лишних пробелов, переносов строк и HTML-сущностей
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text ?? '');
    }

    private function isValidItem(array $item, array $fields_config): bool
    // Проверяет валидность и существование полей
    {
        foreach ($fields_config as $field_name => $field_config) {
            if (empty($field_config['required'])) { // field is optional
                continue;
            }

            if (!array_key_exists($field_name, $item)) { // required field not found
                return false;
            }

            $value = $item[$field_name];

            if (is_array($value) && empty($value)) { // empty required array field
                return false;
            }

            if (!is_array($value) && trim((string) $value) === '') { // empty required scalar field
                return false;
            }
        }

        return true;
    }

    private function createXPath(string $body): DOMXPath
    // Создаёт DOMXPath из сырого HTML
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $body);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function buildXPathQuery(string $selector_type, string $selector): ?string
    // Собирает XPath-запрос из типа селектора и значения селектора
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
    
    private function filterNodes(array $nodes, array $selector_config): array
    {
        if (empty($selector_config['attribute_contains']) || !is_array($selector_config['attribute_contains'])) {
            return $nodes;
        }

        $filtered_nodes = [];

        foreach ($nodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
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