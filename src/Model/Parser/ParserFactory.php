<?php

namespace App\Model\Parser;

use App\Model\Detector\ResponseTypeDetector;
use InvalidArgumentException;

class ParserFactory
{
    public function create(string $type): ParserInterface
    {
        return match ($type) {
            ResponseTypeDetector::TYPE_HTML => new HtmlParser(),

            // Add this when AnyParser is implemented:
            // ResponseTypeDetector::TYPE_ANY => new AnyParser(),

            default => throw new InvalidArgumentException(
                'Parser not found for response type: ' . $type
            ),
        };
    }
}