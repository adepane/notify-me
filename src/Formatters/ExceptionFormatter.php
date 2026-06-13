<?php

declare(strict_types=1);

namespace NotifyMe\Formatters;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;
use Throwable;

/**
 * Renders a Monolog record into a human-readable notification message.
 *
 * When the record carries an exception in its context (Laravel places the
 * Throwable under the "exception" key) the full set of diagnostic fields is
 * extracted; otherwise the plain log message is used. Two output styles are
 * supported: "markdown" (default) and "plain".
 */
final class ExceptionFormatter implements FormatterInterface
{
    public function __construct(
        private readonly string $format = 'markdown',
        private readonly int $maxStackTraceLines = 15,
        private readonly string $environment = 'production',
        private readonly string $appUrl = '',
    ) {
    }

    /**
     * Format a single record into the string the handlers will deliver.
     */
    public function format(LogRecord $record): string
    {
        $fields = $this->extractFields($record);

        return $this->format === 'plain'
            ? $this->toPlain($fields)
            : $this->toMarkdown($fields);
    }

    /**
     * @param  array<int, LogRecord>  $records
     */
    public function formatBatch(array $records): string
    {
        return implode("\n\n", array_map(fn (LogRecord $record): string => $this->format($record), $records));
    }

    /**
     * Pull every diagnostic field out of the record into a flat map.
     *
     * @return array<string, string>
     */
    private function extractFields(LogRecord $record): array
    {
        $exception = $record->context['exception'] ?? null;

        if ($exception instanceof Throwable) {
            $class = $exception::class;
            $message = $exception->getMessage();
            $file = $exception->getFile();
            $line = (string) $exception->getLine();
            $trace = $this->truncateTrace($exception->getTraceAsString());
        } else {
            // Non-exception log line (e.g. Log::error('something')).
            $class = 'Log.'.$record->level->getName();
            $message = $record->message;
            $file = '—';
            $line = '—';
            $trace = '—';
        }

        return [
            'level' => $record->level->getName(),
            'exception' => $class,
            'message' => $message !== '' ? $message : '(no message)',
            'file' => $file,
            'line' => $line,
            'trace' => $trace,
            'environment' => $this->environment,
            'app_url' => $this->appUrl !== '' ? $this->appUrl : '—',
            'timestamp' => $record->datetime->format('Y-m-d H:i:s T'),
        ];
    }

    /**
     * Keep only the first N lines of the stack trace and flag the truncation.
     */
    private function truncateTrace(string $trace): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $trace) ?: [];

        if (count($lines) <= $this->maxStackTraceLines) {
            return $trace;
        }

        $kept = array_slice($lines, 0, $this->maxStackTraceLines);
        $kept[] = sprintf('… (%d more lines truncated)', count($lines) - $this->maxStackTraceLines);

        return implode("\n", $kept);
    }

    /**
     * @param  array<string, string>  $f
     */
    private function toMarkdown(array $f): string
    {
        return <<<MARKDOWN
        *🚨 {$f['level']} — {$f['environment']}*

        *Exception:* `{$f['exception']}`
        *Message:* {$f['message']}
        *Location:* `{$f['file']}:{$f['line']}`
        *App URL:* {$f['app_url']}
        *Time:* {$f['timestamp']}

        *Stack trace:*
        ```
        {$f['trace']}
        ```
        MARKDOWN;
    }

    /**
     * @param  array<string, string>  $f
     */
    private function toPlain(array $f): string
    {
        return <<<PLAIN
        [{$f['level']}] {$f['environment']}

        Exception: {$f['exception']}
        Message: {$f['message']}
        Location: {$f['file']}:{$f['line']}
        App URL: {$f['app_url']}
        Time: {$f['timestamp']}

        Stack trace:
        {$f['trace']}
        PLAIN;
    }
}
