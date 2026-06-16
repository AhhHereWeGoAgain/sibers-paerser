<?php

namespace App\Model\Detector;

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

    public function detect(?string $content_type): string
    {
        if ($content_type === null || trim($content_type) === '') { 
            // missing content type
            return self::TYPE_UNKNOWN;
        }

        $content_type = strtolower($content_type);

        foreach (self::CONTENT_TYPE_MAP as $needle => $type) {
            if (str_contains($content_type, $needle)) { 
                // success
                return $type;
            }
        }

        return self::TYPE_UNKNOWN; 
        // unsupported content type
    }
}