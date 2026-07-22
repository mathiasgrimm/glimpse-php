<?php

namespace MathiasGrimm\GlimpsePhp;

final readonly class ImageResult
{
    /**
     * @param  float|null  $psnr  Peak signal-to-noise ratio in decibels between the
     *                            input and the output, returned by optimize and convert.
     *                            Higher means closer to the input. Null when the API
     *                            does not send the field (resize, thumbnail) or when
     *                            the loss cannot be measured.
     */
    public function __construct(
        public string $bytes,
        public string $format,
        public string $mimeType,
        public int $size,
        public int $width,
        public int $height,
        public ?float $psnr = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        $psnr = data_get($data, 'psnr');

        return new self(
            bytes: (string) base64_decode((string) data_get($data, 'output.data'), true),
            format: (string) data_get($data, 'format'),
            mimeType: (string) data_get($data, 'mime_type'),
            size: (int) data_get($data, 'size'),
            width: (int) data_get($data, 'width'),
            height: (int) data_get($data, 'height'),
            psnr: $psnr === null ? null : (float) $psnr,
        );
    }
}
