<?php

namespace MathiasGrimm\GlimpsePhp;

use Closure;
use Illuminate\Http\Client\Factory;

final class Glimpse
{
    /**
     * @param  (Closure(): ?string)|string|null  $token
     */
    public static function createClient(
        Closure|string|null $token = null,
        string $baseUrl = Client::DEFAULT_BASE_URL,
        ?Factory $http = null,
        ?string $userAgent = null,
    ): Client {
        return new Client($http ?? new Factory, $token, $baseUrl, $userAgent);
    }
}
