# Project State

Live snapshot only — not a history log. Task history lives in closed issues; query the tracker instead of
appending to this file indefinitely. When this file grows a long changelog, trim it back to recent entries
and point further back at the issue tracker (a past cleanup that's worth repeating periodically, not a
one-time task).

## Current status

Admin-notifications core routing is implemented (issue #1, branch `ai/claude/1-admin-alert-core`, merged).
The Laravel config + service-provider scaffold is implemented (issue #4, branch
`ai/claude/4-config-service-provider-scaffold`, PR pending). The concrete Mail channel sender is implemented
(issue #9, branch `ai/claude/9-mail-channel-sender`, PR pending); Teams (#10) is next. Priority order remains
admin notifications > general helpers > date/timezone module.

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
  `.name`. `SageHelpersServiceProvider::registerConfiguredSenders()` registers it into `ChannelRegistry` for
  `Channel::Mail` whenever `admin.email` is configured; unconfigured, it's left unregistered and
  `ChannelRegistry` falls back to `NullChannelSender` as before. Teams (`Channel::Teams`, #10) is still the
  no-op stub.
- Design docs for the admin-notifications module are settled: see `docs/admin-notifications-contract.md`,
  root `CONTEXT.md`, and `docs/adr/0001-fixed-severity-channel-map.md` /
  `docs/adr/0002-admininfo-separate-entry-point.md`.
- `vendor/bin/phpunit` passes (20 tests, 39 assertions).

## What's broken / blocked

- Teams channel sender (#10) doesn't exist yet — `Channel::Teams` still resolves to `NullChannelSender`, so
  `AdminAlert`/`AdminInfo` no-op on Teams until that lands. Mail (#9) is now end-to-end functional.
- The date/timezone module and the general-helper extractions in `docs/helper-consolidation-candidates.md`
  are still unstarted.
- Date/timezone module work is blocked on each of the three consuming apps (bi-reflector, rps,
  compliance-portal) actually setting `APP_TIMEZONE=America/Phoenix` — see `docs/timezone-recommendation.md`.
  This isn't something this repo can unblock on its own.

## Next milestone

Implement the concrete Teams `ChannelSender` (#10) so `AdminAlert`/`AdminInfo` are usable end-to-end on both
channels by a consuming Laravel app. ClickSend SMS remains future work, not part of this milestone.

## Recent decisions

- 2026-09-25: Issue #9 implemented `MailChannelSender` and wired it into
  `SageHelpersServiceProvider::registerConfiguredSenders()`, guarded on `admin.email` being configured.
  `FakeApplication` (test double) gained a `bind()` method so tests can hand the provider a fake `Mailer`
  without pulling in `orchestra/testbench`.
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
