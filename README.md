<p align="center">
  <a href="https://glimpseimg.com"><img src="art/banner.avif" alt="glimpse: ship smaller images, skip the toolchain" width="100%"></a>
</p>

<p align="center">
  Ship smaller images. Skip the toolchain.<br>
  Convert, optimize, resize, thumbnail and analyze images from any PHP application.<br>
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

Shipping images means wrangling ImageMagick, libvips, mozjpeg, cwebp and avifenc: compiled binaries that differ between your laptop, your teammate's laptop, and production. **glimpse-php replaces all of them with one method call**, with zero binary dependencies. The heavy lifting happens on the [Glimpse API](https://glimpseimg.com): stateless, nothing stored, your bytes never linger. You require a single package and go:

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

That is the whole integration: no extensions to compile, no binaries to pin, no queue of shell-outs to babysit. Every method returns typed, immutable results.

## Documentation

The full documentation lives at **[glimpseimg.com/docs/sdk](https://glimpseimg.com/docs/sdk)**:

- [Installation](https://glimpseimg.com/docs/sdk/installation): requirements and optional extensions.
- [Authentication](https://glimpseimg.com/docs/sdk/authentication): building the client and verifying tokens.
- [Methods](https://glimpseimg.com/docs/sdk/methods): every method and the typed objects it returns.
- [Laravel](https://glimpseimg.com/docs/sdk/laravel): container wiring and `Http::fake()` in tests.
- [Errors](https://glimpseimg.com/docs/sdk/errors): the exception hierarchy.

Upgrading from v1? The [upgrade guide](https://glimpseimg.com/docs/sdk/upgrade) covers the move from array returns to typed objects.

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
  <sub>Prefer the terminal? <a href="https://github.com/mathiasgrimm/glimpse-cli"><strong>glimpse-cli</strong></a> is the first-party CLI: the same API from your shell and CI, no PHP code required.<br>
  Grab a free API key at <a href="https://glimpseimg.com"><strong>glimpseimg.com</strong></a> (<strong>Settings → API Tokens</strong>).</sub>
</p>
