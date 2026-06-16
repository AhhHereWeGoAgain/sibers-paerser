<?php 
namespace App\Model\Parser;

interface ParserInterface
{
    public function parse(string $body, array $source_config = [], string $mode = 'list'): array;
}