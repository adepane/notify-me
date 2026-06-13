# notify-me

Send Laravel exception/error logs to **Telegram**, **Discord** and **Slack** through Laravel's native logging system. Each notifier is a Monolog custom handler, so you wire it up in `config/logging.php` exactly like any other channel — and you can combine them in a `stack` to broadcast to every service at once.

- ✅ **Zero-config auto-reporting** — fill in `.env` and every unhandled exception is notified automatically
- ✅ Built on Monolog `AbstractProcessingHandler` (no log-listener hacks)
- ✅ Uses the `Http` facade — fully fakeable in tests
- ✅ Config-driven credentials via `.env`
- ✅ Graceful: a failed notification is logged to your default channel, never thrown
- ✅ PHP 8.2+ / Laravel 11, 12 & 13

## Installation

```bash
composer require dicre/notify-me
php artisan vendor:publish --tag=exception-notifier-config
```

## Configuration

1. Add your credentials to `.env` (see [`.env.example`](.env.example)):

```dotenv
TELEGRAM_BOT_TOKEN=123456:ABC-DEF...
TELEGRAM_CHAT_ID=-1001234567890
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
```

That's it. **Auto-reporting is on by default**, so the package hooks Laravel's
exception handler and sends a notification for every reported exception to each
service whose credentials are present — you don't have to touch
`config/logging.php` or `bootstrap/app.php`. To turn it off:

```dotenv
EXCEPTION_NOTIFIER_AUTO_REPORT=false
```

When disabled (or when you want to dispatch notifications manually), wire the
channels up yourself as shown below.

## Manual channel wiring (optional)

Register the channels in `config/logging.php`:

```php
use NotifyMe\Factories\TelegramLoggerFactory;
use NotifyMe\Factories\DiscordLoggerFactory;
use NotifyMe\Factories\SlackLoggerFactory;

'channels' => [

    'telegram' => [
        'driver' => 'custom',
        'via'    => TelegramLoggerFactory::class,
        'level'  => 'error',
    ],

    'discord' => [
        'driver' => 'custom',
        'via'    => DiscordLoggerFactory::class,
        'level'  => 'error',
    ],

    'slack' => [
        'driver' => 'custom',
        'via'    => SlackLoggerFactory::class,
        'level'  => 'error',
    ],

    // Broadcast to all three at once.
    'notifiers' => [
        'driver'   => 'stack',
        'channels' => ['telegram', 'discord', 'slack'],
    ],
],
```

## Usage

Log to a single channel:

```php
Log::channel('telegram')->error('Checkout failed', [
    'exception' => $e, // the Throwable enriches the notification
]);
```

Broadcast to all channels:

```php
Log::channel('notifiers')->critical('Database unreachable', ['exception' => $e]);
```

Route **unhandled** exceptions automatically (Laravel 11+ `bootstrap/app.php`):

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (Throwable $e) {
        Log::channel('notifiers')->error($e->getMessage(), ['exception' => $e]);
    });
});
```

Or set the default log stack in `.env`:

```dotenv
LOG_CHANNEL=notifiers
```

## What a notification contains

Exception class, message, file & line, a truncated stack trace, the environment,
the app URL and a timestamp — rendered as `markdown` (default) or `plain`,
configurable per channel in `config/exception-notifier.php`.

## Development

```bash
composer lint        # apply Pint formatting (PSR-12)
composer lint:check  # verify formatting
composer analyse     # PHPStan level 6
composer test        # Pest test suite
composer check       # all of the above
```

## License

MIT
