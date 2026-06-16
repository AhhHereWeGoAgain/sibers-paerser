<?php

namespace App\Model\Service;

use App\Core\HttpClient;
use App\Model\Detector\ResponseTypeDetector;
use App\Model\Parser\ParserFactory;
use App\Model\Source\SourceRegistry;
use InvalidArgumentException;

/**
 * Loads external source pages, parses data and prepares results for views.
 */
class DataFetchService
{
    private ResponseTypeDetector $type_detector;

    private ParserFactory $parser_factory;

    private HttpClient $http_client;

    private SourceRegistry $source_registry;

    /**
     * Stores dependencies required for loading, detecting and parsing external data.
     */
    public function __construct(
        ResponseTypeDetector $type_detector,
        ParserFactory $parser_factory,
        HttpClient $http_client,
        SourceRegistry $source_registry
    ) {
        $this->type_detector = $type_detector;
        $this->parser_factory = $parser_factory;
        $this->http_client = $http_client;
        $this->source_registry = $source_registry;
    }

    /**
     * Returns only active sources for the web interface.
     */
    public function getActiveSources(): array
    {
        $sources = $this->source_registry->getAll();
        $active_sources = [];

        foreach ($sources as $key => $source) {
            if (!empty($source['is_active'])) {
                $active_sources[$key] = $source['name'];
            }
        }

        return $active_sources;
    }

    /**
     * Loads and parses list items by source key.
     */
    public function fetchAndParseItemsBySourceKey(string $source_key, int $page = 1): array
    {
        $source_data = $this->source_registry->get($source_key);

        $page = max(1, $page);

        $per_page = (int) ($source_data['pagination']['app_items_per_page'] ?? 10);
        $per_page = max(1, min($per_page, 50));

        if (empty($source_data['pagination']['enabled'])) {
            $result = $this->fetch($source_data, 'list');

            if (!$result['success']) {
                return $result; // Source loading or parsing failed.
            }

            return $this->sliceItemsForAppPage(
                $result,
                $page,
                $per_page,
                1,
                [$source_data['url']]
            ); // Pagination is disabled, so items are sliced locally.
        }

        return $this->fetchPaginatedList($source_key, $source_data, $page, $per_page);
    }

