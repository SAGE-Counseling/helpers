# Admin Notification / Alerting Contract

**Status: current top priority** for `sage-counseling/helpers` — ahead of general helpers and the date module. This is the piece expected to help the most day-to-day: sending admin emails and raising Teams notices from one shared mechanism.

Replaces the recipient-config sprawl found in the full source audit — `rps` alone had four different config keys for "who gets admin mail" (`sage.admin_account`, `sage.admin_email`, `sage.warning_email`, `mail.to_admin`), and `bi-reflector`/`compliance-portal` each had their own variants. One site's alerting is also silently broken (`compliance-portal`'s `Handler::report()` never actually sends), and another calls a function (`warningEmailThrottle()`) that doesn't exist in that app at all.

## Routing: severity drives a fixed channel map

Resolved via `/grill-with-docs` on 2026-09-24 — see [ADR-0001](./adr/0001-fixed-severity-channel-map.md) and `CONTEXT.md` for the full rationale and glossary (`Admin`, `AdminAlert`, `Severity`, `Channel`, `Channel Map`).

- **One admin audience.** There's a single "admin" recipient concept (the `SAGE_ADMIN_EMAIL`/`SAGE_ADMIN_NAME` pair, plus the Teams webhook) — channels are just different pipes to that same audience, not different audiences. Notably, the planned ClickSend "IT admin" SMS channel is *not* a separate recipient group — it's the same Admin, reached one more way.
- **Severity picks channels, call sites don't.** A call site only ever chooses a `Severity` (`Info` / `Warning` / `Urgent` — there is no separate `Error` level, it folds into `Urgent`). Which channels actually fire is entirely owned by a fixed, package-wide map, not configurable per call site or per site:

  | Severity | Channels |
  |---|---|
  | `Info` | Mail |
  | `Warning` | Mail + Teams |
  | `Urgent` | Mail + Teams + Sms *(Sms pending implementation)* |

  Channels only ever add as severity rises. A channel that isn't configured for a given site (e.g. no Teams webhook) no-ops rather than erroring.
- **Adding ClickSend later** means adding one channel implementation and slotting it into the `Urgent` row — no call-site changes needed across bi-reflector/rps/compliance-portal.
- **Explicitly rejected:** per-call-site channel selection (`->via([...])`) and per-site-configurable maps — both would reintroduce the exact per-app drift this package exists to remove. See ADR-0001 for the full reasoning.

## The canonical `.env` values

Every site defines these environment variables (the Teams ones only if it uses Teams). No other "who gets admin mail" config keys should exist once this lands.

```
SAGE_ADMIN_EMAIL="alerts@sagecounseling.example"
SAGE_ADMIN_NAME="SAGE Admin Alerts"
SAGE_TEAMS_WEBHOOK_URL="https://<env>.environment.api.powerplatform.com/powerautomate/automations/direct/workflows/..."
SAGE_TEAMS_APP_LABEL="RPS (Testing Environment)"
```

