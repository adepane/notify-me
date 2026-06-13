<?php

declare(strict_types=1);

namespace NotifyMe;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use NotifyMe\Factories\DiscordLoggerFactory;
use NotifyMe\Factories\SlackLoggerFactory;
use NotifyMe\Factories\TelegramLoggerFactory;
use Throwable;

/**
 * Registers the package configuration and, when enabled, wires automatic
 * exception reporting straight into Laravel's exception handler.
 *
 * The handlers/factories can still be plugged into config/logging.php by hand,
 * but with auto-reporting on (the default) filling in credentials is enough:
 * the provider registers the relevant logging channels and notifies them for
 * every reported exception.
 */
final class ExceptionNotifierServiceProvider extends ServiceProvider
{
    /**
     * Channel name => factory for each service the package can power.
     *
     * @var array<string, class-string>
     */
    private const FACTORIES = [
        'telegram' => TelegramLoggerFactory::class,
        'discord' => DiscordLoggerFactory::class,
        'slack' => SlackLoggerFactory::class,
    ];

    public function register(): void
    {
        // Merge so host apps may override individual keys without copying the
        // whole file, while still getting sane defaults out of the box.
        $this->mergeConfigFrom(
            __DIR__.'/../config/exception-notifier.php',
            'exception-notifier',
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/exception-notifier.php' => $this->app->configPath('exception-notifier.php'),
            ], 'exception-notifier-config');
        }

        $this->registerLoggingChannels();
        $this->registerAutomaticReporting();
    }

    /**
     * Register a custom logging channel for every credentialed service that the
     * host application has not already defined itself.
     */
    private function registerLoggingChannels(): void
    {
        foreach ($this->activeChannels() as $name) {
            $key = "logging.channels.{$name}";

            if (! config()->has($key)) {
                config()->set($key, [
                    'driver' => 'custom',
                    'via' => self::FACTORIES[$name],
                ]);
            }
        }
    }

    /**
     * Hook Laravel's exception handler so every reported exception fans out to
     * the active channels. The toggle is read at report time so it can be
     * flipped at runtime (e.g. in tests) without re-bootstrapping.
     */
    private function registerAutomaticReporting(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        // reportable() lives on the concrete Foundation handler; guard so the
        // package degrades gracefully on unexpected handler implementations.
        if (! method_exists($handler, 'reportable')) {
            return;
        }

        $handler->reportable(function (Throwable $e): void {
            if (! config('exception-notifier.auto_report.enabled', true)) {
                return;
            }

            foreach ($this->activeChannels() as $channel) {
                Log::channel($channel)->error($e->getMessage(), ['exception' => $e]);
            }
        });
    }

    /**
     * The channels whose credentials are present and therefore ready to send.
     *
     * @return list<string>
     */
    private function activeChannels(): array
    {
        $channels = [];

        $telegram = (array) config('exception-notifier.telegram', []);
        if (! empty($telegram['bot_token']) && ! empty($telegram['chat_id'])) {
            $channels[] = 'telegram';
        }

        if (! empty(config('exception-notifier.discord.webhook_url'))) {
            $channels[] = 'discord';
        }

        if (! empty(config('exception-notifier.slack.webhook_url'))) {
            $channels[] = 'slack';
        }

        return $channels;
    }
}
