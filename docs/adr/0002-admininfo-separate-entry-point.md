# AdminInfo as a separate entry point from AdminAlert

**Status:** accepted

We needed a way to send a routine status message to one specific channel (e.g. "post this to Teams only") without going through Severity. The obvious-looking option was adding an optional `?Channel $channel = null` parameter to `AdminAlert::send()` that, when given, bypasses the Channel Map. We rejected that: it reopens exactly the "call site picks channels" design [ADR-0001](./0001-fixed-severity-channel-map.md) explicitly closed, just gated behind an optional parameter instead of a required one — the fixed map would only be "fixed" until the first caller passed `$channel`.

**Decision:** `AdminInfo` is a second, separate entry point, e.g. `AdminInfo::send(Channel $channel, string $message)`. It has no `Severity` and never touches the Channel Map — it always means "informational, and only to this one channel." `AdminAlert` keeps its contract clean: you give it a `Severity`, never a `Channel`. Both entry points share the same underlying per-channel sender implementations (Mail, Teams, future Sms), so adding a new channel is still one implementation, not one per entry point.

**Consequences:** Two public call surfaces (`AdminAlert`, `AdminInfo`) instead of one, which is a small ongoing cost to callers deciding which to use — the rule of thumb is: use `AdminAlert` if the message concerns app health and should escalate with severity; use `AdminInfo` for one-off status posts that were only ever going to one channel anyway.
