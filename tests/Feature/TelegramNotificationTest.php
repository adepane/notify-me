<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('dispatches an error log through the telegram channel', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    Log::channel('telegram')->error('Checkout failed', [
        'exception' => new RuntimeException('Payment gateway timeout'),
    ]);

    Http::assertSent(function (Request $request): bool {
        return str_contains($request->url(), '/botTEST_TOKEN/sendMessage')
            && $request['chat_id'] === '123456'
            && str_contains($request['text'], 'Payment gateway timeout');
    });
});

it('does not dispatch debug logs through the telegram channel', function (): void {
    Http::fake();

    Log::channel('telegram')->debug('just noise');

    Http::assertNothingSent();
});

it('does not throw when telegram returns an error', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response('bad request', 400)]);

    Log::channel('telegram')->error('boom');

    expect(true)->toBeTrue(); // reached this line => no exception bubbled up
});
