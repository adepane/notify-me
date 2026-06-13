<?php

declare(strict_types=1);

namespace NotifyMe\Factories;

use Monolog\Logger;
use NotifyMe\Handlers\SlackHandler;

/**
 * Custom Monolog factory for the Slack channel (see config/logging.php).
 */
final class SlackLoggerFactory
{
    use ResolvesNotifierConfig;

    /**
     * @param  array<string, mixed>  $config  The logging channel driver config.
     */
    public function __invoke(array $config): Logger
    {
        /** @var array<string, mixed> $channel */
        $channel = config('exception-notifier.slack', []);

        $handler = new SlackHandler(
            webhookUrl: (string) ($channel['webhook_url'] ?? ''),
            timeout: (int) ($channel['timeout'] ?? 5),
            level: $this->resolveLevel($channel, $config),
        );

        $handler->setFormatter($this->makeFormatter($channel));

        return new Logger('slack', [$handler]);
    }
}
