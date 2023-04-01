<?php
require(__DIR__ . '/vendor/autoload.php');
(new \Dotenv\Dotenv(__DIR__ . '/.'))->load();

use parsers\exceptions\SitemapParserException;
use parsers\SitemapParser;
use parsers\View;

require 'SitemapParser.php';
require 'View.php';



try {
    $parser = new SitemapParser('MyCustomUserAgent');
    $parser->parseRecursive('https://biz.dinnerbooking.com/sitemap_index.xml');

    $view = new View($parser->getURLs());
    echo $view->renderHTML();
} catch (SitemapParserException $e) {
    echo $e->getMessage();
}