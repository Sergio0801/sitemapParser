<?php

namespace app\src;

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

    protected function getValueByXmlDomParser(string $htmlString)
    {
       // $parser = new DOMParser();
    }
}