    /**
     * Loads and parses detail page for selected item.
     */
    public function fetchAndParseItemDetails(string $source_key, string $detail_url): array
    {
        $source_data = $this->source_registry->get($source_key);

        if (empty($source_data['detail']['enabled'])) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'DETAIL_PARSING_DISABLED',
                    'message' => 'Detail parsing is disabled for this source.',
                    'details' => null,
                ],
                'meta' => [
                    'source_key' => $source_key,
                    'detail_url' => $detail_url,
                ],
            ]; // Detail parsing is not enabled in source config.
        }

        if (trim($detail_url) === '') {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'EMPTY_DETAIL_URL',
                    'message' => 'Detail URL is empty.',
                    'details' => null,
                ],
                'meta' => [
                    'source_key' => $source_key,
                ],
            ]; // Detail URL was not provided.
        }

        $list_url = $source_data['url'];

        if (!empty($source_data['request']['use_cookie_jar'])) {
            $request_headers = $source_data['request']['headers'] ?? [];
            $request_referer = $source_data['request']['referer'] ?? null;
            $request_user_agent = $source_data['request']['user_agent'] ?? null;
            $cookie_file = $this->buildCookieFilePath($source_data);

            $this->ensureCookieDirectoryExists($cookie_file);

            $this->http_client->sendRequest(
                $list_url,
                $request_headers,
                $request_referer,
                $request_user_agent,
                $cookie_file
            );
        }

        $source_data['url'] = $detail_url;

        return $this->fetch($source_data, 'detail');
    }

    /**
     * Loads external page, detects response type and parses response body.
     */
    public function fetch(array $source_data, string $mode = 'list'): array
    {
        if (empty($source_data['url']) || !is_string($source_data['url'])) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'INVALID_SOURCE_CONFIG',
                    'message' => 'Invalid source configuration.',
                    'details' => 'Source URL is missing or invalid.',
                ],
                'meta' => [
                    'mode' => $mode,
                ],
            ]; // Source URL is missing or invalid.
        }

        $url = $source_data['url'];
        $request_headers = $source_data['request']['headers'] ?? [];
        $request_referer = $source_data['request']['referer'] ?? null;
        $request_user_agent = $source_data['request']['user_agent'] ?? null;

        $cookie_file = null;

        if (!empty($source_data['request']['use_cookie_jar'])) {
            $cookie_file = $this->buildCookieFilePath($source_data);
            $this->ensureCookieDirectoryExists($cookie_file);
        }

        $response = $this->http_client->sendRequest(
            $url,
            $request_headers,
            $request_referer,
            $request_user_agent,
            $cookie_file
        );

        if (!$response['success']) {
            $response['meta']['mode'] = $mode;
            $response['meta']['source_url'] = $url;

            return $response; // HTTP client returned request error.
        }

        $body = $response['data']['body'] ?? null;

        if (!empty($source_data['debug']['save_html'])) {
            $this->saveDebugHtml((string) $body, $source_data, $mode);
        }

        $content_type = $response['data']['content_type'] ?? null;
        $status_code = $response['meta']['status_code'] ?? null;

        if (!is_string($body) || trim($body) === '') {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'EMPTY_RESPONSE',
                    'message' => 'External source returned an empty response.',
                    'details' => null,
                ],
                'meta' => [
                    'url' => $url,
                    'status_code' => $status_code,
                    'content_type' => $content_type,
                    'mode' => $mode,
                ],
            ]; // External source returned empty body.
        }

        if (!is_string($content_type) || trim($content_type) === '') {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'MISSING_CONTENT_TYPE',
                    'message' => 'External source did not return Content-Type header.',
                    'details' => null,
                ],
                'meta' => [
                    'url' => $url,
                    'status_code' => $status_code,
                    'mode' => $mode,
                ],
            ]; // Content-Type header is required for parser selection.
        }

        $response_type = $this->type_detector->detect($content_type);

        if ($response_type === ResponseTypeDetector::TYPE_UNKNOWN) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'UNSUPPORTED_RESPONSE_TYPE',
                    'message' => 'Unsupported response type.',
                    'details' => 'Content-Type: ' . $content_type,
                ],
                'meta' => [
                    'url' => $url,
                    'status_code' => $status_code,
                    'content_type' => $content_type,
                    'response_type' => $response_type,
                    'mode' => $mode,
                ],
            ]; // Content-Type is not supported by the application.
        }

        try {
            $parser = $this->parser_factory->create($response_type);
        } catch (InvalidArgumentException $exception) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'PARSER_NOT_FOUND',
                    'message' => 'Parser was not found for response type.',
                    'details' => $exception->getMessage(),
                ],
                'meta' => [
                    'url' => $url,
                    'status_code' => $status_code,
                    'content_type' => $content_type,
                    'response_type' => $response_type,
                    'mode' => $mode,
                ],
            ]; // Parser factory cannot create parser for detected type.
        }

        try {
            $parsed_data = $parser->parse($body, $source_data, $mode);
        } catch (InvalidArgumentException $exception) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'PARSER_CONFIG_ERROR',
                    'message' => 'Parser configuration error.',
                    'details' => $exception->getMessage(),
                ],
                'meta' => [
                    'url' => $url,
                    'status_code' => $status_code,
                    'content_type' => $content_type,
                    'response_type' => $response_type,
                    'mode' => $mode,
                ],
            ]; // Parser cannot work with current source configuration.
        }

        if (empty($parsed_data)) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'NO_ITEMS_FOUND',
                    'message' => 'No items were found in the external source.',
                    'details' => null,
                ],
                'meta' => [
                    'url' => $url,
                    'status_code' => $status_code,
                    'content_type' => $content_type,
                    'response_type' => $response_type,
                    'mode' => $mode,
                    'body_preview' => $this->buildBodyPreview($body),
                    'debug_html_file' => $this->saveDebugHtml($body, $source_data, $mode),
                ],
            ]; // Parser returned empty result.
        }

        return [
            'success' => true,
            'data' => $parsed_data,
            'error' => null,
            'meta' => [
                'url' => $url,
                'status_code' => $status_code,
                'content_type' => $content_type,
                'response_type' => $response_type,
                'mode' => $mode,
            ],
        ]; // External source was loaded and parsed successfully.
    }

    /**
     * Loads enough source pages to build one application page.
     */
    private function fetchPaginatedList(string $source_key, array $source_data, int $page, int $per_page): array
    {
        $pagination = $source_data['pagination'] ?? [];

        $start_page = (int) ($pagination['start_page'] ?? 1);
        $max_source_pages_per_request = (int) ($pagination['max_source_pages_per_request'] ?? 3);

        $start_page = max(1, $start_page);
        $max_source_pages_per_request = max(1, $max_source_pages_per_request);

        $pagination_state = $this->getPaginationState($source_key, $source_data);

        if ($pagination_state === null) {
            $calibration_result = $this->calibratePagination($source_key, $source_data);

            if (!$calibration_result['success']) {
                return $calibration_result; // Pagination calibration failed.
            }

            $pagination_state = $calibration_result['data'];
        }

        $first_page_items_count = (int) ($pagination_state['first_page_items_count'] ?? 0);
        $regular_page_items_count = (int) ($pagination_state['regular_page_items_count'] ?? 0);

        if ($first_page_items_count <= 0 || $regular_page_items_count <= 0) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'INVALID_PAGINATION_STATE',
                    'message' => 'Pagination state is invalid.',
                    'details' => 'First page or regular page item count is zero.',
                ],
                'meta' => [
                    'source_key' => $source_key,
                    'pagination_state' => $pagination_state,
                ],
            ]; // Cached pagination state is invalid.
        }

        $first_needed_item_index = ($page - 1) * $per_page;
        $last_needed_item_index = $first_needed_item_index + $per_page - 1;

        $first_source_page_info = $this->mapItemIndexToSourcePage(
            $first_needed_item_index,
            $start_page,
            $first_page_items_count,
            $regular_page_items_count
        );

        $last_source_page_info = $this->mapItemIndexToSourcePage(
            $last_needed_item_index,
            $start_page,
            $first_page_items_count,
            $regular_page_items_count
        );

        $first_source_page = $first_source_page_info['source_page'];
        $last_source_page = $last_source_page_info['source_page'];

        if (($last_source_page - $first_source_page + 1) > $max_source_pages_per_request) {
            $last_source_page = $first_source_page + $max_source_pages_per_request - 1;
        }

        $all_items = [];
        $loaded_urls = [];
        $loaded_source_pages = [];
        $source_page_item_counts = [];

        for ($source_page = $first_source_page; $source_page <= $last_source_page; $source_page++) {
            $page_url = $this->buildSourcePageUrl($source_data, $source_page);

            $page_source_data = $source_data;
            $page_source_data['url'] = $page_url;

            $result = $this->fetch($page_source_data, 'list');

            $loaded_urls[] = $page_url;
            $loaded_source_pages[] = $source_page;

            if (!$result['success']) {
                if (empty($all_items)) {
                    return $result; // First required source page failed.
                }

                break;
            }

            $items = $result['data'] ?? [];

            if (!is_array($items) || empty($items)) {
                break;
            }

            $source_page_item_counts[$source_page] = count($items);
            $all_items = array_merge($all_items, $items);
        }

        $first_loaded_source_page_start_index = $this->getSourcePageStartItemIndex(
            $first_source_page,
            $start_page,
            $first_page_items_count,
            $regular_page_items_count
        );

        $local_offset = max(0, $first_needed_item_index - $first_loaded_source_page_start_index);
        $page_items = array_slice($all_items, $local_offset, $per_page);

        return [
            'success' => true,
            'data' => $page_items,
            'error' => null,
            'meta' => [
                'mode' => 'list',

                'app_page' => $page,
                'app_items_per_page' => $per_page,

                'first_page_items_count' => $first_page_items_count,
                'regular_page_items_count' => $regular_page_items_count,
                'pagination_state' => $pagination_state,

                'source_pages_loaded' => $loaded_source_pages,
                'source_urls_loaded' => $loaded_urls,
                'source_page_item_counts' => $source_page_item_counts,

                'loaded_items_count' => count($all_items),
                'shown_items_count' => count($page_items),

                'has_previous_page' => $page > 1,
                'has_next_page' => count($page_items) === $per_page,

                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => count($page_items) === $per_page ? $page + 1 : null,
            ],
        ]; // Paginated list was loaded and sliced for the current app page.
    }

    /**
     * Builds external source page URL by pagination template.
     */
    private function buildSourcePageUrl(array $source_data, int $source_page): string
    {
        $pagination = $source_data['pagination'] ?? [];
        $start_page = (int) ($pagination['start_page'] ?? 1);

        if ($source_page === $start_page) {
            return $source_data['url']; // First source page uses base source URL.
        }

        $url_template = $pagination['url_template'] ?? null;

        if (!is_string($url_template) || trim($url_template) === '') {
            return $source_data['url']; // Pagination template is missing.
        }

        return str_replace('{page}', (string) $source_page, $url_template);
    }

    /**
     * Slices already loaded items for the requested application page.
     */
    private function sliceItemsForAppPage(
        array $result,
        int $page,
        int $per_page,
        int $source_pages_loaded,
        array $loaded_urls
    ): array {
        $items = $result['data'] ?? [];

        if (!is_array($items)) {
            $items = [];
        }

        $total_items = count($items);
        $offset = ($page - 1) * $per_page;

        $page_items = array_slice($items, $offset, $per_page);

        return [
            'success' => true,
            'data' => $page_items,
            'error' => null,
            'meta' => array_merge($result['meta'] ?? [], [
                'app_page' => $page,
                'app_items_per_page' => $per_page,

                'source_pages_loaded' => $source_pages_loaded,
                'source_urls_loaded' => $loaded_urls,

                'total_loaded_items' => $total_items,
                'shown_items_count' => count($page_items),

                'has_previous_page' => $page > 1,
                'has_next_page' => ($offset + $per_page) < $total_items,

                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => ($offset + $per_page) < $total_items ? $page + 1 : null,
            ]),
        ]; // Items were sliced without loading additional source pages.
    }

    /**
     * Returns cached pagination state if it exists and is still valid.
     */
    private function getPaginationState(string $source_key, array $source_data): ?array
    {
        $pagination = $source_data['pagination'] ?? [];

        if (empty($pagination['auto_calibrate'])) {
            return null; // Auto calibration is disabled.
        }

        $state_file = $this->buildPaginationStateFilePath($source_key);

        if (!is_file($state_file)) {
            return null; // Pagination cache file does not exist.
        }

        $raw_state = file_get_contents($state_file);

        if ($raw_state === false || trim($raw_state) === '') {
            return null; // Pagination cache file is empty or unreadable.
        }

        $state = json_decode($raw_state, true);

        if (!is_array($state)) {
            return null; // Pagination cache contains invalid JSON.
        }

        $ttl = (int) ($pagination['calibration_ttl'] ?? 3600);
        $ttl = max(60, $ttl);

        $calibrated_at = (int) ($state['calibrated_at'] ?? 0);

        if ($calibrated_at <= 0 || (time() - $calibrated_at) > $ttl) {
            return null; // Pagination cache expired.
        }

        if (empty($state['first_page_items_count']) || empty($state['regular_page_items_count'])) {
            return null; // Pagination cache has no item counts.
        }

        return $state;
    }

    /**
     * Detects how many items are available on first and regular source pages.
     */
    private function calibratePagination(string $source_key, array $source_data): array
    {
        $pagination = $source_data['pagination'] ?? [];

        $start_page = (int) ($pagination['start_page'] ?? 1);
        $start_page = max(1, $start_page);

        $first_page_url = $this->buildSourcePageUrl($source_data, $start_page);
        $regular_page_url = $this->buildSourcePageUrl($source_data, $start_page + 1);

        $first_page_source_data = $source_data;
        $first_page_source_data['url'] = $first_page_url;

        $first_page_result = $this->fetch($first_page_source_data, 'list');

        if (!$first_page_result['success']) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'PAGINATION_CALIBRATION_FAILED',
                    'message' => 'Failed to calibrate first source page.',
                    'details' => $first_page_result['error']['details'] ?? null,
                ],
                'meta' => [
                    'source_key' => $source_key,
                    'first_page_url' => $first_page_url,
                    'first_page_error' => $first_page_result['error'] ?? null,
                    'first_page_meta' => $first_page_result['meta'] ?? [],
                    'first_page_result' => $first_page_result,
                ],
            ]; // First source page could not be loaded or parsed.
        }

        $regular_page_source_data = $source_data;
        $regular_page_source_data['url'] = $regular_page_url;

        $regular_page_result = $this->fetch($regular_page_source_data, 'list');

        if (!$regular_page_result['success']) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'PAGINATION_CALIBRATION_FAILED',
                    'message' => 'Failed to calibrate regular source page.',
                    'details' => $regular_page_result['error']['details'] ?? null,
                ],
                'meta' => [
                    'source_key' => $source_key,
                    'regular_page_url' => $regular_page_url,
                    'regular_page_error' => $regular_page_result['error'] ?? null,
                    'regular_page_meta' => $regular_page_result['meta'] ?? [],
                    'regular_page_result' => $regular_page_result,
                ],
            ]; // Regular source page could not be loaded or parsed.
        }

        $first_page_items = $first_page_result['data'] ?? [];
        $regular_page_items = $regular_page_result['data'] ?? [];

        $first_page_items_count = is_array($first_page_items) ? count($first_page_items) : 0;
        $regular_page_items_count = is_array($regular_page_items) ? count($regular_page_items) : 0;

        if ($first_page_items_count <= 0 || $regular_page_items_count <= 0) {
            return [
                'success' => false,
                'data' => [],
                'error' => [
                    'code' => 'PAGINATION_CALIBRATION_EMPTY',
                    'message' => 'Pagination calibration returned empty item count.',
                    'details' => null,
                ],
                'meta' => [
                    'source_key' => $source_key,
                    'first_page_url' => $first_page_url,
                    'regular_page_url' => $regular_page_url,
                    'first_page_items_count' => $first_page_items_count,
                    'regular_page_items_count' => $regular_page_items_count,
                    'first_page_result' => $first_page_result,
                    'regular_page_result' => $regular_page_result,
                ],
            ]; // Calibration pages did not return item counts.
        }

        $state = [
            'source_key' => $source_key,
            'first_page_url' => $first_page_url,
            'regular_page_url' => $regular_page_url,
            'first_page_items_count' => $first_page_items_count,
            'regular_page_items_count' => $regular_page_items_count,
            'calibrated_at' => time(),
        ];

        $this->savePaginationState($source_key, $state);

        return [
            'success' => true,
            'data' => $state,
            'error' => null,
            'meta' => [
                'source_key' => $source_key,
                'first_page_url' => $first_page_url,
                'regular_page_url' => $regular_page_url,
            ],
        ]; // Pagination calibration completed successfully.
    }

    /**
     * Saves pagination state to file cache.
     */
    private function savePaginationState(string $source_key, array $state): void
    {
        $state_file = $this->buildPaginationStateFilePath($source_key);
        $state_directory = dirname($state_file);

        if (!is_dir($state_directory)) {
            mkdir($state_directory, 0775, true);
        }

        file_put_contents(
            $state_file,
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Builds safe file path for pagination state cache.
     */
    private function buildPaginationStateFilePath(string $source_key): string
    {
        $safe_source_key = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $source_key) ?? 'source';

        return dirname(__DIR__, 3) . '/storage/cache/pagination/' . $safe_source_key . '.json';
    }

    /**
     * Converts global item index to external source page number and local offset.
     */
    private function mapItemIndexToSourcePage(
        int $item_index,
        int $start_page,
        int $first_page_items_count,
        int $regular_page_items_count
    ): array {
        $item_index = max(0, $item_index);

        if ($item_index < $first_page_items_count) {
            return [
                'source_page' => $start_page,
                'local_offset' => $item_index,
            ]; // Item is located on the first source page.
        }

        $regular_item_index = $item_index - $first_page_items_count;

        return [
            'source_page' => $start_page + 1 + intdiv($regular_item_index, $regular_page_items_count),
            'local_offset' => $regular_item_index % $regular_page_items_count,
        ]; // Item is located on one of regular source pages.
    }

    /**
     * Returns global start item index for external source page.
     */
    private function getSourcePageStartItemIndex(
        int $source_page,
        int $start_page,
        int $first_page_items_count,
        int $regular_page_items_count
    ): int {
        if ($source_page <= $start_page) {
            return 0; // First source page starts from index zero.
        }

        return $first_page_items_count + (($source_page - $start_page - 1) * $regular_page_items_count);
    }

    /**
     * Builds safe cookie file path for source host.
     */
    private function buildCookieFilePath(array $source_data): string
    {
        $base_url = $source_data['base_url'] ?? $source_data['url'] ?? 'source';
        $host = parse_url($base_url, PHP_URL_HOST);

        if (!is_string($host) || trim($host) === '') {
            $host = md5((string) $base_url);
        }

        $safe_host = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $host) ?? 'source';

        return dirname(__DIR__, 3) . '/storage/cache/' . $safe_host . '_cookies.txt';
    }

    /**
     * Creates cookie cache directory if it does not exist.
     */
    private function ensureCookieDirectoryExists(string $cookie_file): void
    {
        $cookie_directory = dirname($cookie_file);

        if (!is_dir($cookie_directory)) {
            mkdir($cookie_directory, 0775, true);
        }
    }

    /**
     * Creates short plain text preview from response body.
     */
    private function buildBodyPreview(string $body, int $limit = 1000): string
    {
        $text = trim(strip_tags($body));
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $limit);
        }

        return substr($text, 0, $limit);
    }

    /**
     * Saves raw HTML response for debug purposes.
     */
    private function saveDebugHtml(string $body, array $source_data, string $mode): string
    {
        $base_url = $source_data['base_url'] ?? $source_data['url'] ?? 'source';
        $host = parse_url($base_url, PHP_URL_HOST);

        if (!is_string($host) || trim($host) === '') {
            $host = md5((string) $base_url);
        }

        $safe_host = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $host) ?? 'source';
        $debug_directory = dirname(__DIR__, 3) . '/storage/cache/debug';

        if (!is_dir($debug_directory)) {
            mkdir($debug_directory, 0775, true);
        }

        $debug_file = $debug_directory . '/' . $safe_host . '_' . $mode . '_debug.html';

        file_put_contents($debug_file, $body);

        return $debug_file;
    }
}