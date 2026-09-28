# Project State

Live snapshot only — not a history log. Task history lives in closed issues; query the tracker instead of
appending to this file indefinitely. When this file grows a long changelog, trim it back to recent entries
and point further back at the issue tracker (a past cleanup that's worth repeating periodically, not a
one-time task).

## Current status

Admin-notifications core routing is implemented (issue #1, merged). The Laravel config + service-provider
scaffold (#4), Mail (#9), and Teams (#10) channel senders are all merged — `AdminAlert`/`AdminInfo` are
usable end-to-end on both channels. `illuminate/support` supports Laravel 9–13 (#15, #19). The
date/timezone module (#6), CSV helpers (#7), misc helpers (#8), and queued Delivery (#22) are merged. Spec #3 is
closed (2026-09-28): all of its package-side work has shipped.

## What's working

- Composer package skeleton (PSR-4, PHPUnit, CI workflow, MIT license) is in place.
- `SageCounseling\Helpers\Str` exists with a passing test (`tests/StrTest.php`).
- `SageCounseling\Helpers\Csv` (issue #7): `row()`/`rows()` (fputcsv-escaped; ports rps
  `filterToCsv`/`filterAllToCsv`) and `quotedRow()`/`quotedRows()` (quote-and-join, no escaping, CRLF; ports
  `make_csv`/`makeFullCsv`). `make_csv_helper` not ported.
- Misc helpers (issue #8): `Path::join()`, `Csv::escapeCommas()`/`escapeCommasIn()` (`&comma;`
  for Credible), `Str::snake()` (no Illuminate), `Str::highlight()`, `Debug::log()`/`here()` (gated by
  `app.debug`), `Redaction\CredentialRedactor::last()`, `Redaction\SqlMessageRedactor::stripSqlSuffix()`.
- `SageCounseling\Helpers\Dates` (issue #6): `TIMEZONE = 'America/Phoenix'`, `fromUtc()`, `showDate()`,
  `showDateTime()`. `nesbot/carbon` (`^2.53 || ^3.0`) is an explicit dependency.
- `SageCounseling\Helpers\Notifications\{Severity,Channel,ChannelMap,ChannelSender,ChannelRegistry,
  NullChannelSender,AdminMessage,AdminAlert,AdminInfo}` — the routing core, with unit
  tests covering per-severity channel sets, `AdminInfo` targeting only its named channel, and unregistered
  channels no-op'ing instead of throwing.
- `config/sage-helpers.php` + `SageCounseling\Helpers\Laravel\SageHelpersServiceProvider` — the
  Laravel-specific glue (issue #4). Reads the three canonical env vars, publishes the config into a
  consuming app, and is auto-discovered via `composer.json`'s `extra.laravel.providers`. Depends on
  `illuminate/support` (`^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0`, issues #15, #19).
- `SageCounseling\Helpers\Notifications\MailChannelSender` (issue #9) — sends an `AdminMessage` as raw text
  via a constructor-injected `Illuminate\Contracts\Mail\Mailer`, addressed to `sage-helpers.admin.email`/
  `.name`.
- `SageCounseling\Helpers\Notifications\TeamsChannelSender` (issue #10) — POSTs an `AdminMessage` to a
  Microsoft Teams incoming webhook via a small `HttpPoster` interface (`postJson(string $url, array $payload)`),
  kept separate from `GuzzleHttp\ClientInterface` so the sender itself and its tests don't depend on Guzzle's
  full interface. `GuzzleHttpPoster` is the concrete `HttpPoster`, added as a container singleton in
  `SageHelpersServiceProvider::register()` so tests can substitute a fake without a real HTTP call. No-ops
  (doesn't throw) when the webhook URL is null. `guzzlehttp/guzzle` (`^7.0`) is now a real dependency of the
  package as a result — the first non-Laravel runtime dependency; scoped entirely to
  `src/Notifications/GuzzleHttpPoster.php` and the provider's wiring.
- `SageHelpersServiceProvider::registerConfiguredSenders()` registers `MailChannelSender` for `Channel::Mail`
  whenever `admin.email` is configured, and `TeamsChannelSender` for `Channel::Teams` whenever
  `teams.webhook_url` is configured — independently, so a site with only one configured still gets that one
  channel working. Either or both left unconfigured falls back to `NullChannelSender` (no-op, not throw).
- Design docs for the admin-notifications module are settled: see `docs/admin-notifications-contract.md`,
  root `CONTEXT.md`, and `docs/adr/0001-fixed-severity-channel-map.md` /
  `docs/adr/0002-admininfo-separate-entry-point.md`.
- Queued Delivery (issue #22): `AdminAlert`/`AdminInfo` hand each Channel to
  `Notifications\Delivery`. With `sage-helpers.queue.enabled`, non-Urgent Deliveries dispatch an encrypted
  `QueuedDelivery` job per configured Channel. Urgent always sends immediately. Send failures are logged
  (no body), never thrown. `GuzzleHttpPoster` has a 5s timeout. See ADR-0003.
- `vendor/bin/phpunit` passes (82 tests, 188 assertions) on master.

## What's broken / blocked

- Credible-safe CSV encoding (#25) is waiting on Miri's tests of how Credible handles quotes and newlines.

## Next milestone

Spec #3 is complete. There is no active milestone. Open work: #25 (Credible-safe CSV, blocked on Miri's
Credible tests). Migrating each app onto the package happens in that app's own repo. ClickSend SMS remains future
work.

## Recent decisions

- 2026-09-28: bi-reflector stays on UTC (`config/app.php` hardcodes `'timezone' => 'UTC'`, so `APP_TIMEZONE` has
  no effect), which corrects the 2026-09-26 note that all three apps run on Phoenix time. Miri decided that stored
  values stay UTC and what users see is shown in Phoenix time, so bi-reflector calls
  `Dates::showDate*(Dates::fromUtc($value))`. rps and compliance-portal do run on Phoenix time. See
  SAGE-Counseling/bi-reflector#267.

- 2026-09-27: Issue #8 `Str::highlight()` is a single-pass regex, not a line-for-line port. rps's loop wrapped
  repeated same-case matches twice and only stripped tags when something matched; the port always strips tags
  and accepts null (Blade passes nullable model fields). `Debug` assumes Laravel (facades, no fallback) per the
  dropped framework-agnostic rule. `CredentialRedactor::last()` kept as is, so a value no longer than `$keep`
  comes back whole.

- 2026-09-27: Issue #7 `Csv::quotedRow()` accepts objects via their JSON form (`json_decode(..., true)`), so
  public properties and `JsonSerializable` (e.g. Eloquent models) work. rps's `make_csv` object branch threw a
  TypeError (it imploded a `stdClass`). `fputcsv` gets an explicit `'\\'` escape so output matches rps and
  PHP 8.4 doesn't emit a deprecation.
- 2026-09-27: Issue #22 queued Delivery — design settled via `/grill-with-docs` (ADR-0003, `CONTEXT.md`
  **Delivery**). Beyond the issue: an unconfigured Channel isn't queued as a no-op job, and a dispatch
  failure (e.g. Redis down) is logged like any failed Delivery (no fallback to an immediate send).
- 2026-09-27: Dropped the "framework-agnostic `src/`" rule. The package assumes it runs under Laravel (all
  consumers are Laravel apps, `illuminate/support` is a hard dependency), so any code in `src/` may use
  Laravel features. Surfaced while scoping #22 (queued alert delivery). Supersedes the #1/#4 framework
  scoping below.
- 2026-09-26: Issue #6 added `Dates`. Unblocked because all three consuming apps now set
  `APP_TIMEZONE="America/Phoenix"` (confirmed by Miri). `showDate()`/`showDateTime()` ship the rps behavior
  (`Carbon::parse($date)`), return `''` for null/empty, and deliberately do not convert timezones — callers
  convert UTC input via `fromUtc()`. `nesbot/carbon` constraint `^2.53 || ^3.0` matches what Laravel 9–13 pull
  in; the suite was run on both Carbon 2.73 (Laravel 10) and Carbon 3.14 (Laravel 13).
- 2026-09-26: Issue #19 added `^13.0` to the `illuminate/support` constraint for Laravel 13 consumers.
  Tested against `illuminate/support` resolved to `v13.33.0`: the `Mailer` contract gained a `cc()` method
  in 13, requiring `FakeMailer` (test double) to implement it; `MailChannelSender` itself needed no change.
  Laravel 13 requires PHP 8.3+, so a consuming app's own PHP constraint governs which major it resolves.
- 2026-09-25: Issue #15 widened `illuminate/support` from `^9.0 || ^10.0` to also include `^11.0 || ^12.0`,
  correcting the #4 decision below — discovered when rps (real Laravel 12, `illuminate/support ^12.0`) failed
  to install the package at all. The original narrowing was based on an *unverified* assumption that some
  consuming app needed PHP 8.1; checking all three apps' actual `composer.json` found bi-reflector on PHP
  8.2/Laravel 10, compliance-portal on PHP 8.3/Laravel 10, and rps on PHP 8.2/Laravel 12 — none need PHP 8.1.
  Confirmed compatible by testing against `illuminate/support` resolved to `v12.69.2`: `Mailer` contract
  gained a `sendNow()` method in that range (the only breaking surface change found), requiring `FakeMailer`
  (test double) to implement it; `MailChannelSender` itself needed no change since it only calls `raw()`.
- 2026-09-25: Issue #10 implemented `TeamsChannelSender` behind a package-defined `HttpPoster` interface
  (rather than depending on `GuzzleHttp\ClientInterface` directly), with `GuzzleHttpPoster` as the one
  concrete adapter — keeps the sender's own tests to a one-method fake instead of a full Guzzle client
  double. `guzzlehttp/guzzle` added as a real dependency (`^7.0`) to support this.
- 2026-09-25: Issue #9 implemented `MailChannelSender` and wired it into
  `SageHelpersServiceProvider::registerConfiguredSenders()`, guarded on `admin.email` being configured.
  `FakeApplication` (test double) gained `bind()` (#9) and `singleton()` (#10) so the provider's container
  calls (`make()`/`bind()`/`singleton()`) work identically in tests and in a real Laravel app, without
  pulling in `orchestra/testbench`.
- 2026-09-24: `AuditLog` was fully split out of this repo's scope into its own standalone package/repo —
  see `docs/audit-log-handoff.md`.
- 2026-09-24: Admin-notification routing resolved via a `/grill-with-docs` session — fixed severity→channel
  map, no per-call-site or per-site overrides (`docs/adr/0001-fixed-severity-channel-map.md`); `AdminInfo`
  added as a separate explicit-channel entry point alongside `AdminAlert`
  (`docs/adr/0002-admininfo-separate-entry-point.md`).
- 2026-09-24: Issue #1 scoped `AdminAlert`/`AdminInfo` to a framework-agnostic routing core only, deferring
  concrete Mail/Teams senders to a follow-up issue, per `.ai/CONTEXT.md`'s framework-agnostic-core guidance.
- 2026-09-24: Issue #4 added `illuminate/support` as a real (non-dev) dependency, scoped to `src/Laravel/`
  only — the explicit exception `.ai/CONTEXT.md` calls for before adding a framework dependency. Pinned to
  `^9.0 || ^10.0` (not `^11`/`^12`) to stay installable on PHP 8.1, since Laravel 11+ requires PHP 8.2.
  `illuminate/contracts` was left off `require` since it's already pulled in transitively and nothing in
  `src/`/`tests/` references it directly (caught in code review). `vlucas/phpdotenv` was added as a dev-only
  dependency so the package's own tests can call the `env()` helper directly — `illuminate/support`'s
  `env()` calls into `Illuminate\Support\Env`, which needs `vlucas/phpdotenv` at runtime and doesn't bundle
  it; consuming Laravel apps already ship it via `laravel/framework`.
