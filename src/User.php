<?php

namespace GlimpseImg;

use DateTimeImmutable;

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
            createdAt: new DateTimeImmutable((string) data_get($data, 'created_at')),
        );
    }
}
