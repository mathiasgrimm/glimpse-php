# glimpse-php

The official PHP SDK for [glimpseimg.com](https://glimpseimg.com), the image API for developers. Convert, optimize, resize, thumbnail, inspect, and analyze images from any PHP application.

## Installation

```bash
composer require glimpseimg/glimpse-php
```

Requires PHP 8.2+ and works alongside Laravel 10, 11, or 12 (the SDK depends on `illuminate/http` but needs no framework).

## Usage

Create a client with your API token (generate one in your glimpseimg.com account settings):

```php
use GlimpseImg\Client;
use GlimpseImg\ImageFormat;
use Illuminate\Http\Client\Factory;

$glimpse = new Client(new Factory, 'your-api-token');
```

In a Laravel app, resolve the factory from the container instead so `Http::fake()` works in your tests:

```php
$glimpse = new Client(app(Factory::class), config('services.glimpse.token'));
```

### Convert

AVIF is the recommended target format; it produces the smallest files at equivalent visual quality:

```php
$result = $glimpse->convert(file_get_contents('photo.jpg'), ImageFormat::Avif);

file_put_contents('photo.avif', $result->bytes);

$result->format;   // "avif"
$result->mimeType; // "image/avif"
$result->size;     // bytes
$result->width;    // px
$result->height;   // px
```

### Optimize, resize, thumbnail

```php
$glimpse->optimize($bytes, quality: 85);
$glimpse->resize($bytes, width: 1280);                     // aspect ratio preserved, never upscales
$glimpse->thumbnail($bytes, width: 300, height: 300);
```

### Inspect and analyze

```php
$info = $glimpse->info($bytes); // format, dimensions, colorspace, frames, exif, ...

// Predict converted sizes from metadata alone; no image bytes are sent.
$estimates = $glimpse->analyze(ImageFormat::Jpg, size: 2_500_000, width: 4032, height: 3024);
```

`GlimpseImg\SampleProbe` (needs ext-imagick or ext-gd) and `GlimpseImg\FrameCounter` compute the optional `sample_bpp` and `frames` inputs locally to make analyze estimates far tighter.

### Usage summary

```php
$usage = $glimpse->usage(); // month-to-date operations, bytes saved, average reduction
```

## Errors

All errors extend `GlimpseImg\ApiException`:

- `GlimpseImg\AuthException`: missing or invalid token (401)
- `GlimpseImg\ValidationException`: invalid input (422), with the field errors on `$e->errors`
- `GlimpseImg\ApiException`: any other API failure

Connection failures throw `Illuminate\Http\Client\ConnectionException`.

## Limits

- Input images up to 15 MiB, formats: jpg, png, webp, gif, avif
- Resize and thumbnail dimensions up to 10000 px

## Development

```bash
composer install
composer test   # pint, phpstan, pest
```

## License

MIT
