<?php

namespace parsers\exceptions;

/**
 * TransferException class
 *
 */
class TransferException extends SitemapParserException
{
    public const  UNABLE_FETCH_CONTENT  = 'Unable to fetch URL contents';
    public const  INVALID_RESPONSE  = 'Invalid response';
}