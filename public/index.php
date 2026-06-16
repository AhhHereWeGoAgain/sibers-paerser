<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\DataController;
use App\Core\HttpClient;
use App\Model\Detector\ResponseTypeDetector;
use App\Model\Parser\ParserFactory;
use App\Model\Service\DataFetchService;
use App\Model\Source\SourceRegistry;

$sources = require __DIR__ . '/../config/sources.php';

$source_registry = new SourceRegistry($sources);
$type_detector = new ResponseTypeDetector();
$parser_factory = new ParserFactory();
$http_client = new HttpClient();

$fetch_service = new DataFetchService(
    $type_detector,
    $parser_factory,
    $http_client,
    $source_registry
);

$controller = new DataController($fetch_service);
$controller->index($_GET);