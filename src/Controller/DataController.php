<?php

namespace App\Controller;

use App\Model\Service\DataFetchService;

class DataController
{
    private DataFetchService $fetch_service;

    public function __construct(DataFetchService $fetch_service)
    {
        $this->fetch_service = $fetch_service;
    }

    public function index(array $query_params): void
    {
        $sources = $this->fetch_service->getActiveSources();

        $source_key = $this->getStringParam($query_params, 'source');
        $detail_url = $this->getStringParam($query_params, 'detail_url');
        $page = $this->getPositiveIntParam($query_params, 'page', 1);

        if ($source_key !== null && !array_key_exists($source_key, $sources)) {
            // Unknown or inactive source.
            $this->render('index', [
                'page_title' => 'Data parser',
                'sources' => $sources,
                'selected_source' => null,
                'page' => $page,
                'items' => [],
                'article' => [],
                'error' => [
                    'code' => 'UNKNOWN_SOURCE',
                    'message' => 'Selected source was not found or is inactive.',
                    'details' => 'Source key: ' . $source_key,
                ],
                'meta' => [],
                'result' => null,
            ]);

            return;
        }

        if ($source_key !== null && $detail_url !== null) {
            // Detail page request.
            $result = $this->fetch_service->fetchAndParseItemDetails($source_key, $detail_url);

            $this->render('detail', [
                'page_title' => 'Parsed item details',
                'sources' => $sources,
                'selected_source' => $source_key,
                'page' => $page,
                'detail_url' => $detail_url,
                'article' => $result['data'] ?? [],
                'items' => [],
                'error' => $result['error'] ?? null,
                'meta' => $result['meta'] ?? [],
                'result' => $result,
            ]);

            return;
        }

        if ($source_key !== null) {
            // List page request.
            $result = $this->fetch_service->fetchAndParseItemsBySourceKey($source_key, $page);

            $this->render('index', [
                'page_title' => 'Data parser',
                'sources' => $sources,
                'selected_source' => $source_key,
                'page' => $page,
                'items' => $result['data'] ?? [],
                'article' => [],
                'error' => $result['error'] ?? null,
                'meta' => $result['meta'] ?? [],
                'result' => $result,
            ]);

            return;
        }

        // Initial page without selected source.
        $this->render('index', [
            'page_title' => 'Data parser',
            'sources' => $sources,
            'selected_source' => null,
            'page' => $page,
            'items' => [],
            'article' => [],
            'error' => null,
            'meta' => [],
            'result' => null,
        ]);
    }

    private function render(string $template, array $data = []): void
    {
        $template_path = dirname(__DIR__) . '/View/' . $template . '.php';

        if (!is_file($template_path)) {
            http_response_code(500);

            echo 'View template was not found: ' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8');

            return;
        }

        extract($data, EXTR_SKIP);

        require $template_path;
    }

    private function getStringParam(array $query_params, string $key): ?string
    {
        $value = $query_params[$key] ?? null;

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function getPositiveIntParam(array $query_params, string $key, int $default = 1): int
    {
        $value = $query_params[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);

        if ($value === false || $value < 1) {
            return $default;
        }

        return $value;
    }
}