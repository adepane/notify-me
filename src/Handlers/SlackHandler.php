<?php

declare(strict_types=1);

namespace NotifyMe\Handlers;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

/**
 * Delivers formatted log records to a Slack channel via an incoming webhook.
 */
final class SlackHandler extends AbstractProcessingHandler
{
    use HandlesNotificationFailures;

    public function __construct(
        private readonly string $webhookUrl,
        private readonly int $timeout = 5,
        int|string|Level $level = Level::Error,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if ($this->webhookUrl === '') {
            $this->reportFailure('Slack', 'missing webhook url');

            return;
        }

        try {
            // Slack incoming webhooks expect a JSON body with a "text" field;
            // "mrkdwn" enables its lightweight markup rendering.
            $response = Http::timeout($this->timeout)
                ->asJson()
                ->post($this->webhookUrl, [
                    'text' => (string) $record->formatted,
                    'mrkdwn' => true,
                ]);

            if ($response->failed()) {
                $this->reportFailure('Slack', "HTTP {$response->status()}: {$response->body()}");
            }
        } catch (Throwable $e) {
            $this->reportFailure('Slack', 'request threw an exception', $e);
        }
    }
}
