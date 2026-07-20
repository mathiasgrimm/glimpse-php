<?php

namespace GlimpseImg;

class RateLimitException extends ApiException
{
    /**
     * @param  ?int  $retryAfterSeconds  Seconds to wait from the Retry-After header (never negative), or null when the header is missing or unparseable
     */
    public function __construct(string $message, public readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct($message);
    }
}
