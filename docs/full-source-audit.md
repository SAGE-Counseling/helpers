# Full Source Audit — Cross-Site Consistency & Consolidation

Full-codebase audit of the three sibling apps (`bi-reflector`, `rps`, `compliance-portal`), following up on [`helper-consolidation-candidates.md`](./helper-consolidation-candidates.md), which only covered the three `app/Helpers/*.php` files. This document covers everything else: services, traits, notifications, config, and third-party integration wrappers.

Each site was audited independently by a separate reviewer; findings below are synthesized and cross-referenced. Direct evidence of copy-pasting between sites (identical code with a stale header comment naming the *other* app) is called out explicitly — it's the strongest signal for what to extract first.

## Scope decisions (2026-09-24)

Following review of this audit, these calls were made on how to act on it:

- **`CommandCheckerService` is out of scope.** RPS is having its feeds removed (compliance-portal's were already removed), so the reliability-checking use case goes away in those two sites. bi-reflector's implementation is the best of the three but will stay local to bi-reflector rather than move into the shared package — there's no longer a second consumer.
- **`AuditLog` is fully out of scope for this repo — done.** It has its own standalone repo now (see [`audit-log-handoff.md`](./audit-log-handoff.md) for the historical record of why and what was found). No further AuditLog work happens here.
- **Credible EHR integration is left alone.** It's deprecated in RPS and compliance-portal (being removed), so consolidating the three divergent client implementations is no longer worth the effort — only bi-reflector will keep using it long-term.
- **Admin notification/alerting is the current top priority.** See [`admin-notifications-contract.md`](./admin-notifications-contract.md) for the `.env` contract (email, display name, Teams webhook string) and the notification-channel abstraction, designed to also support a future ClickSend SMS channel without rework.
- **Timezone handling comes last**, since it's gated on real changes needed in each of the three sites (not just package-side work) — see [`timezone-recommendation.md`](./timezone-recommendation.md) for the firm recommendation on defaulting all three sites to `America/Phoenix`.
- **Package shape / priority order going forward**: (1) admin notifications — highest value, biggest immediate help; (2) general helpers (already started, `Str`); (3) date/timezone module — last, because it depends on per-site config changes landing first.

## Direct evidence of copy-paste-across-sites (extract first)

These aren't just "similar" — they're the same code, manually copied, with tells left behind:

- **`CommandCheckerService`** exists in all three apps (`bi-reflector`, `rps`, `compliance-portal`), generic and config-driven (`config('command-checker.commands')`: model class, lookback hours, command class/signature, run-immediately flag). In `rps` and `bi-reflector` the file header literally says **"for compliance-portal"** — i.e. it originated there and was copied outward without updating the comment. **Decision: out of scope** — RPS's and compliance-portal's feeds are being removed, so the reliability-check use case no longer applies to them; bi-reflector keeps its (best) copy locally.
- **Credible EHR backoff/retry trait**: `compliance-portal`'s `app/Sage/Services/Credible/Traits/ApiRetryWithBackoff.php` has a docblock header saying **"for bi-reflector"** — confirmed copied from there. Both sites (and `rps`, via its own separate `CredibleRepository`) each maintain their own Credible client with overlapping Redis-backoff/retry logic. **Decision: leave alone** — Credible integration is deprecated and being removed from RPS and compliance-portal; only bi-reflector will keep using it.
- **`ExceptionLogger`** backs `log_exception()` in all three sites and is nearly identical everywhere (writes exception code/message/class/function to a DB table). `rps`'s copy also has a header saying **"for compliance-portal."** Straightforward extraction once the `$connection` parameter difference (present in bi-reflector's signature, absent elsewhere) is reconciled.
- **`compliance-portal`'s `config/audit-log.php`** contains a comment explicitly anticipating this exact initiative: *"so a second consumer (e.g. RPS) can register its own resource types."* The `AuditLog` module (see below) was seemingly already designed for extraction and just never made the jump.

## Strongest new candidates for the shared package

Beyond what the helper-file review already found:

