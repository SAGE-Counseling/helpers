# sage-counseling/helpers

Shared Composer package consolidating cross-cutting utility code used by SAGE Counseling's three Laravel apps (bi-reflector, rps, compliance-portal): admin notifications, general-purpose helpers, and a date/timezone module.

## Language

**Admin**:
The single recipient audience for operational alerts about the apps themselves (errors, warnings, things needing attention) — one email/name pair plus a Teams webhook, shared across all delivery channels. Not a per-alert-configurable list, and not split into sub-audiences (e.g. no separate "IT admin" group) even though future channels differ in urgency.
_Avoid_: admin_account, admin_email (these are the old per-site `.env`/config keys being replaced)

**AdminAlert**:
The single call made from application code to notify Admin that something happened. Replaces the prior per-site zoo of `adminMessage()`, `adminErrorMessage()`, `warningEmail()`, `teamsInfo()`/`teamsWarning()`/`teamsUrgent()`.
_Avoid_: adminMessage, warningEmail, teamsInfo/teamsWarning/teamsUrgent (superseded call sites)

**Severity**:
The urgency level of an AdminAlert. Exactly three values: `Info`, `Warning`, `Urgent`. Determines both formatting and — via the fixed Channel Map — which channels the alert is delivered on. `Urgent` is the single top tier (there is no separate "Error" level; alerts about application errors use `Urgent`).
_Avoid_: Error (as a distinct severity — folded into Urgent)

**Channel**:
A delivery mechanism for an AdminAlert: `Mail`, `Teams`, and (planned, not yet built) `Sms` via ClickSend. A channel that isn't configured for a given site (e.g. no Teams webhook set) no-ops rather than erroring.

**Channel Map**:
The fixed, package-wide mapping from Severity to the set of Channels an AdminAlert fires on. Not configurable per site, and not used by AdminInfo. Current/planned shape: `Info` → Mail; `Warning` → Mail + Teams; `Urgent` → Mail + Teams + Sms (Sms pending implementation). Channels rise monotonically with severity — no channel fires at a lower severity than a higher one doesn't also fire at.

**AdminInfo**:
A second, distinct entry point from AdminAlert, for a routine status message directed at exactly one explicitly-named Channel — e.g. a Teams-only heads-up that was never meant to also email Admin. Always informational; it has no Severity and does not use the Channel Map. Shares the same underlying per-channel senders as AdminAlert (one Mail sender, one Teams sender, etc.) so adding a channel later means writing that sender once, not once per entry point.
_Avoid_: using AdminAlert with Severity::Info as a stand-in for "just post to Teams" — that conflates the two concepts and reintroduces per-call-site channel picking into AdminAlert, which ADR-0001 explicitly rejected.
