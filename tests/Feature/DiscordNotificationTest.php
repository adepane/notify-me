<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('dispatches an error log through the discord channel', function (): void {
    Http::fake(['discord.test/*' => Http::response('', 204)]);

    Log::channel('discord')->error('Job failed', [
        'exception' => new RuntimeException('Queue worker crashed'),
    ]);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://discord.test/webhook'
            && str_contains($request['content'], 'Queue worker crashed');
    });
});

it('does not dispatch debug logs through the discord channel', function (): void {
    Http::fake();

    Log::channel('discord')->debug('just noise');

    Http::assertNothingSent();
});

it('does not throw when discord returns an error', function (): void {
    Http::fake(['discord.test/*' => Http::response('error', 500)]);

    Log::channel('discord')->error('boom');

    expect(true)->toBeTrue();
});
