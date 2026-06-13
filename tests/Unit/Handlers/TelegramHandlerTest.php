<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Monolog\Level;
use NotifyMe\Handlers\TelegramHandler;

beforeEach(function (): void {
    $this->handler = new TelegramHandler(
        botToken: 'BOT123',
        chatId: 'CHAT456',
        parseMode: 'Markdown',
        timeout: 5,
        level: Level::Error,
    );
});

it('delivers a message successfully', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    $this->handler->handle(makeRecord(formatted: 'Boom!'));

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/botBOT123/sendMessage'));
});

it('sends the correct payload structure', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

    $this->handler->setFormatter(passthroughFormatter('Hello world'));
    $this->handler->handle(makeRecord());

    Http::assertSent(function (Request $request): bool {
        $body = $request->data();

        return $body['chat_id'] === 'CHAT456'
            && $body['text'] === 'Hello world'
            && $body['parse_mode'] === 'Markdown'
            && $body['disable_web_page_preview'] === true;
    });
});

it('fails silently on a non-2xx response', function (): void {
    Http::fake(['api.telegram.org/*' => Http::response(['error' => 'bad'], 400)]);

    expect(fn () => $this->handler->handle(makeRecord()))->not->toThrow(Throwable::class);
});

it('fails silently when the request throws', function (): void {
    Http::fake(fn () => throw new RuntimeException('network down'));
    Log::spy();

    expect(fn () => $this->handler->handle(makeRecord()))->not->toThrow(Throwable::class);
});

it('does not send debug records when the level is error', function (): void {
    Http::fake();

    $this->handler->handle(makeRecord(level: Level::Debug));

    Http::assertNothingSent();
});

it('does nothing when credentials are missing', function (): void {
    Http::fake();
    Log::spy();

    $handler = new TelegramHandler(botToken: '', chatId: '', level: Level::Error);
    $handler->handle(makeRecord());

    Http::assertNothingSent();
});
