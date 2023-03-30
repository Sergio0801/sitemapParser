<?php

namespace parsers;

require 'UrlParser.php';
require 'CurlRequest.php';
require 'exceptions/SitemapParserException.php';
require 'exceptions/TransferException.php';

use Exception;
use parsers\exceptions\{SitemapParserException, TransferException};
use SimpleXMLElement;

/**
 *  SitemapParser class
 */
class SitemapParser
{
    use UrlParser;

    /**
     * Default User-Agent
     */
    private const DEFAULT_USER_AGENT = 'Mozilla/1';

    /**
     * XML Sitemap tag
     */
    private const XML_TAG_SITEMAP = 'sitemap';

    /**
     * Default encoding
     */
    private const ENCODING = 'UTF-8';

    /**
     * XML file extension
     */
    private const XML_EXTENSION = 'xml';

    /**
     * Compressed XML file extension
     */
    private const XML_EXTENSION_COMPRESSED = 'xml.gz';

    /**
     * XML URL tag
     */
    private const XML_TAG_URL = 'url';

    /**
     * Sitemaps discovered
     * @var array
     */
    protected array $sitemaps = [];

    /**
     * URLs discovered
     * @var array
     */
    protected array $urls = [];

    /**
     * User-Agent to send with every HTTP(S) request
     * @var string
     */
    protected string $userAgent;

    /**
     * Configuration options
     * @var array
     */
    protected array $config = [];

    /**
     * Parsed URLs history
     * @var array
     */
    protected array $history = [];

    /**
     * Current URL being parsed
     * @var null|string
     */
    protected ?string $currentURL;

    /**
     * Sitemap URLs discovered but not yet parsed
     * @var array
     */
    protected array $queue = [];

    /**
     * Constructor
     *
     * @param string $userAgent User-Agent to send with every HTTP(S) request
     * @throws SitemapParserException
     */
    public function __construct(string $userAgent = self::DEFAULT_USER_AGENT)
    {
        mb_language("uni");
        if (!mb_internal_encoding(self::ENCODING)) {
            throw new SitemapParserException(SitemapParserException::UNABLE_SET_CHARECTER_TO . self::ENCODING);
        }
        $this->userAgent = $userAgent;
    }

    /**
     * Sitemaps discovered
     *
     * @return array
     */
    public function getSitemaps(): array
    {
        return $this->sitemaps;
    }

    /**
     * URLs discovered
     *
     * @return array
     */
    public function getURLs(): array
    {
        return $this->urls;
    }

    /**
     * Parse Recursive
     *
     * @param string $url
     * @return void
     * @throws SitemapParserException
     */
    public function parseRecursive(string $url): void
    {
        $this->addToQueue([$url]);
        $this->clean();
        while (count($todo = $this->getQueue()) > 0) {
            $sitemaps = $this->sitemaps;
            $urls = $this->urls;
            try {
                $this->parse($todo[0]);
            } catch (TransferException $e) {
                // Keep crawling
                //take  $e->getMessage() and store in storage for Logs
            }
            $this->sitemaps = array_merge_recursive($sitemaps, $this->sitemaps);
            $this->urls = array_merge_recursive($urls, $this->urls);
        }
    }

    /**
     * Parse
     *
     * @param string $url URL to parse
     * @param string|null $urlContent URL body content (provide to skip download)
     * @return void
     * @throws TransferException
     * @throws SitemapParserException
     */
    protected function parse(string $url, ?string $urlContent = null): void
    {
        $this->clean();
        $this->currentURL = $this->urlEncode($url);
        if (!$this->urlValidate($this->currentURL)) {
            throw new SitemapParserException(SitemapParserException::INVALID_URL);
        }
        $this->history[] = $this->currentURL;
        $response = is_string($urlContent) ? $urlContent : $this->getContent();
        $sitemapJson = $this->generateXMLObject($response);
        if ($sitemapJson instanceof SimpleXMLElement === false) {
            $this->parseString($response);

            return;
        }

        $this->parseJson(self::XML_TAG_SITEMAP, $sitemapJson);
        $this->parseJson(self::XML_TAG_URL, $sitemapJson);
    }

    /**
     * Add an array of URLs to the parser queue
     *
     * @param array $urlArray
     * @return void
     */
    public function addToQueue(array $urlArray): void
    {
        foreach ($urlArray as $url) {
            $url = $this->urlEncode($url);
            if ($this->urlValidate($url)) {
                $this->queue[] = $url;
            }
        }
    }

    /**
     * Sitemap URLs discovered but not yet parsed
     *
     * @return array
     */
    protected function getQueue(): array
    {
        $this->queue = array_values(
            array_diff(array_unique(array_merge($this->queue, array_keys($this->sitemaps))), $this->history)
        );
        return $this->queue;
    }

    /**
     * Request the body content of URL
     *
     * @param string|null $url
     * @return mixed Raw body content
     * @throws SitemapParserException
     * @throws TransferException
     */
    protected function getContent(?string $url = null)
    {
        $this->currentURL = $this->urlEncode($url ?: $this->currentURL);
        if (!$this->urlValidate($this->currentURL)) {
            throw new SitemapParserException(SitemapParserException::INVALID_URL);
        }
        try {
            $customConfig = [
                CURLOPT_USERAGENT => $this->userAgent
            ];

            $client = new CurlRequest();
            $client->url = $this->currentURL;

            $result = $client->sendGet(false, false, $customConfig);

            if (empty($result)) {
                throw new TransferException(TransferException::INVALID_RESPONSE, 0);
            }

            return $result;
        } catch (Exception $e) {
            throw new TransferException(TransferException::UNABLE_FETCH_CONTENT, 0, $e);
        }
    }

