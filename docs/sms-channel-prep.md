# SMS Channel (ClickSend): Prep Checklist

**Status:** blocked on credentials and decisions. The API key is in hand (2026-09-28) and the sending number is pending.

This is the remaining work for the planned `Sms` Channel (see `CONTEXT.md`, [ADR-0001](./adr/0001-fixed-severity-channel-map.md), and [admin-notifications-contract.md](./admin-notifications-contract.md)). Development can start before the number arrives. The phone number only matters for the first real send.

## 1. Information to gather

- [ ] **ClickSend API username.** ClickSend uses HTTP Basic auth with username + API key, so the key alone isn't enough. The username is usually the account email and is shown next to the key under Dashboard → API Credentials.
- [x] **API key.** Received 2026-09-28.
- [ ] **Sending number** (pending). This becomes the `from` field.
- [ ] **Admin mobile number(s).** Who receives the texts (see decision 3).
- [ ] **US carrier registration.** Confirm with ClickSend whether the number needs **toll-free verification** (toll-free number) or **10DLC brand/campaign registration** (local number), and whether they handle it. US carriers block business texts from unregistered numbers. The API can report `SUCCESS` and the text still never arrives. Approval can take days to weeks, so start this first.

### Proposed `.env` values

```
SAGE_CLICKSEND_USERNAME="..."
SAGE_CLICKSEND_API_KEY="..."
SAGE_SMS_FROM="+1XXXXXXXXXX"
SAGE_ADMIN_PHONE="+1XXXXXXXXXX"
```

As with Teams, the Sms channel **no-ops** when these are unset. Numbers are in E.164 format (`+1` followed by 10 digits).

## 2. Decisions needed

1. **What goes in the text.** SMS is plaintext and passes through carriers. Urgent alerts often carry exception messages that may contain PHI.
   - *Recommended:* send a minimal text and never the message body, e.g. `URGENT — RPS (Prod): <subject>. See email.`
   - *Alternative:* get a BAA with ClickSend and send more detail.
2. **Flood protection.** A crash loop firing Urgent alerts could send hundreds of paid texts in minutes. Options: rate-limit (e.g. N texts per hour), de-duplicate (e.g. the same subject at most once per 15 minutes), or both. ADR-0001 doesn't cover this. Record whatever is chosen as a new ADR.
3. **One recipient or several.** `CONTEXT.md` defines a single Admin. Is that one phone number, or should the on-call person or a list of people get texts? More than one number means `SAGE_ADMIN_PHONE` becomes a list, and the glossary needs updating.
4. **Naming.** `teams.app_label` (`SAGE_TEAMS_APP_LABEL`) would now also label SMS. Either rename it to a shared `SAGE_APP_LABEL` (keeping the old name working as a fallback) or leave it as is.

## 3. Implementation plan

- Add an `Sms` case to `Channel` (`src/Notifications/Channel.php`).
- Add `Channel::Sms` to the `Urgent` row in `ChannelMap` (`src/Notifications/ChannelMap.php`) and remove the "not yet a Channel case" comment. No call-site changes are needed in bi-reflector, rps, or compliance-portal.
- Add an `SmsChannelSender` that implements `ChannelSender` and no-ops when it isn't configured.
- **Add a dedicated ClickSend client instead of reusing `HttpPoster`.** `HttpPoster::postJson()` doesn't work for this, for two reasons:
  - It sends no auth header, and ClickSend needs Basic auth.
  - It returns `void` and ignores the response. ClickSend returns **HTTP 200 even when a message fails**, and the real result is in each message's `data.messages[].status` (`SUCCESS`, `INVALID_RECIPIENT`, `INSUFFICIENT_CREDIT`, `INVALID_SENDER_ID`, `THROTTLED`, `COUNTRY_NOT_ENABLED`, ...). A status other than `SUCCESS` must count as a failed Delivery, meaning it's logged and never turned into another alert.
  - Use the same pattern as Teams: a small interface plus a Guzzle adapter, with the same 5-second connect/request timeout.
- Wire up registration and config in `SageHelpersServiceProvider` and `config/sage-helpers.php`.
- Update `CONTEXT.md` (Channel and Channel Map entries), `admin-notifications-contract.md` (env table and config sample), ADR-0001 ("Sms pending"), and the README.
- Urgent Deliveries always send immediately (ADR-0003), so SMS sends inline and never through the queue. Keep the timeout short.
- The ClickSend MCP isn't needed. It's for sending texts from inside Claude Code, and this uses the plain REST API.

## 4. ClickSend API reference

- **Docs:** https://developers.clicksend.com/docs
- **Base URL:** `https://rest.clicksend.com/v3`
- **Auth:** `Authorization: Basic base64(username:api_key)`
- **Send:** `POST /v3/sms/send`

```json
{
  "messages": [
    {
      "source": "sage-helpers",
      "from": "+1XXXXXXXXXX",
      "to": "+1XXXXXXXXXX",
      "body": "URGENT — RPS (Prod): Queue worker down. See email."
    }
  ]
}
```

- **Response** (HTTP 200 even if a message failed, so check each `status`):

```json
{
  "http_code": 200,
  "response_code": "SUCCESS",
  "response_msg": "Messages queued for delivery.",
  "data": {
    "total_count": 1,
    "queued_count": 1,
    "blocked_count": 0,
    "messages": [
      { "message_id": "…", "status": "SUCCESS", "to": "+1…", "message_price": "0.0792" }
    ]
  }
}
```

- `SUCCESS` means *queued*, not *delivered*. Delivery reports are a separate feature and out of scope for now.
- Optional per-message fields: `schedule`, `custom_string`.

## 5. Testing rules

- Unit tests use a **fake** ClickSend client only. Per `.ai/GUARDRAILS.md`, a test or "quick check" must never send a real text.
- One live test text to confirm credentials and number registration happens **only with Miri's explicit go-ahead**, after the number is registered.
- Tests to cover: no-op when unconfigured; request shape and auth header; a non-`SUCCESS` per-message status counts as a failure; a timeout or HTTP error is logged and doesn't throw into the caller; `ChannelMap` Urgent row includes Sms.

## Next steps once the info is in

1. Settle decisions 1–4, possibly in a `/grill` session, and record any ADR and `CONTEXT.md` changes.
2. Open a GitHub issue for the Sms channel, with credentials and registration as a separate checklist item.
3. Implement on a feature branch following the normal issue → branch → PR flow.
