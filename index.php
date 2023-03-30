<?php

require 'SitemapParser.php';
require 'View.php';

use parsers\SitemapParser;
use parsers\View;

try {
    $parser = new SitemapParser('MyCustomUserAgent');
    $parser->parseRecursive('https://biz.dinnerbooking.com/sitemap_index.xml');

    $view = new View($parser->getURLs());
    echo $view->renderHTML();
} catch (SitemapParserException $e) {
    echo $e->getMessage();
}