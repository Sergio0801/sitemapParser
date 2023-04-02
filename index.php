<?php
require(__DIR__ . '/vendor/autoload.php');

use Dotenv\Dotenv;
use app\src\exceptions\SitemapParserException;
use app\src\SitemapParser;
use app\src\View;



$dotenv = Dotenv::createUnsafeImmutable(__DIR__ . '/.');
$dotenv->safeLoad();


try {
    $parser = new SitemapParser();
    $parser->parseRecursive();

    $view = new View($parser->getURLs());
    echo $view->renderHTML();
} catch (SitemapParserException $e) {
    echo $e->getMessage();
}