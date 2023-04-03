<?php

namespace app\src;

use DOMDocument;

trait HTMLParser
{
    /**
     * @param string $pattern
     * @param string $htmlString
     * @return string|null
     */
    protected function getValueByRegularExpression(string $pattern, string $htmlString): ?string
    {
        preg_match($pattern, $htmlString, $matches);
        if (!empty($matches[1])) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param string $htmlString
     * @return null[]
     */
    protected function getValueByXmlDomParser(string $htmlString): array
    {
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadHTML($htmlString);
        $h1Element = $xmlDoc->getElementsByTagName('h1')[0];
        $h2Element = $xmlDoc->getElementsByTagName('h1')[0];
        $array = [
            'h1' => !is_null($h1Element) ? $h1Element->nodeValue : null,
            'h2' => !is_null($h2Element) ? $h2Element->nodeValue : null
        ];
        unset($xmlDoc, $h1Element, $h2Element);
        return $array;
    }
}