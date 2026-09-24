# Timezone Recommendation: Default All Sites to America/Phoenix

## The problem this replaces

All three sites currently handle "Arizona time" by hardcoding a fixed UTC offset (`-5` or `-7` hours, depending on what the input was assumed to already be in) at whatever call site happens to touch a date — Credible EHR responses, ROI authorization queries, display formatting. The audit found at least four independent implementations of this, all slightly different, one with a `strpos($item, 'utc')` truthiness bug that can silently misfire (`strpos` returning `0` for a match at position 0 is falsy).

The underlying fact behind all of this is correct: **Arizona does not observe DST**, so `America/Phoenix` really is a fixed UTC-7 offset, every day of the year, forever. That's exactly why it was tempting to hardcode `-7` and move on. But encoding that fact as a magic number instead of the named IANA timezone is what let it drift — every call site has to independently know and re-verify "oh right, no DST here," and every one of them a chance to get the sign or the hour count wrong (as at least one already has).

## Firm recommendation

**Use the named timezone `America/Phoenix` everywhere, never a manual offset.** Specifically:

### 1. Set the application default timezone via `.env`

In each site's `config/app.php`, change:

```php
'timezone' => 'UTC',
```

to:

```php
'timezone' => env('APP_TIMEZONE', 'America/Phoenix'),
```

And add to each site's `.env` (and `.env.example`):

```
APP_TIMEZONE=America/Phoenix
```

This is a one-line, framework-level change per site. It makes `now()`, `Carbon::now()`, `date()`, scheduled-task timestamps, and log timestamps all default to Phoenix time automatically — no call-site-level offset math needed for anything that doesn't explicitly say otherwise. Since Phoenix has no DST, this value never needs to change and never needs a "did DST just flip" bug check.

### 2. Keep the database in UTC

Don't change how dates are *stored*. Continue storing `created_at`/`updated_at` and other timestamps in UTC in the database — that's the correct practice regardless of app-level display timezone, and changing it now would be a much bigger, riskier migration than fixing display-time conversion. The app-timezone change in step 1 affects how Carbon *displays/interprets* naive datetimes by default; it does not rewrite what's in the database.

### 3. Fix the Credible/external-API date-parsing call sites explicitly

The current hardcoded-offset hacks exist because responses from Credible sometimes come back as UTC and need converting for display. Once `APP_TIMEZONE=America/Phoenix` is set, replace every hand-rolled offset with an explicit, named conversion:

```php
// Instead of: Carbon::parse($value, 'UTC')->subHours(5)
Carbon::parse($value, 'UTC')->setTimezone('America/Phoenix');
```

Never infer "this value is UTC" from a `strpos()` string check on the field name — that's the source of the current bug. Each integration point should say explicitly which timezone the source system returns, once, in one place.

### 4. Centralize this in the shared package's date helper

Once (1)-(3) land at each site, `sage-counseling/helpers` should ship a small date module (see the `helpers`, `dates`, and `audit log` shape mentioned for the package going forward) with, at minimum:

- A named constant for the app timezone (`SageCounseling\Helpers\Dates::TIMEZONE = 'America/Phoenix'`) rather than repeating the string.
- A `fromUtc($value): Carbon` helper that does the explicit `Carbon::parse($value, 'UTC')->setTimezone(self::TIMEZONE)` conversion, so every site calls one function instead of reimplementing it.
- The corrected `showDate()`/`showDateTime()` display helpers already identified in the earlier candidates list, now built on top of this.

### 5. Rollout order

Do the `.env`/`config/app.php` change **first and separately** from any date-helper code changes — it's low-risk, immediately fixes `now()`/log-timestamp accuracy everywhere, and gives you a stable, correct baseline to write the Credible-parsing fixes against. Trying to fix the Credible offset bugs before the app-level timezone is correct just relocates the same class of bug.

## Why not just fix the offset math in place?

Because the audit found the same "fix" reinvented differently at least four times, with at least one confirmed bug in the reinvention. A magic number encodes a fact without saying so; `America/Phoenix` *is* the fact, self-documenting, and immune to a future "wait, does Arizona observe DST or not" re-litigation. There's no scenario where hardcoding `-7` is better than naming the zone once PHP/Carbon are already timezone-database-aware, which they are.
