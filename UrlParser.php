<?php

namespace parsers;

/**
 * Trait UrlParser
 *
 */
trait UrlParser
{
    /**
     * URL encoder according to RFC 3986
     * Returns a string containing the encoded URL with disallowed characters converted to their percentage encodings.
     * @link http://publicmind.in/blog/url-encoding/
     *
     * @param string $url
     * @return string
     */
    protected function urlEncode(string $url): string
    {
        $reserved = [
            ":" => '!%3A!ui',
            "/" => '!%2F!ui',
            "?" => '!%3F!ui',
            "#" => '!%23!ui',
            "[" => '!%5B!ui',
            "]" => '!%5D!ui',
            "@" => '!%40!ui',
            "!" => '!%21!ui',
            "$" => '!%24!ui',
            "&" => '!%26!ui',
            "'" => '!%27!ui',
            "(" => '!%28!ui',
            ")" => '!%29!ui',
            "*" => '!%2A!ui',
            "+" => '!%2B!ui',
            "," => '!%2C!ui',
            ";" => '!%3B!ui',
            "=" => '!%3D!ui',
            "%" => '!%25!ui'
        ];
        return preg_replace(array_values($reserved), array_keys($reserved), rawurlencode($url));
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return bool
     */
    protected function urlValidate(string $url): bool
    {
        return (
            filter_var($url, FILTER_VALIDATE_URL) &&
            ($parsed = parse_url($url)) !== false &&
            $this->urlValidateHost($parsed['host']) &&
            $this->urlValidateScheme($parsed['scheme'])
        );
    }

    /**
     * Validate host name
     *
     * @link http://stackoverflow.com/questions/1755144/how-to-validate-domain-name-in-php
     *
     * @param  string $host
     * @return bool
     */
    protected static function urlValidateHost(string $host): bool
    {
        return (
            preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $host) //valid chars check
            && preg_match("/^.{1,253}$/", $host) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $host) //length of each label
        );
    }

    /**
     * Validate URL scheme
     *
     * @param  string $scheme
     * @return bool
     */
    protected static function urlValidateScheme(string $scheme): bool
    {
        return in_array($scheme, [
                'http',
                'https',
            ]
        );
    }
}
