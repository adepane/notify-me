<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Monolog\Level;
use NotifyMe\Handlers\DiscordHandler;

beforeEach(function (): void {
    $this->webhook = 'https://discord.com/api/webhooks/abc/def';
    $this->handler = new DiscordHandler(webhookUrl: $this->webhook, timeout: 5, level: Level::Error);
});

it('delivers a message successfully', function (): void {
    Http::fake([$this->webhook => Http::response('', 204)]);

    $this->handler->handle(makeRecord(formatted: 'Boom!'));

    Http::assertSent(fn (Request $request): bool => $request->url() === $this->webhook);
});

it('sends the correct payload structure', function (): void {
    Http::fake([$this->webhook => Http::response('', 204)]);

    $this->handler->setFormatter(passthroughFormatter('Hello discord'));
    $this->handler->handle(makeRecord());

    Http::assertSent(fn (Request $request): bool => $request->data() === ['content' => 'Hello discord']);
});

it('fails silently on a non-2xx response', function (): void {
    Http::fake([$this->webhook => Http::response(['error' => 'bad'], 500)]);

    expect(fn () => $this->handler->handle(makeRecord()))->not->toThrow(Throwable::class);
});

it('fails silently when the request throws', function (): void {
    Http::fake(fn () => throw new RuntimeException('network down'));

    expect(fn () => $this->handler->handle(makeRecord()))->not->toThrow(Throwable::class);
});

it('does not send debug records when the level is error', function (): void {
    Http::fake();

    $this->handler->handle(makeRecord(level: Level::Debug));

    Http::assertNothingSent();
});

it('does nothing when the webhook url is missing', function (): void {
    Http::fake();

    (new DiscordHandler(webhookUrl: '', level: Level::Error))->handle(makeRecord());

    Http::assertNothingSent();
});
