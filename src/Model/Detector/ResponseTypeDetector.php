<?php

namespace App\Model\Detector;

/**
 * Detects response type by HTTP Content-Type header.
 */
class ResponseTypeDetector
{
    public const TYPE_JSON = 'json';
    public const TYPE_HTML = 'html';
    public const TYPE_XML = 'xml';
    public const TYPE_RSS = 'rss';
    public const TYPE_UNKNOWN = 'unknown';

    private const CONTENT_TYPE_MAP = [
        'application/json' => self::TYPE_JSON,
        'text/json' => self::TYPE_JSON,

        'text/html' => self::TYPE_HTML,

        'application/xml' => self::TYPE_XML,
        'text/xml' => self::TYPE_XML,

        'application/rss+xml' => self::TYPE_RSS,
        'application/atom+xml' => self::TYPE_RSS,
    ];

    /**
     * Returns normalized response type for the given Content-Type header.
     */
    public function detect(?string $content_type): string
    {
        if ($content_type === null || trim($content_type) === '') {
            return self::TYPE_UNKNOWN; // Content-Type header is missing.
        }

        $content_type = strtolower($content_type);

        foreach (self::CONTENT_TYPE_MAP as $needle => $type) {
            if (str_contains($content_type, $needle)) {
                return $type; // Supported Content-Type was found.
            }
        }

        return self::TYPE_UNKNOWN; // Content-Type is not supported by the application.
    }
}
