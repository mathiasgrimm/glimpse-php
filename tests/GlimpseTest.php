<?php

use Composer\InstalledVersions;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use MathiasGrimm\GlimpsePhp\AuthException;
use MathiasGrimm\GlimpsePhp\Glimpse;
use MathiasGrimm\GlimpsePhp\Tests\Fixtures\Images;

test('creates a client without HTTP setup and rejects missing tokens before sending', function () {
    expect(fn () => Glimpse::createClient()->info(Images::png()))
        ->toThrow(AuthException::class);
});

test('uses an injected HTTP factory with the default URL and SDK user agent', function () {
    $http = new Factory;
    $http->fake(['*/v1/info' => Factory::response(fakeInfoResponse())]);

    $result = Glimpse::createClient('test-token', http: $http)->info(Images::png());

    expect($result->width)->toBe(1280);
    $http->assertSent(fn (Request $request) => $request->url() === 'https://glimpseimg.com/api/v1/info'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request->hasHeader('User-Agent', 'glimpse-php/'.(InstalledVersions::getPrettyVersion('mathiasgrimm/glimpse-php') ?? 'unknown')));
});

test('supports rotating tokens and custom URL and user agent', function () {
    $http = new Factory;
    $http->fake(['*/v1/info' => Factory::response(fakeInfoResponse())]);
    $token = 'first-token';

    $client = Glimpse::createClient(
        token: function () use (&$token): string {
            return $token;
        },
        baseUrl: 'https://example.test/api',
        http: $http,
        userAgent: 'my-app/1.0',
    );

    $client->info(Images::png());
    $token = 'second-token';
    $client->info(Images::png());

    foreach (['first-token', 'second-token'] as $expectedToken) {
        $http->assertSent(fn (Request $request) => $request->url() === 'https://example.test/api/v1/info'
            && $request->hasHeader('Authorization', 'Bearer '.$expectedToken)
            && $request->hasHeader('User-Agent', 'my-app/1.0'));
    }
    $http->assertSentCount(2);
});
