# Helper Consolidation Candidates

Source review of the three sites' global helper files:

- `bi-reflector/app/Helpers/sageHelper.php`
- `rps/app/Helpers/sagehelpers.php`
- `compliance-portal/app/Helpers/sagehelpers.php`

Goal: identify functions to pull into `sage-counseling/helpers`. This is step one of the consolidation — followed by [`full-source-audit.md`](./full-source-audit.md) (full codebase review) and its recorded scope decisions.

**Package shape going forward:** `sage-counseling/helpers` will hold general-purpose helpers plus a dedicated date/timezone module (see [`timezone-recommendation.md`](./timezone-recommendation.md)). The `AuditLog` module found during the full source audit is explicitly *not* going here — see [`audit-log-handoff.md`](./audit-log-handoff.md) for why it's becoming its own package instead.

## Already duplicated across sites — extract first

These exist in more than one site today, so every day they stay duplicated is a day the copies can drift further.

- **`log_exception(Throwable $exception, ?string $customMessage, ?string $function[, ?string $connection])`**
  In all three sites, nearly identical. Each resolves `app(\App\Services\ExceptionLogger::class)->logException(...)`. bi-reflector's signature also takes a `$connection` param the other two don't.
  To extract: the package can't depend on each app's own `ExceptionLogger` class, so this needs either (a) an interface the package defines and each app binds its concrete logger to, or (b) a configurable class-name resolved via `config('sage-helpers.exception_logger')`. Reconcile the `$connection` param difference across sites first.

- **`showDate($date)`**
  In rps and compliance-portal. rps's version is correct (parses string dates via `Carbon::parse($date)->toDateString()`); compliance-portal's Carbon-parsing branch is commented out, so it silently returns `''` for any string date — likely a live bug there (see cleanup list below). Package should ship the rps behavior.

- **`adminMessage($message)` / `adminErrorMessage($message)`**
  In bi-reflector and rps, but the implementations diverge: bi-reflector notifies a `User` model instance found via `config('sage.admin_account')`; rps's `adminMessage` does the same, but `adminErrorMessage` instead routes an ad-hoc mail notification via `Notification::route('mail', ...)`. Needs a design decision on which pattern to standardize before extracting — these aren't safe to merge as-is.

## Generic today, single-site — strong package candidates

No Laravel-app-specific dependencies (or only a facade the package can require), currently living in rps or compliance-portal only. These can likely move over close to as-is.

- **CSV helpers** (rps): `filterToCsv(array $data): string`, `filterAllToCsv(array $data)`, `makeFullCsv(array $data)`, `make_csv($data)`, `make_csv_helper($data)`. Pure PHP, no framework dependency. Note `make_csv_helper` looks like an abandoned alternate implementation of `make_csv` (quotes non-numeric values, joins with `,` instead of using `fputcsv`) — worth deciding if it's still needed or dead.
- **`file_build_path(...$segments): string`** (rps) — trivial `implode(DIRECTORY_SEPARATOR, ...)` wrapper, pure PHP.
- **`array_to_xml(array $arr, SimpleXMLElement $xml)`** (rps) — recursive array-to-XML converter, pure PHP/SimpleXML.
- **`filterArrayComma(array $data)` / `filterStringComma(string $data)`** (rps) — recursively escapes commas in array values, pure PHP.
- **`highlight($haystack, $term)`** (rps) — wraps search-term matches in a `<span>`, pure PHP/string functions.
- **`toSnakeArray(array $data)`** (rps) — converts array keys to snake_case. Currently uses `Illuminate\Support\Str::snake()`; the package's own `SageCounseling\Helpers\Str` class could grow a `snake()` method so this doesn't need an Illuminate dependency, or the package can just require `illuminate/support` as a dependency if that's acceptable.
- **`showDateTime($date)`** (compliance-portal) — thin `Carbon::parse($date)->toDateTimeString()` wrapper, natural companion to `showDate`.

## App-specific — not portable as-is

Depend on models/notification classes that differ per app, or need real redesign before they're shareable.

- **`credible_credential(string $key)`** (bi-reflector) — tied to `App\Models\CredibleCredential`, which is bi-reflector-specific (Credible EHR export). Not shared.
- **`teamsInfo` / `teamsWarning` / `teamsUrgent`** (rps) — tied to app-specific `TeamsInfoNotification`/etc. classes and `config('services.teams.webhook_url')`. The *pattern* (severity-leveled Teams notifications) is reusable, but would need the notification classes themselves standardized and moved into the package (or a package-level Notification base class) first.
- **`warningEmail` / `warningEmailThrottle`** (rps) — tied to `App\Models\AdminReport`. The Redis-throttle code path in `warningEmailThrottle` is unreachable (see cleanup list).
- **`filterArrayDates($array)`** (compliance-portal) — the `array_filter` callback returns a `Carbon` instance instead of a `bool`, so its actual filtering behavior is likely broken. Needs a redesign, not a straight extraction.

## Cleanup found along the way (flag for the full-source audit, not package material)

- `getSetting($id) {}` (rps) — empty stub, does nothing. Dead code.
- `warningEmailThrottle` (rps) — has a `return;` before the `Redis::throttle(...)` call, making that whole branch unreachable dead code (comment says `// todo get redis to work`).
- `showDate` (compliance-portal) — Carbon-parsing branch is commented out; string dates silently return `''` instead of the formatted date. Likely a live bug, not just leftover debug code.
- `filterArrayDates` (compliance-portal) — filter callback doesn't return a boolean; behavior is probably not what was intended.
- `make_csv_helper` (rps) — appears to be an unused/abandoned duplicate of `make_csv`; confirm whether anything still calls it before deciding to port it.

## Next step

Full source audit of each site (beyond the helper files) for other consolidation and consistency opportunities.
