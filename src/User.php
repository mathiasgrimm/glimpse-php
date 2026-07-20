<?php

namespace MathiasGrimm\GlimpsePhp;

use DateTimeImmutable;
use Exception;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public DateTimeImmutable $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        return new self(
            id: (int) data_get($data, 'id'),
            name: (string) data_get($data, 'name'),
            email: (string) data_get($data, 'email'),
            createdAt: self::date($data, 'created_at'),
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