    /**
     * Cleanup between each parse
     *
     * @return void
     */
    protected function clean(): void
    {
        $this->sitemaps = [];
        $this->urls = [];
    }

    /**
     * Validate URL arrays and add them to their corresponding arrays
     *
     * @param string $type sitemap|url
     * @param array $array Tag array
     * @return bool
     */
    protected function addArray(string $type, array $array): bool
    {
        if (!isset($array['loc'])) {
            return false;
        }
        $array['loc'] = $this->urlEncode(trim($array['loc']));

        if ($this->urlValidate($array['loc'])) {
            switch ($type) {
                case self::XML_TAG_SITEMAP:
                    $this->sitemaps[$array['loc']] = $this->fixMissingTags(['lastmod'], $array);
                    return true;
                case self::XML_TAG_URL:
                    $this->urls[$array['loc']] = $this->fixMissingTags(['lastmod'], $array);
                    return true;
            }
        }
        return false;
    }

    /**
     * Check for missing values and set them to null
     *
     * @param array $tags Tags check if exists
     * @param array $array Array to check
     * @return array
     */
    protected function fixMissingTags(array $tags, array $array): array
    {
        foreach ($tags as $tag) {
            if (empty($array[$tag])) {
                $array[$tag] = null;
            }
        }
        return $array;
    }

    /**
     * Generate the SimpleXMLElement object if the XML is valid
     *
     * @param mixed $xml
     * @return SimpleXMLElement|false
     */
    protected function generateXMLObject($xml)
    {
        // strip XML comments from files
        // if they occur at the beginning of the file it will invalidate the XML
        // this occurs with certain versions of Yoast
        $xml = preg_replace('/\s*\<\!\-\-((?!\-\-\>)[\s\S])*\-\-\>\s*/', '', (string)$xml);
        try {
            libxml_use_internal_errors(true);
            return new SimpleXMLElement($xml, LIBXML_NOCDATA);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Parse line separated text string
     *
     * @param ?string $string
     * @return bool
     */
    protected function parseString(?string $string): bool
    {
        if (!isset($this->config['strict']) || $this->config['strict'] !== false) {
            // Strings are not part of any documented sitemap standard
            return false;
        }
        $array = array_filter(array_map('trim', mb_split('\r\n|\n|\r', $string)));
        foreach ($array as $line) {
            if ($this->isSitemapURL($line)) {
                $this->addArray(self::XML_TAG_SITEMAP, ['loc' => $line]);
                continue;
            }
            $this->addArray(self::XML_TAG_URL, ['loc' => $line]);
        }
        return true;
    }

    /**
     * Check if the URL may contain Sitemap
     *
     * @param string $url
     * @return bool
     */
    protected function isSitemapURL(string $url): bool
    {
        $path = parse_url($this->urlEncode($url), PHP_URL_PATH);
        return $this->urlValidate($url) && (
                mb_substr($path, -mb_strlen(self::XML_EXTENSION) - 1) == '.' . self::XML_EXTENSION ||
                mb_substr($path, -mb_strlen(self::XML_EXTENSION_COMPRESSED) - 1) == '.' . self::XML_EXTENSION_COMPRESSED
            );
    }

    /**
     * Convert object to array recursively
     *
     * @param $object
     * @return array|mixed
     */
    protected function objectToArray($object)
    {
        if (is_object($object) || is_array($object)) {
            $ret = (array)$object;

            foreach ($ret as &$item) {
                $item = $this->objectToArray($item);
            }

            return $ret;
        } else {
            return $object;
        }
    }

    /**
     * Parse Json object
     *
     * @param string $type
     * @param SimpleXMLElement $json
     * @return bool
     * @throws SitemapParserException
     * @throws TransferException
     */
    protected function parseJson(string $type, SimpleXMLElement $json): bool
    {
        if (!isset($json->$type)) {
            return false;
        }

        $nameSpaces = $json->getDocNamespaces();

        if (!empty($nameSpaces)) {
            foreach ($json->$type as $node) {
                $tags = ["namespaces" => []];

                foreach ($nameSpaces as $nameSpace => $value) {
                    if ($nameSpace != "") {
                        $tags["namespaces"] = array_merge(
                            $tags["namespaces"],
                            [
                                $nameSpace => $this->objectToArray(
                                    $node->children($nameSpace, true)
                                )
                            ]
                        );
                    } else {
                        $tags = array_merge($tags, (array)$node);
                    }
                }
                if (self::XML_TAG_URL == $type) {
                    $headersBlocks = $this->parsePage($tags['loc']);
                    if (!empty($headersBlocks)) {
                        $tags = array_merge($tags, $headersBlocks);

                    }
                }

                $this->addArray($type, $tags);
            }
        } else {
            foreach ($json->$type as $node) {
                $this->addArray($type, (array)$node);
            }
        }

        return true;
    }

    /**
     * Receive content from URL
     * @param string $url
     * @return array
     * @throws SitemapParserException
     * @throws TransferException
     */
    private function parsePage(string $url): array
    {
        $array = [];
        $htmlString = $this->getContent($url);
        preg_match('/<h1[^>]*>\s*(.*?)\s*<\/h1>/i', $htmlString, $mathesH1);
        if (!empty($mathesH1[1])) {
            $array['h1'] = $mathesH1[1];
        }
        preg_match('/<h2[^>]*>\s*(.*?)\s*<\/h2>/i', $htmlString, $mathesH2);
        if (!empty($mathesH2[1])) {
            $array['h2'] = $mathesH2[1];
        }

        return $array;
    }
}
