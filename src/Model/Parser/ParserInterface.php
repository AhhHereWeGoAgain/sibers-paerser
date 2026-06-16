<?php

namespace App\Model\Parser;

/**
 * Defines a common contract for all response parsers.
 */
interface ParserInterface
{
    /**
     * Parses response body according to source configuration and selected mode.
     */
    public function parse(string $body, array $source_config = [], string $mode = 'list'): array;
}