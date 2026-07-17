<p align="center">
  <a href="https://glimpseimg.com"><img src="art/banner.avif" alt="glimpse: ship smaller images, skip the toolchain" width="100%"></a>
</p>

<p align="center">
  Ship smaller images. Skip the toolchain.<br>
  Convert, optimize, resize, thumbnail, analyze and inspect images from any PHP application.<br>
  The official PHP SDK for <a href="https://glimpseimg.com"><strong>glimpseimg.com</strong></a>, the image API for developers.
</p>

<p align="center">
  <a href="https://packagist.org/packages/mathiasgrimm/glimpse-php"><img src="https://img.shields.io/packagist/v/mathiasgrimm/glimpse-php?style=flat-square&label=packagist" alt="Latest Version on Packagist"></a>
  <a href="https://github.com/mathiasgrimm/glimpse-php/actions/workflows/ci.yml"><img src="https://img.shields.io/github/actions/workflow/status/mathiasgrimm/glimpse-php/ci.yml?branch=main&label=tests&style=flat-square" alt="Tests"></a>
  <a href="https://packagist.org/packages/mathiasgrimm/glimpse-php"><img src="https://img.shields.io/packagist/dt/mathiasgrimm/glimpse-php?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/mathiasgrimm/glimpse-php"><img src="https://img.shields.io/packagist/dependency-v/mathiasgrimm/glimpse-php/php?style=flat-square&label=php" alt="PHP Version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/mathiasgrimm/glimpse-php?style=flat-square" alt="License"></a>
</p>

---

Shipping images means wrangling ImageMagick, libvips, mozjpeg, cwebp and avifenc: compiled binaries that differ between your laptop, your teammate's laptop, and production. **glimpse-php replaces all of them with one method call.** The heavy lifting happens on the [Glimpse API](https://glimpseimg.com): stateless, nothing stored, your bytes never linger. You require a single package and go:

```bash
composer require mathiasgrimm/glimpse-php
```

```php
use GlimpseImg\Client;
use GlimpseImg\ImageFormat;
use Illuminate\Http\Client\Factory;

$glimpse = new Client(new Factory, 'your-api-token');

$avif = $glimpse->convert(file_get_contents('photo.jpg'), ImageFormat::Avif);
file_put_contents('photo.avif', $avif->bytes);
```

