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
   SAGE_TEAMS_WEBHOOK_URL=https://outlook.office.com/webhook/...
   ```

   A channel with no config (e.g. no `SAGE_TEAMS_WEBHOOK_URL`) simply no-ops instead of erroring — so it's safe to leave Teams unset in an app that doesn't use it.

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
