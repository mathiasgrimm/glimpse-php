<?php

namespace GlimpseImg;

final readonly class ImageInfo
{
    /**
     * @param  array<string, int>  $channelDepths  The bit depth per channel (red, green, blue, and alpha when present).
     * @param  array<string, array{x: float, y: float}>  $chromaticity  The chromaticity primaries and white point.
     * @param  array<string, array{min: float, max: float, mean: float, standard_deviation: float, kurtosis: float, skewness: float}>  $statistics  Per-channel statistics normalized to 0-1 (kurtosis and skewness are dimensionless).
     * @param  array<string, string>  $properties  The raw properties embedded in the file, including exif.
     */
    public function __construct(
        public string $format,
        public string $mimeType,
        public int $width,
        public int $height,
        public ?string $type,
        public ?string $colorspace,
        public int $depth,
        public array $channelDepths,
        public int $size,
        public ImageResolution $resolution,
        public ?string $units,
        public float $gamma,
        public ?string $interlace,
        public ?string $compression,
        public int $compressionQuality,
        public ?string $orientation,
        public ?string $renderingIntent,
        public int $iterations,
        public int $colors,
        public array $chromaticity,
        public string $backgroundColor,
        public string $borderColor,
        public int $frames,
        public bool $hasAlpha,
        public array $statistics,
        public array $properties,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): self
    {
        return new self(
            format: (string) data_get($data, 'format'),
            mimeType: (string) data_get($data, 'mime_type'),
            width: (int) data_get($data, 'width'),
            height: (int) data_get($data, 'height'),
            type: self::stringOrNull(data_get($data, 'type')),
            colorspace: self::stringOrNull(data_get($data, 'colorspace')),
            depth: (int) data_get($data, 'depth'),
            channelDepths: self::map(data_get($data, 'channel_depths')),
            size: (int) data_get($data, 'size'),
            resolution: new ImageResolution(
                x: (float) data_get($data, 'resolution.x'),
                y: (float) data_get($data, 'resolution.y'),
            ),
            units: self::stringOrNull(data_get($data, 'units')),
            gamma: (float) data_get($data, 'gamma'),
            interlace: self::stringOrNull(data_get($data, 'interlace')),
            compression: self::stringOrNull(data_get($data, 'compression')),
            compressionQuality: (int) data_get($data, 'compression_quality'),
            orientation: self::stringOrNull(data_get($data, 'orientation')),
            renderingIntent: self::stringOrNull(data_get($data, 'rendering_intent')),
            iterations: (int) data_get($data, 'iterations'),
            colors: (int) data_get($data, 'colors'),
            chromaticity: self::map(data_get($data, 'chromaticity')),
            backgroundColor: (string) data_get($data, 'background_color'),
            borderColor: (string) data_get($data, 'border_color'),
            frames: (int) data_get($data, 'frames'),
            hasAlpha: (bool) data_get($data, 'has_alpha'),
            statistics: self::map(data_get($data, 'statistics')),
            properties: self::map(data_get($data, 'properties')),
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function map(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