- **`AuditLog` module** (`compliance-portal`, `app/AuditLog/*`) — a self-contained HIPAA access-audit logger (Actor, NetworkContext, ActionType, PurposeOfUse/Resolver, ResourceTypeAllowList, DeniedAccessBackstop, AuditLogEntry model), already written host-app-agnostic with no direct Eloquent coupling to compliance-portal-specific models, and a config file that already names RPS as an intended second consumer. **This is the single highest-value candidate in the whole audit.** **Decision: it will NOT move into `sage-counseling/helpers`** — it's a standardized audit mechanism, not a helper, and is being spun out as its own standalone package. See [`audit-log-handoff.md`](./audit-log-handoff.md). Still worth resolving how it relates to `bi-reflector`'s independent `HasPhiAuditLogging` trait (access-audit vs. change-audit — they may both belong under the new package, or stay separate) as part of that handoff.
- **PHI-safe logging stack** (`bi-reflector`): `DiagnosticLogger` (structured logger separating safe metadata from an encrypted PHI blob, with an `emergency`-channel fallback if the DB write itself fails), `PHIFilter` (regex/key-based redactor for strings and arrays, salted-hash placeholders), `RedactsPHI` trait, and the Monolog-level `PHIRedactionTap`/`PHIRedactionProcessor` pair (redacts PHI at the logging-channel level, works with both wrapped and raw Monolog loggers). `compliance-portal` has a parallel but differently-shaped need: `config/sanitize.php`'s declarative per-table/per-column PHI classification for its `SanitizeDatabase` command. These should be reconciled into one shared PHI-redaction engine with per-app config, rather than three independent partial solutions.
- **Microsoft Teams notification channel** (`rps` only today) — `TeamsInfoNotification`/`TeamsWarningNotification`/`TeamsUrgentNotification` differ only in title/color; a single shared, parameterized notification class would replace all three and could extend to the other sites without them needing to build their own Teams integration from scratch.
- **Small, genuinely generic utilities worth a one-line move each**: `SqlMessageRedactor` (strips Laravel's inlined SQL suffix from `QueryException` messages, content-based detection), `CredentialRedactor::last()` (truncate secrets for safe logging), `ColorSet` (config-driven CSS-class-by-value mapper, `rps`), `FlashMessages` trait (session flash helper, `compliance-portal`), `SageDebug` trait (`Log::debug()` wrapper gated by `config('app.debug')`, `rps`). All zero-dependency, all currently living in only one site.
- **`DataSyncStatusService`** (`compliance-portal`) and **`CommandCheckerService`**'s sibling concept in `bi-reflector`'s `data_freshness.php` — both describe "is this data source stale" in slightly different shapes; worth a single shared "data freshness / staleness watchdog" contract.

## Recurring architectural inconsistency: Credible EHR integration

Every site talks to the same Credible BH EHR API, and every site does it differently — including *within* a single app:

- **`bi-reflector`**: `CredibleRepository` (Guzzle-based).
- **`rps`**: *two* separate clients — `CredibleService` (SOAP) and `CredibleRepository` (Guzzle, chainable, different conventions from `AversysRepository`'s own chainable style in the same app).
- **`compliance-portal`**: *two* separate clients — `CredibleService` (SOAP, copied from bi-reflector per its header) and `ApiRepository` (older Guzzle/REST, partially marked `@deprecated` but still live).

Common bugs/smells across these clients, independently discovered by each reviewer:
- Hardcoded `America/Phoenix` / fixed `-5`/`-7` hour offsets instead of real timezone conversion, in at least three separate `fixDate()`-style implementations (`bi-reflector`, `rps`, `compliance-portal` all have their own).
- `rps`'s `CredibleRepository` catches Guzzle 5-era exception classes (`ServerErrorResponseException`/`ClientErrorResponseException`) that no longer exist in Guzzle 6/7 — dead catch blocks, real errors fall through uncaught to a generic handler.
- Fetch → coerce → upsert logic for bulk Credible data is reimplemented at least 3-4 times within `bi-reflector` alone (`getCredibleItemTrait`'s three near-identical methods, plus `GetChunkTrait`), and again per-site.

**Recommendation:** before (or alongside) extracting Credible client code into the shared package, pick one transport (SOAP vs. REST) and one chainable-client shape, and design a single "bulk external-feed fetch/coerce/upsert" abstraction with correct timezone handling. This is as much an internal-consistency problem within each app as it is a cross-site one.

## Notification / admin-alerting inconsistency

All three sites solve "notify an admin of something" differently, and inconsistently even within one app:

- **Recipient config sprawl**: `rps` alone uses four different config keys for "who gets admin mail" (`sage.admin_account`, `sage.admin_email`, `sage.warning_email`, `mail.to_admin`). `bi-reflector` and `compliance-portal` each have their own `sage.admin_account`/`admin_email` variants. None of these agree on a name.
- **Channel/pattern mismatch**: `rps` has Teams (info/warning/urgent) + mail; `bi-reflector` and `compliance-portal` are mail-only, each with their own near-duplicate `NotifyAdminOfMessage`/`NotifyAdminOfErrorMessage`-style classes.
- **Known bugs in this area**: `bi-reflector`'s `CredibleRepository::post()` calls `warningEmailThrottle(...)`, a function that **does not exist in bi-reflector at all** — it only exists in `rps`. Any failed Credible POST throws a fatal error instead of alerting anyone. `compliance-portal`'s `Handler::report()` has a `strpos(...) === true` bug that means its one piece of custom exception-alerting code **never fires** (`strpos` never returns `true`).

**Decision: adopted.** The shared package defines one `AdminAlert`/`NotifyAdmin` abstraction (message + severity + optional channel) backed by a single `.env` contract — see [`admin-notifications-contract.md`](./admin-notifications-contract.md) — and each site migrates its admin-notification call sites to it. This directly prevents the "function doesn't exist in this app" class of bug from recurring.

## Date/time handling — no site has a real answer

Every reviewer independently flagged this as unsolved:

- No app has a shared `Date`/timezone helper; every call site hardcodes its own timezone offset or format string.
- `bi-reflector`, `rps`, and `compliance-portal` each have their own Credible-date-fixing logic with a hardcoded `America/Phoenix` and a `subHours(5)`/`-7` DST-unaware fudge.
- `compliance-portal`'s `AuthorizesByRoiAgency` hardcodes a `-7` offset directly in raw SQL with a comment justifying it via "Arizona doesn't observe DST" — correct reasoning, but inlined rather than centralized, so the same reasoning has to be independently re-verified everywhere it's copied.
- The already-known `showDate()` bug (compliance-portal's Carbon-parsing branch commented out) has a **confirmed live UI effect**: blank "last service date" cells on `clients/show.blade.php` for string-typed dates.

**Decision: adopted.** The `-5`/`-7` hardcoded offsets were always standing in for one fact — Arizona doesn't observe DST, so `America/Phoenix` is a fixed UTC-7 year-round — but doing it as raw offset math instead of the named timezone is exactly what let it drift into inconsistent, hard-to-verify code at each site. See [`timezone-recommendation.md`](./timezone-recommendation.md) for the firm recommendation on configuring all three sites to default to `America/Phoenix`, which the package's date helpers then build on.

## Architectural pattern differences worth reconciling (not just code, but structure)

- **`rps`** has a strong, consistent single-purpose "Action class" convention (60+ classes under `app/Actions/*`). Worth checking whether `bi-reflector`/`compliance-portal` use the same convention or lean on fat controllers/services instead — this affects how any shared package's own actions/helpers should be shaped to fit each site idiomatically.
- **No app has a base Eloquent model class** (`bi-reflector` confirmed every model extends `Model` directly) — if a shared package ever wants to standardize model-level behavior (e.g. PHI-safe `toArray()`, audit logging hooks), a `BaseModel` will need to be introduced consistently, not assumed to exist.
- **Validation rules**: `rps`'s `OnlyAccessAndAdmin` rule uses the deprecated `Illuminate\Contracts\Validation\Rule` interface (pre-Laravel-10 style) and has a real bug (references `$this->user`, which is never set). If other sites do similar AD/OU-based access rules, this is a candidate for a shared, correctly-implemented "is user in these AD groups" rule — but only after the bug is fixed.

## Cross-site bugs and dead code found during this audit

Not the primary goal, but each reviewer surfaced real defects worth ticketing separately from the consolidation work:

**bi-reflector**
- `CredibleRepository::post()` calls `warningEmailThrottle()`, undefined in this app — fatal error on any failed Credible POST.
- Unused `use` import referencing a nonexistent `ErrorOccuredOnTheDashboardNotification` class.
- `LeakyBucketService::applyPenalty()` does a raw `echo 'x';` before sleeping — stray debug output in a service class.
- `CredibleRepository::noDate()`'s `$this->convertDates` flag appears to be write-only (never read elsewhere in the class).
- `config/sage.php`'s `expenses_file_name` hardcoded to `'EXP_2021.csv'`.
- Entire `app/Actions/Deprecated/*` directory kept in the codebase past its stated deprecation.

**rps**
- `warningEmailThrottle()`'s Redis-throttle branch is unreachable dead code (`return;` before it, `// todo get redis to work`).
- `getSetting($id) {}` — empty no-op stub in the helpers file.
- `CredibleRepository::get()` catches Guzzle 5-era exception classes that no longer exist in the installed Guzzle version — dead/broken catch blocks.
- `OnlyAccessAndAdmin` rule references an unset `$this->user` property.
- Stray non-PHP file `aversys notes` sitting inside `config/`; `config/old-glpi.php` is dead legacy config.
- `WarningErrorNotification` ships a duplicate, likely-abandoned second email template alongside its Mailable's real one, with the "correct" approach commented out.

**compliance-portal**
- `showDate()`'s commented-out Carbon branch causes a confirmed blank field on the client detail page for string dates.
- `filterArrayDates()` is confirmed to have zero call sites — pure dead code, in addition to its broken filter callback.
- `Handler::report()`'s `strpos(...) === true` check is always false — the one custom exception-alert path in this app never actually sends.
- `app/Repositories/Repository.php::show()` has a `-` typo (`$this->model - findOrFail($id)`) instead of `->` — guaranteed fatal if ever called.
- `SendEmail::attach()` references `$this->email->id`, but `$this->email` is never set on the class — guaranteed error if called; `send()` also never dispatches the `SendEmailJob` it imports, despite the class's apparent purpose.
- `CleanStaleRecordsTraits` declares an empty `__construct()` inside a trait — silently overrides any host class that doesn't define its own constructor.

## Recommended order of operations (updated per scope decisions)

1. **Extract `ExceptionLogger`** into `sage-counseling/helpers` — lowest risk, already proven near-identical across all three sites. (`CommandCheckerService` and the Credible backoff/retry trait are no longer in scope — see Scope decisions above.)
2. **Configure `America/Phoenix` as the default timezone** on all three sites per [`timezone-recommendation.md`](./timezone-recommendation.md), then build the package's date helpers on top of it. Do this early — it unblocks fixing the Credible/ROI hardcoded-offset code cleanly rather than layering a new utility on top of still-broken assumptions.
3. **Land the admin-notification `.env` contract and `AdminAlert` abstraction** per [`admin-notifications-contract.md`](./admin-notifications-contract.md), and migrate each site's admin-alert call sites onto it. This directly fixes the two "silently never fires" bugs found in the audit.
4. **Spin out the `AuditLog` module** as its own standalone package per [`audit-log-handoff.md`](./audit-log-handoff.md) — separate workstream from `sage-counseling/helpers`, can run in parallel with the above.
5. **Fix the remaining standalone bugs** found in this audit (the fatal `-` typo in compliance-portal's `Repository::show()`, `SendEmail::attach()`'s unset property, etc.) as their own small tickets — they're real production defects, not just cleanup, but don't block the consolidation work above.
6. **PHI-redaction/logging stack consolidation** (bi-reflector's `DiagnosticLogger`/`PHIFilter`/Monolog tap vs. compliance-portal's `sanitize.php`) remains open for a future round — not yet scheduled.
