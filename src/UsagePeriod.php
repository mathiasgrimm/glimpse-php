<?php

namespace GlimpseImg;

use DateTimeImmutable;

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
            from: new DateTimeImmutable((string) data_get($data, 'from')),
            to: new DateTimeImmutable((string) data_get($data, 'to')),
        );
    }
}
