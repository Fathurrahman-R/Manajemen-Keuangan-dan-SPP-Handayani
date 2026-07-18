Both Laravel applications in this monorepo (backend API and Filament admin/portal) use the default Laravel logging stack backed by Monolog. There is no custom logger wrapper or application-wide logger facade — code calls `Illuminate\Support\Facades\Log` directly from controllers, services, notifications, migrations, and Livewire components.

**Channels and sinks**
- Default channel is `stack`, composed of channels listed in `LOG_STACK` (default: `single`).
- Built-in channels available in both apps' `config/logging.php`: `single`, `daily` (rotated by `LOG_DAILY_DAYS`), `slack` (critical-level only), `papertrail` via UDP, `stderr`, `syslog`, `errorlog`, `null`, and an `emergency` fallback to `storage/logs/laravel.log`.
- Deprecation logging defaults to `null` (`LOG_DEPRECATIONS_CHANNEL=null`) so deprecations are not emitted unless explicitly enabled.
- Log level is driven by `LOG_LEVEL`; placeholder replacement is enabled via `replace_placeholders => true` on file-based channels.

**Structured fields and context**
- All structured log entries pass a second associative-array argument to `Log::level()` (e.g. `['error' => $e->getMessage(), 'order_id' => $orderId]`), which Monolog serializes as JSON context. This is the repo's de-facto structured-logging convention.
- No custom Monolog processor or formatter is registered beyond the framework-default `PsrLogMessageProcessor` used by the `papertrail` and `stderr` channels.

**Sensitive-data handling**
- The Midtrans integration owns its own persistence layer for payment payloads via `App\Services\Midtrans\MidtransLogService`. Before writing inbound webhook bodies or outbound charge/status requests into the `midtrans_transaction_logs` table, it runs a `mask()` routine that:
  - Recursively walks decoded JSON arrays and replaces keys `server_key` / `signature_key` with `***MASKED***`.
  - Falls back to regex-based masking for non-JSON strings.
  - Applies a safety-net check against the live `midtrans.server_key` config value; if the literal key still appears after masking, the write is aborted and a `Log::critical('Midtrans log masking safety net triggered', ...)` entry is emitted instead.
- Outside this service, there is no centralised PII/redaction policy enforced at the logger level — callers must ensure sensitive values are omitted from their context arrays.

**Where logs land**
- File sink: `storage/logs/laravel.log` (single or daily).
- Stderr sink is available for containerised deployments via the `stderr` channel.
- Slack alerts are wired for critical events when `LOG_SLACK_WEBHOOK_URL` is set.
- Papertrail/syslog/errorlog channels exist but are unused by default.

**Observed usage patterns across the codebase**
- Controllers and services predominantly use `Log::error(...)` for exceptions and business-rule violations, `Log::warning(...)` for recoverable conditions (duplicate accounts, invalid signatures, failed API calls), and `Log::critical(...)` for security-sensitive failures (the masking safety net).
- Notification delivery failures are logged with contextual keys such as `notification_type`, `recipient_email`, `exception_message`, enabling per-notification-type alerting.
- Migrations occasionally emit `Log::warning` for skipped rows during data migration scripts.
- Frontend-v2 Livewire components call `\Illuminate\Support\Facades\Log::error(...)` directly when the remote API returns unexpected status codes.

**Rules developers should follow**
1. Use `Illuminate\Support\Facades\Log` directly — do not introduce a new logger facade.
2. Always pass a structured context array as the second argument so downstream tools can index fields like `order_id`, `direction`, `http_status`, etc.
3. Never include raw secrets (`server_key`, `signature_key`, tokens, passwords) in log context or persisted payloads; rely on `MidtransLogService::recordInbound` / `recordOutbound` for payment-related payloads.
4. Choose the correct level: `error` for unrecoverable failures, `warning` for expected-but-unusual conditions, `critical` only for security/safety-net breaches.
5. When adding a new sink, configure it in `config/logging.php` and reference it via `LOG_STACK` rather than hard-coding channels in application code.