<?php

use GlimpseImg\ApiException;
use GlimpseImg\AuthException;
use GlimpseImg\Client;
use GlimpseImg\ImageFormat;
use GlimpseImg\ImageInfo;
use GlimpseImg\SizeEstimate;
use GlimpseImg\Tests\Fixtures\Images;
use GlimpseImg\UsageSummary;
use GlimpseImg\User;
use GlimpseImg\ValidationException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;

function fakeHttp(array $responses = []): Factory
{
    $http = new Factory;

    $responses === [] ? $http->fake() : $http->fake($responses);

    return $http;
}

function client(Factory $http, Closure|string|null $token = 'test-token', string $baseUrl = Client::DEFAULT_BASE_URL): Client
{
    return new Client($http, $token, $baseUrl);
}

test('convert posts the base64 envelope and returns a decoded ImageResult', function () {
    $http = fakeHttp(['*/v1/convert' => Factory::response(fakeTransformResponse())]);

    $result = client($http)->convert(Images::png(), ImageFormat::Jpg);

    expect($result->bytes)->toBe(Images::jpg())
        ->and($result->format)->toBe(ImageFormat::Jpg->value)
        ->and($result->mimeType)->toBe('image/jpeg')
        ->and($result->width)->toBe(1280)
        ->and($result->height)->toBe(720);

    $http->assertSent(function (Request $request) {
        return $request->url() === 'https://glimpseimg.com/api/v1/convert'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['input']['type'] === 'BASE64'
            && $request['input']['data'] === Images::PNG_BASE64
            && $request['format'] === ImageFormat::Jpg->value;
    });
});

test('convert sends optimize and quality when given', function () {
    $http = fakeHttp(['*/v1/convert' => Factory::response(fakeTransformResponse())]);

    client($http)->convert(Images::png(), ImageFormat::Jpg, optimize: true, quality: 60);

    $http->assertSent(fn (Request $request) => $request['optimize'] === true
        && $request['quality'] === 60);
});

test('convert omits optimize and quality from the payload by default', function () {
    $http = fakeHttp(['*/v1/convert' => Factory::response(fakeTransformResponse())]);

    client($http)->convert(Images::png(), ImageFormat::Jpg);

    $http->assertSent(fn (Request $request) => ! array_key_exists('optimize', $request->data())
        && ! array_key_exists('quality', $request->data()));
});

test('resize sends optimize and quality when given', function () {
    $http = fakeHttp(['*/v1/resize' => Factory::response(fakeTransformResponse())]);

    client($http)->resize(Images::png(), width: 800, optimize: true, quality: 60);

    $http->assertSent(fn (Request $request) => $request['width'] === 800
        && $request['optimize'] === true
        && $request['quality'] === 60);
});

test('resize omits optimize and quality from the payload by default', function () {
    $http = fakeHttp(['*/v1/resize' => Factory::response(fakeTransformResponse())]);

    client($http)->resize(Images::png(), width: 800);

    $http->assertSent(fn (Request $request) => ! array_key_exists('optimize', $request->data())
        && ! array_key_exists('quality', $request->data()));
});

test('optimize omits quality from the payload when not given', function () {
    $http = fakeHttp(['*/v1/optimize' => Factory::response(fakeTransformResponse())]);

    client($http)->optimize(Images::png());

    $http->assertSent(fn (Request $request) => ! array_key_exists('quality', $request->data()));
});

test('thumbnail sends width, height, and quality when given', function () {
    $http = fakeHttp(['*/v1/thumbnail' => Factory::response(fakeTransformResponse())]);

    client($http)->thumbnail(Images::png(), width: 100, height: 50, quality: 42);

    $http->assertSent(fn (Request $request) => $request['width'] === 100
        && $request['height'] === 50
        && $request['quality'] === 42);
});

