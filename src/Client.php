<?php

namespace GlimpseImg;

use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

final class Client
{
    public const DEFAULT_BASE_URL = 'https://glimpseimg.com/api';

    /**
     * @param  (Closure(): ?string)|string|null  $token  A bearer token, or a
     *                                                   closure resolved on every request so the token source can
     *                                                   change between calls. Missing tokens fail at call time.
     */
    public function __construct(
        private readonly Factory $http,
        private readonly Closure|string|null $token = null,
        private readonly string $baseUrl = self::DEFAULT_BASE_URL,
    ) {}

    public function convert(string $bytes, ImageFormat $format, bool $optimize = false, ?int $quality = null): ImageResult
    {
        return ImageResult::fromResponse($this->post('/v1/convert', [
            'format' => $format->value,
            'optimize' => $optimize ?: null,
            'quality' => $quality,
        ], $bytes));
    }

    public function optimize(string $bytes, ?int $quality = null): ImageResult
    {
        return ImageResult::fromResponse($this->post('/v1/optimize', ['quality' => $quality], $bytes));
    }

    public function resize(string $bytes, ?int $width = null, ?int $height = null, bool $optimize = false, ?int $quality = null): ImageResult
    {
        return ImageResult::fromResponse($this->post('/v1/resize', [
            'width' => $width,
            'height' => $height,
            'optimize' => $optimize ?: null,
            'quality' => $quality,
        ], $bytes));
    }

    public function thumbnail(string $bytes, ?int $width = null, ?int $height = null, ?int $quality = null): ImageResult
    {
        return ImageResult::fromResponse($this->post('/v1/thumbnail', ['width' => $width, 'height' => $height, 'quality' => $quality], $bytes));
    }

    public function info(string $bytes): ImageInfo
    {
        return ImageInfo::fromResponse($this->post('/v1/info', [], $bytes));
    }

    /**
     * Predict converted output sizes from metadata alone; no image bytes
     * are sent. The optional sample bits per pixel (a local JPEG trial
     * encode, see SampleProbe) makes the lossy estimates far tighter. The
     * optional frame count (see FrameCounter) makes the AVIF estimate
     * honest for animated sources: AVIF output keeps every frame.
     *
     * @return list<SizeEstimate>
     */
    public function analyze(ImageFormat $format, int $size, ?int $width = null, ?int $height = null, ?int $quality = null, ?float $sampleBpp = null, ?int $frames = null): array
    {
        $estimates = $this->post('/v1/analyze', [
            'format' => $format->value,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'quality' => $quality,
            'sample_bpp' => $sampleBpp,
            'frames' => $frames,
        ]);

        return array_map(
            SizeEstimate::fromResponse(...),
            array_values(array_filter($estimates, 'is_array')),
        );
    }

    /**
     * Month-to-date usage for the token's current team: operation counts,
     * bytes saved, and the average per-image size reduction, with the
     * calendar-month window echoed in `period`.
     */
    public function usage(): UsageSummary
    {
        $response = $this->request($this->requireToken())->get('/v1/usage');

        $data = $this->guard($response)->json('data');

        return UsageSummary::fromResponse(is_array($data) ? $data : []);
    }

    public function user(?string $token = null): User
    {
        $response = $this->request($token ?? $this->requireToken())->get('/user');

        $user = $this->guard($response)->json();

        return User::fromResponse(is_array($user) ? $user : []);
    }

    /**
     * @param  array<string, int|float|string|bool|null>  $params
     * @return array<string, mixed>
     */
    private function post(string $path, array $params, ?string $bytes = null): array
    {
        $payload = ($bytes === null ? [] : [
            'input' => ['type' => 'BASE64', 'data' => base64_encode($bytes)],
        ]) + array_filter($params, fn ($value) => $value !== null);

        $response = $this->request($this->requireToken())->post($path, $payload);

        $data = $this->guard($response)->json('data');

        return is_array($data) ? $data : [];
    }

    private function request(string $token): PendingRequest
    {
        return $this->http->baseUrl(rtrim($this->baseUrl, '/'))
            ->withToken($token)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(120);
    }

    private function guard(Response $response): Response
    {
        if ($response->status() === 401) {
            throw new AuthException('Invalid or missing token.');
        }

        if ($response->status() === 403) {
            $message = $response->json('message');

            throw new ForbiddenException(
                is_string($message) && $message !== '' ? $message : 'This token may not call this endpoint.',
            );
        }

        if ($response->status() === 422) {
            $message = $response->json('message');
            $errors = $response->json('errors');

            throw new ValidationException(
                is_string($message) && $message !== '' ? $message : 'The request was invalid.',
                is_array($errors) ? $errors : [],
            );
        }

        if ($response->status() === 429) {
            $message = $response->json('message');

            throw new RateLimitException(
                is_string($message) && $message !== '' ? $message : 'The API rate limit was reached.',
                $this->retryAfterSeconds($response),
            );
        }

        if ($response->failed()) {
            $message = $response->json('message');

            throw new ApiException(sprintf(
                'API error (%d)%s',
                $response->status(),
                is_string($message) && $message !== '' ? ': '.$message : '',
            ));
        }

        return $response;
    }

    /**
     * Parse the Retry-After header, which RFC 9110 allows as either
     * delay-seconds or an HTTP date. Negative and fractional delays are
     * clamped so a caller can sleep the value as-is; an absent or
     * unparseable header yields null.
     */
    private function retryAfterSeconds(Response $response): ?int
    {
        $header = $response->header('Retry-After');

        if ($header === '') {
            return null;
        }

        if (is_numeric($header)) {
            return max(0, (int) ceil((float) $header));
        }

        $timestamp = strtotime($header);

        return $timestamp === false ? null : max(0, $timestamp - time());
    }

    private function requireToken(): string
    {
        $token = $this->token instanceof Closure ? ($this->token)() : $this->token;

        if ($token === null || $token === '') {
            throw new AuthException('Not authenticated.');
        }

        return $token;
    }
}
