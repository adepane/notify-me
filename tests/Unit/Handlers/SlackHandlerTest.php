<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Monolog\Level;
use NotifyMe\Handlers\SlackHandler;

beforeEach(function (): void {
    $this->webhook = 'https://hooks.slack.com/services/T000/B000/XXXX';
    $this->handler = new SlackHandler(webhookUrl: $this->webhook, timeout: 5, level: Level::Error);
});

it('delivers a message successfully', function (): void {
    Http::fake([$this->webhook => Http::response('ok', 200)]);

    $this->handler->handle(makeRecord(formatted: 'Boom!'));

    Http::assertSent(fn (Request $request): bool => $request->url() === $this->webhook);
});

it('sends the correct payload structure', function (): void {
    Http::fake([$this->webhook => Http::response('ok', 200)]);

    $this->handler->setFormatter(passthroughFormatter('Hello slack'));
    $this->handler->handle(makeRecord());

    Http::assertSent(fn (Request $request): bool => $request->data() === ['text' => 'Hello slack', 'mrkdwn' => true]);
});

it('fails silently on a non-2xx response', function (): void {
    Http::fake([$this->webhook => Http::response('invalid_payload', 400)]);

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

    (new SlackHandler(webhookUrl: '', level: Level::Error))->handle(makeRecord());

    Http::assertNothingSent();
});
