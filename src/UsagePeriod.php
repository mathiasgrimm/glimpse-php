<?php

namespace GlimpseImg;

use DateTimeImmutable;
use Exception;

final readonly class UsagePeriod
{
    public function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        return new self(
            from: self::date($data, 'from'),
            to: self::date($data, 'to'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function date(array $data, string $key): DateTimeImmutable
    {
        $value = data_get($data, $key);

        if (! is_string($value) || $value === '') {
            throw new ApiException("The API response is missing the '{$key}' date.");
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            throw new ApiException("The API response carries an invalid '{$key}' date.");
        }
    }
}
