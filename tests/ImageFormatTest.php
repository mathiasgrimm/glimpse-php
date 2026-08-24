<?php

use MathiasGrimm\GlimpsePhp\ImageFormat;
use MathiasGrimm\GlimpsePhp\Tests\Fixtures\Images;

/**
 * An ISOBMFF ftyp box followed by padding: the 4-byte size, `ftyp`, the
 * major brand, a minor version, and the compatible brands.
 */
function ftyp(string $major, string ...$compatible): string
{
    $box = 'ftyp'.$major."\x00\x00\x00\x00".implode('', $compatible);

    return pack('N', strlen($box) + 4).$box.str_repeat("\x00", 32);
}

test('tryFromBinary detects each supported format by its magic numbers', function () {
    expect(ImageFormat::tryFromBinary(Images::jpg()))->toBe(ImageFormat::Jpg)
        ->and(ImageFormat::tryFromBinary(Images::png()))->toBe(ImageFormat::Png)
        ->and(ImageFormat::tryFromBinary('GIF89a'.str_repeat("\x00", 20)))->toBe(ImageFormat::Gif)
        ->and(ImageFormat::tryFromBinary('GIF87a'.str_repeat("\x00", 20)))->toBe(ImageFormat::Gif)
        ->and(ImageFormat::tryFromBinary('RIFF'."\x24\x00\x00\x00".'WEBPVP8 '))->toBe(ImageFormat::Webp)
        ->and(ImageFormat::tryFromBinary("\x00\x00\x00\x20ftypavifavifmif1"))->toBe(ImageFormat::Avif)
        ->and(ImageFormat::tryFromBinary("\x00\x00\x00\x2Cftypavisavifavis"))->toBe(ImageFormat::Avif)
        ->and(ImageFormat::tryFromBinary(ftyp('heic', 'mif1', 'heic')))->toBe(ImageFormat::Heic)
        ->and(ImageFormat::tryFromBinary(ftyp('heix', 'mif1', 'heix')))->toBe(ImageFormat::Heic);
});

test('tryFromBinary resolves a generic heif container by its compatible brands, like the API', function () {
    // `mif1` is what Android and libheif-based tools write for HEVC images,
    // but the same brand can wrap AV1; an AV1 brand anywhere wins.
    expect(ImageFormat::tryFromBinary(ftyp('mif1', 'mif1', 'heic')))->toBe(ImageFormat::Heic)
        ->and(ImageFormat::tryFromBinary(ftyp('mif1')))->toBe(ImageFormat::Heic)
        ->and(ImageFormat::tryFromBinary(ftyp('mif1', 'mif1', 'avif', 'miaf')))->toBe(ImageFormat::Avif);
});

test('tryFromBinary returns null for image sequences and unknown containers', function () {
    expect(ImageFormat::tryFromBinary(ftyp('hevc', 'mif1', 'msf1', 'hevc')))->toBeNull()
        ->and(ImageFormat::tryFromBinary(ftyp('hevx', 'mif1', 'msf1')))->toBeNull()
        ->and(ImageFormat::tryFromBinary(ftyp('msf1', 'mif1', 'msf1')))->toBeNull()
        ->and(ImageFormat::tryFromBinary(ftyp('isom', 'isom', 'mp42')))->toBeNull();
});

test('tryFromBinary returns null for unsupported bytes', function () {
    expect(ImageFormat::tryFromBinary('plain text'))->toBeNull()
        ->and(ImageFormat::tryFromBinary(''))->toBeNull()
        ->and(ImageFormat::tryFromBinary('RIFF'."\x24\x00\x00\x00".'WAVEfmt '))->toBeNull()
        ->and(ImageFormat::tryFromBinary('%PDF-1.7'))->toBeNull();
});

test('fromExtension normalizes the aliases and the case', function () {
    expect(ImageFormat::fromExtension('jpeg'))->toBe(ImageFormat::Jpg)
        ->and(ImageFormat::fromExtension('JPG'))->toBe(ImageFormat::Jpg)
        ->and(ImageFormat::fromExtension('heic'))->toBe(ImageFormat::Heic)
        ->and(ImageFormat::fromExtension('HEIC'))->toBe(ImageFormat::Heic)
        ->and(ImageFormat::fromExtension('heif'))->toBe(ImageFormat::Heic)
        ->and(ImageFormat::fromExtension('bmp'))->toBeNull();
});

test('only heic is decode-only', function () {
    $decodeOnly = array_values(array_filter(ImageFormat::cases(), fn (ImageFormat $format) => $format->isDecodeOnly()));

    expect($decodeOnly)->toBe([ImageFormat::Heic]);
});
