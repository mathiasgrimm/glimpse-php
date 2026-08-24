<?php

namespace MathiasGrimm\GlimpsePhp;

enum ImageFormat: string
{
    case Jpg = 'jpg';
    case Png = 'png';
    case Webp = 'webp';
    case Gif = 'gif';
    case Avif = 'avif';

    /**
     * Read by the API (info, analyze, and convert as the source), never
     * written: not a convert target, and not accepted by optimize, resize,
     * or thumbnail, which keep the source format.
     */
    case Heic = 'heic';

    /**
     * The ftyp major brands of a HEIF-family file that hold a single HEVC
     * image, plus the generic `mif1` brand that Android and libheif-based
     * tools write for the same thing.
     *
     * @var list<string>
     */
    private const HEIC_BRANDS = ['heic', 'heix', 'heim', 'heis', 'mif1'];

    /**
     * The major brands of image sequences (HEVC or generic), which the API
     * does not take.
     *
     * @var list<string>
     */
    private const SEQUENCE_BRANDS = ['hevc', 'hevx', 'msf1'];

    public static function fromExtension(string $extension): ?self
    {
        $extension = strtolower($extension);

        return self::tryFrom(match ($extension) {
            'jpeg' => 'jpg',
            'heif' => 'heic',
            default => $extension,
        });
    }

    /**
     * Detect the format from raw image bytes, or null when the bytes are
     * not a supported image. Mirrors the API's ImageFormat::tryFromBinary
     * in name and contract, but sniffs magic numbers instead of finfo on
     * purpose: the SDK runs on arbitrary machines, and finfo needs a
     * libmagic recent enough to know AVIF and HEIC.
     */
    public static function tryFromBinary(string $binary): ?self
    {
        return match (true) {
            str_starts_with($binary, "\xFF\xD8\xFF") => self::Jpg,
            str_starts_with($binary, "\x89PNG\r\n\x1A\n") => self::Png,
            str_starts_with($binary, 'GIF87a'), str_starts_with($binary, 'GIF89a') => self::Gif,
            str_starts_with($binary, 'RIFF') && substr($binary, 8, 4) === 'WEBP' => self::Webp,
            substr($binary, 4, 4) === 'ftyp' => self::fromFtypBrands($binary),
            default => null,
        };
    }

    /**
     * Whether the API reads the format but never writes it. True for HEIC:
     * send it to convert (as the source), info, or analyze, not to
     * optimize, resize, or thumbnail, and never as a convert target.
     */
    public function isDecodeOnly(): bool
    {
        return $this === self::Heic;
    }

    /**
     * The format of a HEIF-family container, decided by its ftyp brands
     * the way the API decides it. The major brand settles the usual cases
     * (`avif` and `avis` are AVIF, the HEVC still brands are HEIC, the
     * sequence brands are not images the API takes); the generic `mif1`
     * brand can wrap either codec, so its compatible brands are walked and
     * an AV1 brand anywhere wins, otherwise it is HEIC.
     */
    private static function fromFtypBrands(string $binary): ?self
    {
        $major = substr($binary, 8, 4);

        if ($major === 'avif' || $major === 'avis') {
            return self::Avif;
        }

        if (in_array($major, self::SEQUENCE_BRANDS, true)) {
            return null;
        }

        if (! in_array($major, self::HEIC_BRANDS, true)) {
            return null;
        }

        if ($major === 'mif1' && array_intersect(self::compatibleBrands($binary), ['avif', 'avis']) !== []) {
            return self::Avif;
        }

        return self::Heic;
    }

    /**
     * The compatible brands an ftyp box declares: a 4-byte size, `ftyp`,
     * the major brand at 8, a minor version at 12 (a number, deliberately
     * skipped so its bytes can never look like a brand), then the brands
     * from 16 to the declared end of the box, capped well past any real
     * ftyp box in case the size field lies.
     *
     * @return list<string>
     */
    private static function compatibleBrands(string $binary): array
    {
        /** @var array{1: int} $size */
        $size = unpack('N', substr($binary, 0, 4));

        $end = min($size[1], strlen($binary), 4096);
        $brands = [];

        for ($offset = 16; $offset + 4 <= $end; $offset += 4) {
            $brands[] = substr($binary, $offset, 4);
        }

        return $brands;
    }
}
