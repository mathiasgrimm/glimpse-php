<?php

namespace MathiasGrimm\GlimpsePhp;

final readonly class SizeEstimate
{
    public function __construct(
        public string $format,
        public int $size,
        public int $saved,
        public float $savedPercent,
        public ?int $quality,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $quality = data_get($data, 'quality');

        return new self(
            format: (string) data_get($data, 'format'),
            size: (int) data_get($data, 'size'),
            saved: (int) data_get($data, 'saved'),
            savedPercent: (float) data_get($data, 'saved_percent'),
            quality: $quality === null ? null : (int) $quality,
        );
    }
}