| Variable | Purpose |
|---|---|
| `SAGE_ADMIN_EMAIL` | The single email address all admin reports/alerts/warnings go to. |
| `SAGE_ADMIN_NAME` | The display name paired with that address (used as the `Mail::to([$email => $name])` name, and anywhere a report needs to show who/what it's addressed to). |
| `SAGE_TEAMS_WEBHOOK_URL` | The Microsoft Teams **Power Automate Workflows** webhook URL used by the Teams notification channel, for sites that send Teams alerts. The channel posts an Adaptive Card envelope; legacy Office 365 connector URLs (`*.webhook.office.com/webhookb2/...`) are retired and not supported. Sites that don't use Teams simply leave it unset. |
| `SAGE_TEAMS_APP_LABEL` | Optional. Identifies the app/environment in the Teams card heading (`{Info\|Warning\|URGENT} — {label}`), since all three apps may post to the same channel. Falls back to `APP_NAME`; if both are unset the heading is just the severity prefix. |

A site with no Teams integration (currently only `rps` has one) can leave `SAGE_TEAMS_WEBHOOK_URL` blank; the package's Teams channel should no-op (or log a warning once) rather than error if it's called with no webhook configured.

## Package-side config

`sage-counseling/helpers` ships a `config/sage-helpers.php` (published into each app) that reads these values:

```php
return [
    'admin' => [
        'email' => env('SAGE_ADMIN_EMAIL'),
        'name'  => env('SAGE_ADMIN_NAME'),
    ],
    'teams' => [
        'webhook_url' => env('SAGE_TEAMS_WEBHOOK_URL'),
        'app_label'   => env('SAGE_TEAMS_APP_LABEL', env('APP_NAME')),
    ],
];
```

## The shared abstraction

One `AdminAlert` call replaces the per-site `adminMessage()`/`adminErrorMessage()`/`warningEmail()`/`teamsInfo()`/`teamsWarning()`/`teamsUrgent()` zoo:

```php
AdminAlert::send(string $message, Severity $severity = Severity::Info, ?string $subject = null);
```

- Resolves the recipient from `config('sage-helpers.admin.email')` / `.name` — never a `User::find(...)` lookup against a hardcoded ID (the current bi-reflector/rps pattern, which fatals if that user row doesn't exist).
- `Severity` (`Info` / `Warning` / `Urgent`) drives delivery entirely through the fixed Channel Map above — the call site never picks channels itself. This replaces the three near-duplicate `TeamsInfoNotification`/`TeamsWarningNotification`/`TeamsUrgentNotification` classes with one parameterized class.
- Every call site becomes a call to a function that's guaranteed to exist in every site that has the package installed — directly closing the "calls `warningEmailThrottle()`, which doesn't exist in this app" class of bug found in bi-reflector.

## AdminInfo: explicit-channel status posts

Resolved via `/grill-with-docs` on 2026-09-24 — see [ADR-0002](./adr/0002-admininfo-separate-entry-point.md) and `CONTEXT.md`.

`AdminAlert` is deliberately closed to explicit channel selection (see Routing above). But there's a real, separate need: a routine status message meant for exactly one channel — e.g. a Teams-only heads-up that was never meant to also email Admin. That's `AdminInfo`, a second, distinct entry point:

```php
AdminInfo::send(Channel $channel, string $message);
```

- No `Severity` — always informational, by definition.
- Never touches the Channel Map — the caller names the one channel directly (`Channel::Mail`, `Channel::Teams`, later `Channel::Sms`).
- Shares the same per-channel sender implementations as `AdminAlert` (one Mail sender, one Teams sender, etc.) — adding a channel later is still one implementation shared by both entry points, not a duplicated backend.
- Rule of thumb for callers: use `AdminAlert` when the message concerns app health and should escalate with severity; use `AdminInfo` for a one-off status post that was only ever going to one channel.

## Migration notes for each site

- **bi-reflector**: replace `adminMessage()`/`adminErrorMessage()` and the `User::find(config('sage.admin_account'))` lookup pattern; fix the dangling `warningEmailThrottle()` call in `CredibleRepository::post()` as part of this migration, not separately.
- **rps**: replace `adminMessage()`, `adminErrorMessage()`, `teamsInfo()`/`teamsWarning()`/`teamsUrgent()`, and `warningEmail()`/`warningEmailThrottle()`. Retire `AdminReport` as the persistence layer for warnings unless it's still needed for its own reporting UI — confirm before dropping.
- **compliance-portal**: replace the mail-only admin alerting in `Exception Handler::report()`; this is also the fix for the `strpos(...) === true` bug (condition should just go away — `AdminAlert` doesn't need a message-content sniff to decide whether to fire).

## What this does not cover

Business-specific notifications (e.g. `NotifyMissingReferralDocument`, `ClientTestedPositiveOnDrugTest`, MCAO-referral alerts) stay as app-specific notification classes — this contract is only for the generic "tell an admin something happened" path, not domain notifications.
