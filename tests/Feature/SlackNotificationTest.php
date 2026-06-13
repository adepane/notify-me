<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('dispatches an error log through the slack channel', function (): void {
    Http::fake(['slack.test/*' => Http::response('ok', 200)]);

    Log::channel('slack')->error('Service degraded', [
        'exception' => new RuntimeException('Redis connection refused'),
    ]);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://slack.test/webhook'
            && str_contains($request['text'], 'Redis connection refused')
            && $request['mrkdwn'] === true;
    });
});

it('does not dispatch debug logs through the slack channel', function (): void {
    Http::fake();

    Log::channel('slack')->debug('just noise');

    Http::assertNothingSent();
});

it('fans out to every channel through the notifiers stack', function (): void {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
        'discord.test/*' => Http::response('', 204),
        'slack.test/*' => Http::response('ok', 200),
    ]);

    Log::channel('notifiers')->error('Total meltdown', [
        'exception' => new RuntimeException('everything is down'),
    ]);

    // One delivery per channel in the stack.
    Http::assertSentCount(3);
});

it('does not throw when slack returns an error', function (): void {
    Http::fake(['slack.test/*' => Http::response('invalid_payload', 400)]);

    Log::channel('slack')->error('boom');

    expect(true)->toBeTrue();
});
