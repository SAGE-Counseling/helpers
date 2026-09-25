# Project State

Live snapshot only — not a history log. Task history lives in closed issues; query the tracker instead of
appending to this file indefinitely. When this file grows a long changelog, trim it back to recent entries
and point further back at the issue tracker (a past cleanup that's worth repeating periodically, not a
one-time task).

## Current status

Admin-notifications core routing is implemented (issue #1, branch `ai/claude/1-admin-alert-core`, merged).
The Laravel config + service-provider scaffold is implemented (issue #4, merged). The concrete Mail (#9,
merged) and Teams (#10, this branch) channel senders are both implemented — `AdminAlert`/`AdminInfo` are now
usable end-to-end on both channels once this branch lands. Priority order remains admin notifications >
general helpers > date/timezone module.

## What's working

- Composer package skeleton (PSR-4, PHPUnit, CI workflow, MIT license) is in place.
- `SageCounseling\Helpers\Str` exists with a passing test (`tests/StrTest.php`).
- `SageCounseling\Helpers\Notifications\{Severity,Channel,ChannelMap,ChannelSender,ChannelRegistry,
  NullChannelSender,AdminMessage,AdminAlert,AdminInfo}` — the framework-agnostic routing core, with unit
  tests covering per-severity channel sets, `AdminInfo` targeting only its named channel, and unregistered
  channels no-op'ing instead of throwing.
- `config/sage-helpers.php` + `SageCounseling\Helpers\Laravel\SageHelpersServiceProvider` — the
  Laravel-specific glue (issue #4). Reads the three canonical env vars, publishes the config into a
  consuming app, and is auto-discovered via `composer.json`'s `extra.laravel.providers`. Depends on
  `illuminate/support` (`^9.0 || ^10.0`, PHP 8.1-compatible) — the only part of the
  package allowed to depend on `illuminate/*`, per `.ai/CONTEXT.md`.
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
- `vendor/bin/phpunit` passes (22 tests, 45 assertions) on this branch, including a test covering both
  senders registered together.

## What's broken / blocked

- The date/timezone module and the general-helper extractions in `docs/helper-consolidation-candidates.md`
  are still unstarted.
- Date/timezone module work is blocked on each of the three consuming apps (bi-reflector, rps,
  compliance-portal) actually setting `APP_TIMEZONE=America/Phoenix` — see `docs/timezone-recommendation.md`.
  This isn't something this repo can unblock on its own.

## Next milestone

The admin-notifications module (#1, #4, #9, #10) is now complete end-to-end. Next: general-helper
extractions (#7, #8) and the date/timezone module (#6). ClickSend SMS remains future work, not part of any
current milestone.

## Recent decisions

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
