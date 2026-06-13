<?php

declare(strict_types=1);

namespace NotifyMe\Factories;

use Monolog\Logger;
use NotifyMe\Handlers\TelegramHandler;

/**
 * Custom Monolog factory wired into config/logging.php as the "via" callable.
 *
 * Laravel invokes this class (it is invokable) passing the channel's driver
 * config and expects a configured Monolog Logger in return.
 */
final class TelegramLoggerFactory
{
    use ResolvesNotifierConfig;

    /**
     * @param  array<string, mixed>  $config  The logging channel driver config.
     */
    public function __invoke(array $config): Logger
    {
        /** @var array<string, mixed> $channel */
        $channel = config('exception-notifier.telegram', []);

        $handler = new TelegramHandler(
            botToken: (string) ($channel['bot_token'] ?? ''),
            chatId: (string) ($channel['chat_id'] ?? ''),
            parseMode: ((string) $this->setting($channel, 'format', 'markdown')) === 'plain' ? '' : 'Markdown',
            timeout: (int) ($channel['timeout'] ?? 5),
            level: $this->resolveLevel($channel, $config),
        );

        $handler->setFormatter($this->makeFormatter($channel));

        return new Logger('telegram', [$handler]);
    }
}