test('info returns a typed ImageInfo', function () {
    $http = fakeHttp(['*/v1/info' => Factory::response(fakeInfoResponse())]);

    $info = client($http)->info(Images::png());

    expect($info)->toBeInstanceOf(ImageInfo::class)
        ->and($info->format)->toBe('jpg')
        ->and($info->mimeType)->toBe('image/jpeg')
        ->and($info->width)->toBe(1280)
        ->and($info->height)->toBe(720)
        ->and($info->type)->toBe('TRUECOLOR')
        ->and($info->colorspace)->toBe('SRGB')
        ->and($info->depth)->toBe(8)
        ->and($info->channelDepths)->toBe(['red' => 8, 'green' => 8, 'blue' => 8])
        ->and($info->size)->toBe(812000)
        ->and($info->resolution->x)->toBe(72.0)
        ->and($info->resolution->y)->toBe(72.0)
        ->and($info->units)->toBe('PIXELS_PER_INCH')
        ->and($info->gamma)->toBe(0.4545)
        ->and($info->interlace)->toBe('NONE')
        ->and($info->compression)->toBe('JPEG')
        ->and($info->compressionQuality)->toBe(92)
        ->and($info->orientation)->toBe('TOP_LEFT')
        ->and($info->renderingIntent)->toBe('PERCEPTUAL')
        ->and($info->iterations)->toBe(0)
        ->and($info->colors)->toBe(187028)
        ->and($info->chromaticity['white'])->toBe(['x' => 0.3127, 'y' => 0.329])
        ->and($info->backgroundColor)->toBe('srgb(255,255,255)')
        ->and($info->borderColor)->toBe('srgb(223,223,223)')
        ->and($info->frames)->toBe(1)
        ->and($info->hasAlpha)->toBeFalse()
        ->and($info->statistics['red']['mean'])->toBe(0.4823)
        ->and($info->properties['exif:Make'])->toBe('Canon');
});

test('info preserves null for the nullable metadata fields', function () {
    $http = fakeHttp(['*/v1/info' => Factory::response(fakeInfoResponse([
        'type' => null,
        'colorspace' => null,
        'units' => null,
        'interlace' => null,
        'compression' => null,
        'orientation' => null,
        'rendering_intent' => null,
    ]))]);

    $info = client($http)->info(Images::png());

    expect($info->type)->toBeNull()
        ->and($info->colorspace)->toBeNull()
        ->and($info->units)->toBeNull()
        ->and($info->interlace)->toBeNull()
        ->and($info->compression)->toBeNull()
        ->and($info->orientation)->toBeNull()
        ->and($info->renderingIntent)->toBeNull();
});

test('usage fetches the summary with the configured token', function () {
    $http = fakeHttp(['*/v1/usage' => Factory::response(['data' => [
        'period' => ['from' => '2026-07-01T00:00:00+00:00', 'to' => '2026-07-31T23:59:59+00:00'],
        'operations' => 68,
        'bytes_saved' => 62111321,
        'average_reduction' => 45,
        'by_operation' => ['convert' => 40, 'optimize' => 28],
    ]])]);

    $usage = client($http)->usage();

    expect($usage)->toBeInstanceOf(UsageSummary::class)
        ->and($usage->period->from->format(DATE_ATOM))->toBe('2026-07-01T00:00:00+00:00')
        ->and($usage->period->to->format(DATE_ATOM))->toBe('2026-07-31T23:59:59+00:00')
        ->and($usage->operations)->toBe(68)
        ->and($usage->bytesSaved)->toBe(62111321)
        ->and($usage->averageReduction)->toBe(45)
        ->and($usage->byOperation)->toBe(['convert' => 40, 'optimize' => 28]);

    $http->assertSent(fn (Request $request) => $request->url() === 'https://glimpseimg.com/api/v1/usage'
        && $request->method() === 'GET'
        && $request->hasHeader('Authorization', 'Bearer test-token'));
});

