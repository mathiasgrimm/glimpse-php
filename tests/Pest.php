<?php

use GlimpseImg\Tests\Fixtures\Images;

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
