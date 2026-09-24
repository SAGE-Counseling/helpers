# Project State

Live snapshot only — not a history log. Task history lives in closed issues; query the tracker instead of
appending to this file indefinitely. When this file grows a long changelog, trim it back to recent entries
and point further back at the issue tracker (a past cleanup that's worth repeating periodically, not a
one-time task).

## Current status

Package scope and design are being planned in `docs/` before any implementation lands. Priority order is
admin notifications > general helpers > date/timezone module. No code beyond the initial `Str` class and
its test exists yet for any of the three areas.

## What's working

- Composer package skeleton (PSR-4, PHPUnit, CI workflow, MIT license) is in place.
- `SageCounseling\Helpers\Str` exists with a passing test (`tests/StrTest.php`).
- Design docs for the admin-notifications module are settled: see `docs/admin-notifications-contract.md`,
  root `CONTEXT.md`, and `docs/adr/0001-fixed-severity-channel-map.md` /
  `docs/adr/0002-admininfo-separate-entry-point.md`.

## What's broken / blocked

- Nothing is implemented yet for `AdminAlert`/`AdminInfo`, the date/timezone module, or the general-helper
  extractions listed in `docs/helper-consolidation-candidates.md` — the design is done, the code isn't.
- Date/timezone module work is blocked on each of the three consuming apps (bi-reflector, rps,
  compliance-portal) actually setting `APP_TIMEZONE=America/Phoenix` — see `docs/timezone-recommendation.md`.
  This isn't something this repo can unblock on its own.

## Next milestone

Implement `AdminAlert` and `AdminInfo` per the settled contract (`docs/admin-notifications-contract.md`),
including the Mail and Teams channel senders and the fixed severity→channel map. SMS (ClickSend) is future
work, not part of this milestone.

## Recent decisions

- 2026-09-24: `AuditLog` was fully split out of this repo's scope into its own standalone package/repo —
  see `docs/audit-log-handoff.md`.
- 2026-09-24: Admin-notification routing resolved via a `/grill-with-docs` session — fixed severity→channel
  map, no per-call-site or per-site overrides (`docs/adr/0001-fixed-severity-channel-map.md`); `AdminInfo`
  added as a separate explicit-channel entry point alongside `AdminAlert`
  (`docs/adr/0002-admininfo-separate-entry-point.md`).
