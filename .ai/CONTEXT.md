This file defines mandatory context for AI-assisted development in this repository: environment facts and
hot spots. It is distinct from the root `CONTEXT.md`, which is a domain-language glossary (see
`docs/agents/domain.md`) — the two are not duplicates despite the shared filename.

# Project Context

## Project type

A Composer library (`sage-counseling/helpers`), not an application. It's consumed by three separate Laravel
apps (bi-reflector, rps, compliance-portal) as a shared dependency — it has no routes, no HTTP entry point,
and no CLI of its own.

## Framework and versions

No framework dependency. `composer.json` requires only `php: ^8.1`. Do not add `illuminate/support` or any
other framework package without an explicit decision — see the PHP guardrails in `.ai/GUARDRAILS.md`.

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

PHP 8.1+. No framework. Depends only on PHPUnit ^10 as a dev dependency.

## Tooling Expectations

Keep the package framework-agnostic in `src/` — any Laravel-specific glue (service provider, published
config) should be scoped narrowly and not force a framework dependency for consumers who only want the
plain PHP utilities.

## Schema/Data Authority

Not applicable — no schema, no database.
