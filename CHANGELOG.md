# Changelog

All notable changes to `dicre/notify-me` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-06-13

### Added

- Initial release.
- Telegram notifier via a Monolog `AbstractProcessingHandler` (`TelegramHandler` / `TelegramLoggerFactory`).
- Discord notifier via webhook (`DiscordHandler` / `DiscordLoggerFactory`).
- Slack notifier via webhook (`SlackHandler` / `SlackLoggerFactory`).
- `ExceptionFormatter` rendering exception class, message, file & line, truncated
  stack trace, environment, app URL and timestamp in `markdown` (default) or `plain`.
- **Automatic exception reporting.** When enabled, the package hooks Laravel's
  exception handler and notifies every service whose credentials are configured
  for each reported exception — no `config/logging.php` or `bootstrap/app.php`
  wiring required. Auto-registers the `telegram`, `discord` and `slack` logging
  channels for any credentialed service the host app has not already defined.
  Controlled by the `auto_report.enabled` config key (default `true`),
  toggleable via the `EXCEPTION_NOTIFIER_AUTO_REPORT` env variable.
- Config-driven credentials via `.env` and publishable `config/exception-notifier.php`.
- Graceful failure handling: a failed notification is logged to the default channel
  instead of being thrown (`HandlesNotificationFailures`).
- `ExceptionNotifierServiceProvider` with Laravel auto-discovery.
- Support for PHP 8.1+ and Laravel 10, 11 & 12.

[Unreleased]: https://github.com/adepane/notify-me/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/adepane/notify-me/releases/tag/v1.0.0
