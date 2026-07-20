<?php

namespace GlimpseImg;

class RateLimitException extends ApiException
{
    /**
     * @param  ?int  $retryAfterSeconds  Seconds from the Retry-After header, or null when the API sent none
     */
    public function __construct(string $message, public readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct($message);
    }
}
