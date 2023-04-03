<?php

namespace app\src\exceptions;


use Exception;


/**
 * SitemapParserException class
 *
 */
class SitemapParserException extends Exception
{
    public const INVALID_URL = 'Invalid URL';
    public const UNABLE_SET_CHARECTER_TO = 'Unable to set internal character encoding to ';

    /**
     * Returns: string the user-friendly name of this exception
     * @return string
     */
    public function getName(): string
    {
        return static::class;
    }
}