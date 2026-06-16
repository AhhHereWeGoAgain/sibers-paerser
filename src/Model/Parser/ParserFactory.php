<?php

namespace App\Model\Parser;

use App\Model\Detector\ResponseTypeDetector;
use InvalidArgumentException;

/**
 * Creates parser instances by detected response type.
 */
class ParserFactory
{
    /**
     * Returns a parser for the given response type.
     */
    public function create(string $type): ParserInterface
    {
        return match ($type) {
            ResponseTypeDetector::TYPE_HTML => new HtmlParser(),

            // Add this line when AnyParser is implemented.
            // ResponseTypeDetector::TYPE_ANY => new AnyParser(),

            default => throw new InvalidArgumentException(
                'Parser not found for response type: ' . $type
            ), // Unsupported response type.
        };
    }
}
