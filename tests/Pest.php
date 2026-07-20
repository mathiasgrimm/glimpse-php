<?php

use MathiasGrimm\GlimpsePhp\Tests\Fixtures\Images;

/**
 * A canned successful analyze-endpoint response envelope.
 *
 * @return array{data: list<array<string, mixed>>}
 */
function fakeAnalyzeResponse(): array
{
    return ['data' => [
        ['format' => 'jpg', 'size' => 812000, 'saved' => 1688000, 'saved_percent' => 67.5, 'quality' => 85],
        ['format' => 'png', 'size' => 6100000, 'saved' => -3600000, 'saved_percent' => -144.0, 'quality' => null],
        ['format' => 'webp', 'size' => 590000, 'saved' => 1910000, 'saved_percent' => 76.4, 'quality' => 85],
        ['format' => 'avif', 'size' => 470000, 'saved' => 2030000, 'saved_percent' => 81.2, 'quality' => 85],
    ]];
}

/**
 * A canned successful info-endpoint response envelope covering the full
 * API shape, including the nullable fields and the map-valued fields.
 * Overrides are merged on top of the defaults, so a test can null out or
 * replace individual keys.
 *
 * @param  array<string, mixed>  $overrides
 * @return array{data: array<string, mixed>}
 */
function fakeInfoResponse(array $overrides = []): array
{
    return ['data' => $overrides + [
        'format' => 'jpg',
        'mime_type' => 'image/jpeg',
        'width' => 1280,
        'height' => 720,
        'type' => 'TRUECOLOR',
        'colorspace' => 'SRGB',
        'depth' => 8,
        'channel_depths' => ['red' => 8, 'green' => 8, 'blue' => 8],
        'size' => 812000,
        'resolution' => ['x' => 72.0, 'y' => 72.0],
        'units' => 'PIXELS_PER_INCH',
        'gamma' => 0.4545,
        'interlace' => 'NONE',
        'compression' => 'JPEG',
        'compression_quality' => 92,
        'orientation' => 'TOP_LEFT',
        'rendering_intent' => 'PERCEPTUAL',
        'iterations' => 0,
        'colors' => 187028,
        'chromaticity' => [
            'red' => ['x' => 0.64, 'y' => 0.33],
            'green' => ['x' => 0.3, 'y' => 0.6],
            'blue' => ['x' => 0.15, 'y' => 0.06],
            'white' => ['x' => 0.3127, 'y' => 0.329],
        ],
        'background_color' => 'srgb(255,255,255)',
        'border_color' => 'srgb(223,223,223)',
        'frames' => 1,
        'has_alpha' => false,
        'statistics' => [
            'red' => ['min' => 0.0, 'max' => 1.0, 'mean' => 0.4823, 'standard_deviation' => 0.2511, 'kurtosis' => -1.1204, 'skewness' => 0.1093],
        ],
        'properties' => ['exif:Make' => 'Canon', 'jpeg:colorspace' => '2'],
    ]];
}

/**
 * A canned successful transform-endpoint response envelope.
 *
 * @return array{data: array<string, mixed>}
 */
function fakeTransformResponse(string $format = 'jpg', string $mimeType = 'image/jpeg'): array
{
    return ['data' => [
        'output' => ['type' => 'BASE64', 'data' => Images::JPG_BASE64],
        'format' => $format,
        'mime_type' => $mimeType,
        'size' => strlen(Images::jpg()),
        'width' => 1280,
        'height' => 720,
    ]];
}
