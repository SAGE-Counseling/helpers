# Queued Delivery: own delivery step, Urgent always immediate

**Status:** accepted

Sites can opt into queued Deliveries (#22) so a slow or failing SMTP server or Teams webhook doesn't hold up the code that raised an alert. Two choices here would look odd later without an explanation.

**Decision 1: a small delivery step of our own, not Laravel Notifications.** `AdminAlert` and `AdminInfo` pass each `(Channel, AdminMessage)` pair to one shared delivery step. That step either calls the registered sender right away or dispatches one encrypted (`ShouldBeEncrypted`) queued job per Channel. The job looks up the real sender in `ChannelRegistry` when it runs. The registry only ever holds real senders, so a job can't re-queue itself. We considered moving delivery onto Laravel Notifications, which bi-reflector's legacy admin alerts already use, but rejected it. Teams isn't a built-in notification channel, so both senders would have to be rewritten as notification channels. The routing would also end up expressed in Laravel's notification model instead of the fixed Channel Map ([ADR-0001](./0001-fixed-severity-channel-map.md)) and the separate `AdminInfo` entry point ([ADR-0002](./0002-admininfo-separate-entry-point.md)).

**Decision 2: `Urgent` Deliveries are always immediate, even with queueing on.** An `Urgent` alert can be about the queue itself, such as a job's `failed()` handler. The failure we worry about most is Horizon having stopped (e.g. after a deploy) while Redis is still up. In that state a dispatch succeeds and the job just sits there, so "fall back to immediate if dispatch throws" wouldn't catch it. `Urgent` alerts are rare, and nearly every call site already runs inside a worker or a command, so the extra latency is acceptable.

**Consequences:** An `Urgent` alert still waits on its transports. To limit that, immediate Deliveries catch and log sender failures instead of throwing into the caller, and the Teams HTTP call has a timeout. Queuing mostly benefits `Info`/`Warning` alerts and `AdminInfo`.