test('usage rejects a response without period dates', function () {
    $http = fakeHttp(['*/v1/usage' => Factory::response(['data' => [
        'operations' => 68,
        'bytes_saved' => 62111321,
        'average_reduction' => 45,
        'by_operation' => ['convert' => 40, 'optimize' => 28],
    ]])]);

    expect(fn () => client($http)->usage())
        ->toThrow(ApiException::class, "The API response is missing the 'from' date.");
});

test('usage rejects a response with a malformed period date', function () {
    $http = fakeHttp(['*/v1/usage' => Factory::response(['data' => [
        'period' => ['from' => '2026-07-01T00:00:00+00:00', 'to' => 'not-a-date'],
        'operations' => 68,
        'bytes_saved' => 62111321,
        'average_reduction' => 45,
        'by_operation' => [],
    ]])]);

    expect(fn () => client($http)->usage())
        ->toThrow(ApiException::class, "The API response carries an invalid 'to' date.");
});

test('user verifies an explicit token without touching the configured one', function () {
    $http = fakeHttp(['*/user' => Factory::response([
        'id' => 7,
        'name' => 'Mathias',
        'email' => 'mathias@example.com',
        'created_at' => '2025-11-03T09:30:00.000000Z',
    ])]);

    $user = client($http)->user('candidate-token');

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->toBe(7)
        ->and($user->name)->toBe('Mathias')
        ->and($user->email)->toBe('mathias@example.com')
        ->and($user->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($user->createdAt->format('Y-m-d H:i:s'))->toBe('2025-11-03 09:30:00');

    $http->assertSent(fn (Request $request) => $request->url() === 'https://glimpseimg.com/api/user'
        && $request->hasHeader('Authorization', 'Bearer candidate-token'));
});

test('user rejects a response without a created_at date', function () {
    $http = fakeHttp(['*/user' => Factory::response([
        'id' => 7,
        'name' => 'Mathias',
        'email' => 'mathias@example.com',
    ])]);

    expect(fn () => client($http)->user('candidate-token'))
        ->toThrow(ApiException::class, "The API response is missing the 'created_at' date.");
});

test('user rejects a response with a malformed created_at date', function () {
    $http = fakeHttp(['*/user' => Factory::response([
        'id' => 7,
        'name' => 'Mathias',
        'email' => 'mathias@example.com',
        'created_at' => 'not-a-date',
    ])]);

    expect(fn () => client($http)->user('candidate-token'))
        ->toThrow(ApiException::class, "The API response carries an invalid 'created_at' date.");
});

test('a missing token fails before any HTTP request', function () {
    $http = fakeHttp();

    expect(fn () => client($http, token: null)->optimize(Images::png()))
        ->toThrow(AuthException::class, 'Not authenticated.');

    $http->assertNothingSent();
});

test('a token closure is resolved on every request', function () {
    $http = fakeHttp(['*/v1/optimize' => Factory::response(fakeTransformResponse())]);

    $tokens = ['first-token', 'second-token'];
    $client = client($http, token: function () use (&$tokens) {
        return array_shift($tokens);
    });

    $client->optimize(Images::png());
    $client->optimize(Images::png());

    $http->assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer first-token'));
    $http->assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer second-token'));
});

test('a token closure returning null fails at call time', function () {
    $http = fakeHttp();

    expect(fn () => client($http, token: fn () => null)->optimize(Images::png()))
        ->toThrow(AuthException::class, 'Not authenticated.');

    $http->assertNothingSent();
});

test('analyze posts metadata only, with no image payload', function () {
    $http = fakeHttp(['*/v1/analyze' => Factory::response(fakeAnalyzeResponse())]);

    $estimates = client($http)->analyze(ImageFormat::Jpg, 2_500_000, 4032, 3024, 80);

    expect($estimates)->toHaveCount(4)
        ->and($estimates[0])->toBeInstanceOf(SizeEstimate::class)
        ->and($estimates[0]->format)->toBe('jpg')
        ->and($estimates[0]->size)->toBe(812000)
        ->and($estimates[0]->saved)->toBe(1688000)
        ->and($estimates[0]->savedPercent)->toBe(67.5)
        ->and($estimates[0]->quality)->toBe(85)
        ->and($estimates[1]->saved)->toBe(-3600000)
        ->and($estimates[1]->savedPercent)->toBe(-144.0)
        ->and($estimates[1]->quality)->toBeNull()
        ->and($estimates[3]->format)->toBe('avif');

    $http->assertSent(function (Request $request) {
        return $request->url() === 'https://glimpseimg.com/api/v1/analyze'
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && ! array_key_exists('input', $request->data())
            && $request['format'] === ImageFormat::Jpg->value
            && $request['size'] === 2_500_000
            && $request['width'] === 4032
            && $request['height'] === 3024
            && $request['quality'] === 80;
    });
});

test('analyze omits dimensions, quality, sample, and frames from the payload when null', function () {
    $http = fakeHttp(['*/v1/analyze' => Factory::response(fakeAnalyzeResponse())]);

    client($http)->analyze(ImageFormat::Png, 1_000_000);

    $http->assertSent(fn (Request $request) => ! array_key_exists('width', $request->data())
        && ! array_key_exists('height', $request->data())
        && ! array_key_exists('quality', $request->data())
        && ! array_key_exists('sample_bpp', $request->data())
        && ! array_key_exists('frames', $request->data()));
});

test('analyze sends the sample bpp when given', function () {
    $http = fakeHttp(['*/v1/analyze' => Factory::response(fakeAnalyzeResponse())]);

    client($http)->analyze(ImageFormat::Jpg, 2_500_000, 4032, 3024, sampleBpp: 0.7066);

    $http->assertSent(fn (Request $request) => $request['sample_bpp'] === 0.7066);
});

test('analyze sends the frame count when given', function () {
    $http = fakeHttp(['*/v1/analyze' => Factory::response(fakeAnalyzeResponse())]);

    client($http)->analyze(ImageFormat::Gif, 24_000, 64, 64, frames: 20);

    $http->assertSent(fn (Request $request) => $request['frames'] === 20);
});

test('a 401 response maps to AuthException', function () {
    $http = fakeHttp(['*/v1/optimize' => Factory::response(['message' => 'Unauthenticated.'], 401)]);

    expect(fn () => client($http)->optimize(Images::png()))
        ->toThrow(AuthException::class, 'Invalid or missing token.');
});

test('a 422 response maps to ValidationException carrying the errors map', function () {
    $http = fakeHttp(['*/v1/convert' => Factory::response([
        'message' => 'The format field is invalid.',
        'errors' => ['format' => ['The format field is invalid.']],
    ], 422)]);

    try {
        client($http)->convert(Images::png(), ImageFormat::Png);
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->getMessage())->toBe('The format field is invalid.')
            ->and($e->errors)->toBe(['format' => ['The format field is invalid.']]);
    }
});

test('other failures map to ApiException with the status code', function () {
    $http = fakeHttp(['*/v1/optimize' => Factory::response(['message' => 'Server Error'], 500)]);

    expect(fn () => client($http)->optimize(Images::png()))
        ->toThrow(ApiException::class, 'API error (500): Server Error');
});

test('a custom base url points requests at a different host', function () {
    $http = fakeHttp(['*/v1/info' => Factory::response(['data' => []])]);

    client($http, baseUrl: 'https://glimpseimg.test/api')->info(Images::png());

    $http->assertSent(fn (Request $request) => $request->url() === 'https://glimpseimg.test/api/v1/info');
});

test('a trailing slash on the base url is normalized away', function () {
    $http = fakeHttp(['*/v1/info' => Factory::response(['data' => []])]);

    client($http, baseUrl: 'https://glimpseimg.test/api/')->info(Images::png());

    $http->assertSent(fn (Request $request) => $request->url() === 'https://glimpseimg.test/api/v1/info');
});
