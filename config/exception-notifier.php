<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Global defaults
    |--------------------------------------------------------------------------
    |
    | These values are shared by every notifier channel unless the channel
    | overrides them. They control how the exception payload is rendered
    | before it is delivered to a remote service.
    |
    */

    'defaults' => [
        // "markdown" or "plain". Controls how the ExceptionFormatter renders.
        'format' => env('EXCEPTION_NOTIFIER_FORMAT', 'markdown'),

        // Maximum number of stack-trace lines included in the message.
        'max_stack_trace_lines' => (int) env('EXCEPTION_NOTIFIER_MAX_TRACE_LINES', 15),

        // Minimum log level that triggers a notification.
        'level' => env('EXCEPTION_NOTIFIER_LEVEL', 'error'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic reporting
    |--------------------------------------------------------------------------
    |
    | When enabled, the package hooks Laravel's exception handler and sends a
    | notification for every reported exception to each service whose
    | credentials are configured — no need to register channels in
    | config/logging.php or wire a report() callback in bootstrap/app.php.
    |
    | It is on by default; turn it off entirely with
    | EXCEPTION_NOTIFIER_AUTO_REPORT=false in your .env.
    |
    */

    'auto_report' => [
        'enabled' => env('EXCEPTION_NOTIFIER_AUTO_REPORT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram
    |--------------------------------------------------------------------------
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),

        // null = inherit from defaults above.
        'format' => env('TELEGRAM_FORMAT'),
        'max_stack_trace_lines' => env('TELEGRAM_MAX_TRACE_LINES'),
        'level' => env('TELEGRAM_LEVEL'),

        // Seconds before the HTTP request is aborted.
        'timeout' => (int) env('TELEGRAM_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Discord
    |--------------------------------------------------------------------------
    */

    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),

        'format' => env('DISCORD_FORMAT'),
        'max_stack_trace_lines' => env('DISCORD_MAX_TRACE_LINES'),
        'level' => env('DISCORD_LEVEL'),

        'timeout' => (int) env('DISCORD_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),

        'format' => env('SLACK_FORMAT'),
        'max_stack_trace_lines' => env('SLACK_MAX_TRACE_LINES'),
        'level' => env('SLACK_LEVEL'),

        'timeout' => (int) env('SLACK_TIMEOUT', 5),
    ],

];
