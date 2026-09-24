# AuditLog: Handoff to a Standalone Package

**Status: done — out of this repo's scope entirely.** A standalone `audit-log` directory/git repo has already been created to carry this work forward independently. Nothing further on AuditLog belongs in `sage-counseling/helpers`; this document is kept here only as the historical record of why the split happened and what the audit found, for reference by whoever picks up the new repo.

## Decision

The `AuditLog` module currently living in `compliance-portal` (`app/AuditLog/*`) will **not** move into `sage-counseling/helpers`. It's a standardized method for auditing everything done in an app — a cross-cutting compliance mechanism, not a "helper" — and deserves its own package, its own versioning, and its own release lifecycle, separate from the general-purpose utility grab-bag `helpers` is becoming.

This document is the starting point for that spin-off, written from what the full source audit found. It's a handoff, not a finished design — the actual package structure, namespace, and API should get their own design pass before extraction starts.

## What exists today (source: `compliance-portal`)

Location: `app/AuditLog/`

| Component | Role |
|---|---|
| `AuditLogger` | Entry point — records an audit entry. |
| `Actor` | Represents who performed the action (user, or system). |
| `NetworkContext` | Captures request-level context (IP, etc.) for the entry. |
| `ActionType` | Enum/class of recognized action types. |
| `PurposeOfUse` / `PurposeOfUseResolver` | HIPAA "purpose of use" classification for an access event. |
| `ResourceTypeAllowList` | Config-driven allow-list of resource types the log will accept — this is the extension point already designed for multi-app use. |
| `DeniedAccessBackstop` | Centralized catch — `Handler::render()` invokes this for every non-2xx response, so denied-access events get logged from one place rather than scattered per-controller. |
| `Models/AuditLogEntry` | The persisted audit record. |
| `InvalidResourceTypeException` | Thrown when an entry references a resource type outside the allow-list. |

Backing config: `config/audit-log.php`, which already contains a comment anticipating this exact handoff — it describes the resource-type allow-list as structured *"so a second consumer (e.g. RPS) can register its own resource types."*

The module was audited as host-app-agnostic: no direct references to compliance-portal-specific Eloquent models were found in the reviewed files. That's the main reason it's viable to extract without a rewrite.

## Open questions to resolve before/during extraction

1. **Relationship to `bi-reflector`'s `HasPhiAuditLogging` trait.** bi-reflector independently built a different audit mechanism — a trait wrapping Spatie Activitylog that logs only an identifier column to avoid persisting PHI, distinguishing system vs. user actors. This looks like it's solving a *change*-audit problem (what changed on a model) rather than compliance-portal's *access*-audit problem (who looked at what, and why). Decide whether:
   - both belong in the new package as two distinct concerns (access log + change log), or
   - one subsumes the other, or
   - they stay genuinely separate concerns and only the access-audit module (compliance-portal's) gets extracted now.
2. **Database ownership.** `AuditLogEntry` needs a migration. Does the new package ship and run its own migration (standard for a Laravel package), or does each consuming app own the table? Standard practice favors the package shipping a publishable migration.
3. **Resource type registration API.** `ResourceTypeAllowList` needs a clean per-app registration surface (a service-provider `boot()` call, a config array, or both) now that a second real consumer (RPS) exists rather than being hypothetical.
4. **`DeniedAccessBackstop` wiring.** Currently invoked directly from compliance-portal's exception `Handler::render()`. The package needs to document (or provide) the expected wiring point for a consuming app's own `Handler`, since this can't be auto-registered the way a middleware could.
5. **Naming/namespace.** Not yet decided — e.g. `sage-counseling/audit-log`, published under `SageCounseling\AuditLog\*`.
6. **Versioning independence.** Since this is now explicitly a separate package from `helpers`, confirm it gets its own repo (mirroring how `sage-counseling/helpers` was set up) rather than living in a subdirectory of an existing app or the helpers repo.

## Suggested next steps

1. Create a new repo (`sage-counseling/audit-log` or similar), scaffolded the same way `helpers` was (Composer package skeleton, PSR-4 autoload, PHPUnit, CI).
2. Port `app/AuditLog/*` from compliance-portal into it largely as-is, resolving the open questions above as they're hit rather than up front.
3. Wire compliance-portal to consume the new package (replacing its local `app/AuditLog/*`), verifying no behavior change.
4. Wire RPS as the second consumer — this is the real test of whether the module was actually built generically, since it was designed for this but never verified against a second app.
5. Decide on `bi-reflector`'s `HasPhiAuditLogging` (open question 1) once the module has two working consumers and the shape of "what does access-audit vs. change-audit actually need" is clearer in practice.

## Why this isn't happening inside `helpers`

`sage-counseling/helpers` is shaping up to hold three things: general-purpose helpers (`Str`, and similar small utilities), a date/timezone module (see `timezone-recommendation.md`), and previously this audit module. Audit logging is architecturally different from those — it has its own database table, its own compliance obligations, its own registration/config surface per consuming app, and its own reason to version independently of a `slug()`-style utility function. Bundling it into a general helpers package would make both harder to reason about: helpers releases would carry audit-log schema changes, and audit-log changes would need to wait on helpers' release cadence.
