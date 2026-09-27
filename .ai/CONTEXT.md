This file defines mandatory context for AI-assisted development in this repository: environment facts and
hot spots. It is distinct from the root `CONTEXT.md`, which is a domain-language glossary (see
`docs/agents/domain.md`) — the two are not duplicates despite the shared filename.

# Project Context

## Project type

A Composer library (`sage-counseling/helpers`), not an application. It's consumed by three separate Laravel
apps (bi-reflector, rps, compliance-portal) as a shared dependency — it has no routes, no HTTP entry point,
and no CLI of its own.

## Framework and versions

Laravel. The package assumes it runs inside a Laravel app, since all three consumers are Laravel apps.
`composer.json` requires `illuminate/support` (`^9.0 || ^10.0 || ^11.0 || ^12.0 || ^13.0`), `nesbot/carbon`
and `guzzlehttp/guzzle`, and auto-registers `SageHelpersServiceProvider`. Code anywhere in `src/` may use
Laravel features (container, config, queues, facades, contracts) where they fit.

## Tooling

- Test command: `vendor/bin/phpunit`
- Formatter: not configured
- Static analysis: not configured

## Hot spots

- `docs/admin-notifications-contract.md` / root `CONTEXT.md` / `docs/adr/0001-*.md` / `docs/adr/0002-*.md` —
  the admin-notifications routing design was deliberately settled after two rounds of rejecting call-site
  channel selection. Don't add a `$channel` parameter to `AdminAlert::send()` or a per-site channel-map
  override without re-opening those ADRs first.

## Local Development Environment

Windows 11, PowerShell primary shell (Bash tool also available). No local database, no queue worker, no
running app server — this is a library, developed and tested in isolation from the three consuming apps.

## Server Environment

Not applicable — this package has no deployment of its own. It ships as a versioned Composer dependency
that the three consuming apps' own deployments pull in.

## Databases

None. This package has no database and no persistent state.

## Languages and Frameworks

PHP 8.1+, Laravel 9–13 (via `illuminate/*`). PHPUnit ^10 as a dev dependency.

## Tooling Expectations

The package targets Laravel apps, so prefer Laravel's own mechanisms (queues, notifications, config,
container bindings, fakes in tests) over package-built abstractions. Any `illuminate/*` usage must stay
compatible with the full supported range, Laravel 9–13.

## Schema/Data Authority

Not applicable — no schema, no database.