That is the whole integration: no extensions to compile, no binaries to pin, no queue of shell-outs to babysit. Want to know what a conversion will buy you *before* you convert? [`analyze()`](#analyze) predicts the output size for every format **without uploading your image**.

## Why glimpse-php

- **Zero image binaries.** No ImageMagick, no libvips, no format-specific encoders to install, pin, or debug across machines. If it runs PHP 8.2, it runs glimpse.
- **One method per job.** `convert()`, `optimize()`, `resize()`, `thumbnail()`, `analyze()`, `info()` and `usage()` are small, predictable methods that do one thing well.
- **5 output formats.** JPG, PNG, WebP, GIF and AVIF, with AVIF producing the smallest files at equivalent visual quality.
- **Typed results.** Every transform returns an immutable `ImageResult` with the bytes, format, MIME type, size and dimensions. No array-shape guessing.
- **Know before you convert.** `analyze()` predicts per-format savings from metadata and an optional local sample probe. The image itself is never uploaded.
- **Framework-friendly, framework-free.** Built on `illuminate/http`, so `Http::fake()` just works in Laravel tests, but the SDK runs in any PHP application.
- **Safe by default.** `resize()` never upscales; the optimizer never returns a file larger than its input.
- **Stateless by design.** One request in, one image out. Nothing is stored server-side.

## Installation

```bash
composer require mathiasgrimm/glimpse-php
```

Requires PHP 8.2+ and works alongside Laravel 10, 11, or 12 (the SDK depends on `illuminate/http` but needs no framework).

Optionally install `ext-imagick` (or `ext-gd`) to sharpen [`analyze()`](#analyze) estimates considerably: `SampleProbe` trial-encodes a small sample of your image locally to measure its actual complexity.

## Authentication

Grab a free API key at [glimpseimg.com](https://glimpseimg.com) (**Settings → API Tokens**). glimpse is free while we finish billing, and there will always be a free tier. Then hand the token to the client:

```php
use GlimpseImg\Client;
use Illuminate\Http\Client\Factory;

$glimpse = new Client(new Factory, 'your-api-token');
```

In a Laravel app, resolve the factory from the container instead so `Http::fake()` works in your tests:

```php
$glimpse = new Client(app(Factory::class), config('services.glimpse.token'));
```

The constructor is flexible about where the token comes from and where the API lives:

```php
new Client($http, 'your-api-token');                          // plain string
new Client($http, fn () => $vault->get('glimpse-token'));     // closure, resolved per request
new Client($http, $token, 'https://staging.example.com/api'); // custom base URL
```

A missing token throws `GlimpseImg\AuthException` at call time, before any HTTP request is made. To check who a token belongs to (or to verify a candidate token without touching the client's own), use `user()`:

```php
$glimpse->user();                    // the account behind the client's token
$glimpse->user('candidate-token');   // verify a different token
```

## Usage

All transform methods take the raw image bytes as a string and return an `ImageResult`. Input formats are verified from the actual bytes, never a filename.

### Convert

AVIF is the recommended target format; it produces the smallest files at equivalent visual quality:

```php
$result = $glimpse->convert($bytes, ImageFormat::Avif);
$result = $glimpse->convert($bytes, ImageFormat::Webp, optimize: true);               // optimizes the converted image
$result = $glimpse->convert($bytes, ImageFormat::Webp, optimize: true, quality: 70);  // lossy re-encode at quality 70

file_put_contents('photo.avif', $result->bytes);

$result->format;   // "avif"
$result->mimeType; // "image/avif"
$result->size;     // bytes
$result->width;    // px
$result->height;   // px
```

Supported formats: `ImageFormat::Jpg`, `Png`, `Webp`, `Gif`, `Avif`. When the target format comes from user input, `ImageFormat::fromExtension('jpeg')` normalizes file extensions and `ImageFormat::tryFromBinary($bytes)` sniffs the format from magic numbers; both return `null` rather than throwing.

`optimize:` runs the converted image through the optimizer chain (the result is never larger than without it). `quality:` (1-100, default 85) requires `optimize:`.

### Optimize

```php
$glimpse->optimize($bytes);               // lossless optimizer chain, keeps the format
$glimpse->optimize($bytes, quality: 70);  // lossy re-encode at quality 70
```

### Resize

Fits the image into a bounding box, preserving aspect ratio and never upscaling. The format is kept:

```php
$glimpse->resize($bytes, width: 1280);
$glimpse->resize($bytes, width: 800, height: 600);
$glimpse->resize($bytes, width: 800, optimize: true, quality: 70);  // resize, then lossy re-encode
```

`optimize:` and `quality:` work the same as on `convert()`.

### Thumbnail

Resize plus lossy re-encode in one pass. API defaults: 300x300 box at quality 60.

```php
$glimpse->thumbnail($bytes);
$glimpse->thumbnail($bytes, width: 150, quality: 50);
```

### Info

```php
$info = $glimpse->info($bytes); // format, dimensions, colorspace, frames, exif, ...
```

### Analyze

Know before you convert: `analyze()` predicts the converted size for every format so you can pick a target *before* spending a conversion. Your image is never uploaded. Only its metadata (format, size, dimensions) plus an optional locally computed sample probe are sent:

```php
$estimates = $glimpse->analyze(ImageFormat::Png, size: 368_947, width: 3200, height: 840);

// [
//   ['format' => 'jpg',  'size' => 149402, 'saved' => 219545, 'saved_percent' => 59.5, 'quality' => 85],
//   ['format' => 'png',  'size' => 165991, 'saved' => 202956, 'saved_percent' => 55.0, 'quality' => null],
//   ['format' => 'webp', 'size' => 83046,  'saved' => 285901, 'saved_percent' => 77.5, 'quality' => 85],
//   ['format' => 'avif', 'size' => 41472,  'saved' => 327475, 'saved_percent' => 88.7, 'quality' => 85],
// ]
```

Estimates are heuristics for picking a target format, not guarantees. They get far tighter when you feed `analyze()` a locally measured sample: `SampleProbe` (needs `ext-imagick` or `ext-gd`) trial-encodes a downscaled copy of the image to measure its actual complexity, and `FrameCounter` counts animation frames (which matters for animated GIF and AVIF):

```php
use GlimpseImg\FrameCounter;
use GlimpseImg\SampleProbe;

$bytes = file_get_contents('photo.jpg');
$probe = (new SampleProbe)->measure($bytes);   // null when neither extension can decode the image
$frames = (new FrameCounter)->count($bytes);   // null when the frame count is unknown

$estimates = $glimpse->analyze(
    ImageFormat::Jpg,
    size: strlen($bytes),
    width: $probe?->width,
    height: $probe?->height,
    sampleBpp: $probe?->sampleBpp,
    frames: $frames,
);
```

### Usage summary

```php
$usage = $glimpse->usage(); // month-to-date operations, bytes saved, average reduction
```

## Errors

All errors extend `GlimpseImg\ApiException`:

| Exception | Thrown when |
| --- | --- |
| `GlimpseImg\AuthException` | Missing or invalid token (401) |
| `GlimpseImg\ValidationException` | Invalid input (422), with the field errors on `$e->errors` |
| `GlimpseImg\ApiException` | Any other API failure |

Connection failures throw `Illuminate\Http\Client\ConnectionException`.

```php
use GlimpseImg\ValidationException;

try {
    $glimpse->convert($bytes, ImageFormat::Avif);
} catch (ValidationException $e) {
    $e->errors; // ['input' => ['The input exceeds the 15 MiB limit.']]
}
```

## Limits

- Input images are capped at 15 MiB. Formats: `jpg`, `png`, `webp`, `gif`, `avif`.
- Resize and thumbnail dimensions are capped at 10000 px.

## Development

```bash
composer install
composer test        # Pint (check), PHPStan, and the Pest suite
```

### Releasing

```bash
make release VERSION=vX.Y.Z
```

Runs the test suite, tags `VERSION`, pushes the tag, and creates the GitHub release. Packagist picks the new tag up through the GitHub webhook.

## License

glimpse-php is open-source software licensed under the [MIT license](LICENSE).

---

<p align="center">
  <sub>Prefer the terminal? <a href="https://github.com/mathiasgrimm/glimpse-cli"><strong>glimpse-cli</strong></a> brings the same API to your shell and CI, no PHP code required.<br>
  Grab a free API key at <a href="https://glimpseimg.com"><strong>glimpseimg.com</strong></a> (<strong>Settings → API Tokens</strong>).</sub>
</p>
