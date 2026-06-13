<?php

declare(strict_types=1);

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Http;
use Monolog\Handler\NullHandler;

beforeEach(function (): void {
    // Send the framework's own report() logging to a no-op channel so the test
    // only observes notifications emitted by the package's auto-report hook.
    config()->set('logging.channels.null', [
        'driver' => 'monolog',
        'handler' => NullHandler::class,
    ]);
    config()->set('logging.default', 'null');
});

it('notifies every credentialed channel when an exception is reported', function (): void {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true], 200),
        'discord.test/*' => Http::response('', 204),
        'slack.test/*' => Http::response('ok', 200),
    ]);

    app(ExceptionHandler::class)->report(new RuntimeException('unhandled boom'));

    Http::assertSentCount(3);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.telegram.org')
        && str_contains($request['text'], 'unhandled boom'));
    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/webhook');
    Http::assertSent(fn ($request) => $request->url() === 'https://slack.test/webhook');
});

it('sends nothing when auto reporting is disabled', function (): void {
    config()->set('exception-notifier.auto_report.enabled', false);

    Http::fake();

    app(ExceptionHandler::class)->report(new RuntimeException('should stay quiet'));

    Http::assertNothingSent();
});

it('only notifies channels whose credentials are present', function (): void {
    config()->set('exception-notifier.telegram.bot_token', null);
    config()->set('exception-notifier.slack.webhook_url', null);

    Http::fake([
        'discord.test/*' => Http::response('', 204),
    ]);

    app(ExceptionHandler::class)->report(new RuntimeException('discord only'));

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://discord.test/webhook');
});
