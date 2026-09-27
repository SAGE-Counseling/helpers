# SAGE Counseling Helpers

[![Tests](https://github.com/SAGE-Counseling/helpers/actions/workflows/tests.yml/badge.svg)](https://github.com/SAGE-Counseling/helpers/actions/workflows/tests.yml)

Shared PHP helper package used across SAGE Counseling's sites.

## Installation

```bash
composer require sage-counseling/helpers
```

## Tools

### `Str`

General-purpose string helpers.

```php
use SageCounseling\Helpers\Str;

Str::slug('Hello World'); // "hello-world"
Str::slug('Hello World', '_'); // "hello_world"
```

No setup required.

### `Dates`

Date/timezone helpers. Arizona doesn't observe DST, so `Dates::TIMEZONE` (`America/Phoenix`) is a fixed
UTC-7 year-round — use it instead of hand-rolled `subHours()`/`-7` offsets.

```php
use SageCounseling\Helpers\Dates;

Dates::TIMEZONE;                          // "America/Phoenix"
Dates::fromUtc('2026-07-15 20:30:00');    // Carbon, 2026-07-15 13:30:00 America/Phoenix
Dates::showDate('2026-07-15 13:30:00');   // "2026-07-15"
Dates::showDateTime($carbon);             // "2026-07-15 13:30:00"
Dates::showDate(null);                    // ""
```

`showDate()`/`showDateTime()` accept a string or any `DateTimeInterface` and format it as-is — they don't
convert timezones, so pass UTC values through `fromUtc()` first. Assumes the app's `APP_TIMEZONE` is
`America/Phoenix`.

### Admin notifications

Sends operational alerts (errors, warnings, routine status) to Admin — a single email/name and a Teams webhook, shared across all three apps. See [`docs/admin-notifications-contract.md`](docs/admin-notifications-contract.md) and the ADRs in [`docs/adr/`](docs/adr) for the design rationale.

#### Setup

1. The package's Laravel service provider (`SageHelpersServiceProvider`) is auto-discovered — no manual registration needed.
2. Publish the config file (optional — the package works with just env vars):

   ```bash
   php artisan vendor:publish --tag=sage-helpers-config
   ```

3. Set the relevant env vars in each app:

   ```
   SAGE_ADMIN_EMAIL=admin@example.com
   SAGE_ADMIN_NAME="Admin"
   SAGE_TEAMS_WEBHOOK_URL=https://<env>.environment.api.powerplatform.com/powerautomate/automations/direct/workflows/...
   SAGE_TEAMS_APP_LABEL="RPS (Production)"   # optional; defaults to APP_NAME
   ```

   `SAGE_TEAMS_WEBHOOK_URL` must be a Power Automate **Workflows** webhook — the Teams channel sends an Adaptive Card, and the retired Office 365 connector URLs aren't supported.

   A channel with no config (e.g. no `SAGE_TEAMS_WEBHOOK_URL`) simply no-ops instead of erroring — so it's safe to leave Teams unset in an app that doesn't use it.

4. Optional — queue alerts instead of sending them inline (requires a running queue worker, e.g. Horizon):

   ```
   SAGE_ALERTS_QUEUE=true
   SAGE_ALERTS_QUEUE_CONNECTION=redis   # optional; defaults to the app's default connection
   SAGE_ALERTS_QUEUE_NAME=default       # optional
   ```

   Each channel becomes its own encrypted job (3 tries). `Urgent` alerts always send immediately, so an alert about the queue itself still gets out. Either way, a failing channel is logged and never throws into your code. An app that already published `config/sage-helpers.php` picks up the `queue` defaults automatically.

#### `AdminAlert` — severity-routed alerts

The single call for "something needs Admin's attention." You never pick a channel yourself — `Severity` determines it via the fixed Channel Map: `Info` → Mail, `Warning` → Mail + Teams, `Urgent` → Mail + Teams.

```php
use SageCounseling\Helpers\Notifications\AdminAlert;
use SageCounseling\Helpers\Notifications\Severity;

AdminAlert::send('Nightly import finished with 3 skipped rows.'); // Severity::Info by default
AdminAlert::send('Payment gateway responded slowly (4.2s).', Severity::Warning);
AdminAlert::send('Unhandled exception in InvoiceController.', Severity::Urgent, subject: 'Invoice error');
```

#### `AdminInfo` — single-channel routine message

For a routine status message directed at exactly one explicitly-named channel (e.g. a Teams-only heads-up that shouldn't also email Admin). Always informational — it has no `Severity` and ignores the Channel Map.

```php
use SageCounseling\Helpers\Notifications\AdminInfo;
use SageCounseling\Helpers\Notifications\Channel;

AdminInfo::send(Channel::Teams, 'Deploy finished for rps v2.4.0.');
```

#### Non-Laravel / custom senders

Outside Laravel, or to override a sender, register one directly against `ChannelRegistry` (typically in a service provider's `boot()`):

```php
use SageCounseling\Helpers\Notifications\Channel;
use SageCounseling\Helpers\Notifications\ChannelRegistry;
use SageCounseling\Helpers\Notifications\MailChannelSender;

ChannelRegistry::register(Channel::Mail, new MailChannelSender($mailer, 'admin@example.com', 'Admin'));
```

## Testing

```bash
composer install
vendor/bin/phpunit
```
