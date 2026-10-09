# Deploy versioning

Every production deploy gets a calendar version, is tagged in git, and is shown to users. The version logic
lives in this package as `SageCounseling\Helpers\Laravel\ReleaseVersion`, so an app uses it directly instead of
copying a class. RPS adopted the scheme first (RPS #262) and holds the reference Envoy deploy wiring.

## The scheme

`YYYY.MM.NN`, every segment at least two digits: `2026.10.01`, `2026.10.02`, then `2026.11.01`.

- `YYYY.MM` is the year and month of the production deploy, in the app's timezone (`APP_TIMEZONE`, default
  `America/Phoenix`).
- `NN` counts production deploys within that month, starting at `01`. It goes past `99` without wrapping.
- The version is assigned when the deploy runs, not when work is merged. Every tag is a build that went live
  on production, and only production deploys take a number.
- It is not semver (semver forbids leading zeros), so never put it in `composer.json`'s `version` field.
- The Laravel version is not part of the tag. The app reads it at runtime with `app()->version()`.

## The class

| Method | Returns |
| --- | --- |
| `ReleaseVersion::next(array $existingTags, DateTimeInterface $now)` | The version the next deploy takes, e.g. `2026.10.03`. Tags not exactly `YYYY.MM.NN` are ignored. |
| `ReleaseVersion::fromFile(string $path)` | The trimmed contents of a release's `VERSION` file, or `dev` when the file is missing or empty. |
| `ReleaseVersion::label()` | `v2026.10.01 · Laravel 13.x.y`, or `dev · Laravel 13.x.y`. For signed-in users. |
| `ReleaseVersion::number()` | `v2026.10.01`, or `dev`. For pages external users see; leaves out the Laravel version. |

`label()` and `number()` read `config('app.version')` (set from `fromFile()`, below), so they show the version
baked into the release's config cache. `number()` returns `dev` when that value is `dev`, empty or unset.
The separator in `label()` is U+00B7 (middle dot).

## How it works

1. **Pick the version (locally).** In `Envoy.blade.php`'s `@setup`, when the task is `deploy`, Envoy runs
   `git fetch --tags origin <branch>`, pins the commit at the tip of `origin/<branch>`, and passes the local
   tag list to `ReleaseVersion::next()`. If the fetch fails, the deploy stops before anything reaches the
   server.
2. **Write it into the release (server).** The clone task checks that the clone's `HEAD` is the pinned commit
   (if the branch moved in between, the deploy stops and you run it again), and writes the version to
   `VERSION` in the release directory. The server clones with `--depth=1` and has no tags, which is why the
   version travels as a file.
3. **Read it at runtime.** `config/app.php` sets
   `'version' => ReleaseVersion::fromFile(base_path('VERSION'))`. The deploy's `config:cache` bakes it in.
   With no `VERSION` file (local, dev) it is `dev`. `VERSION` is in `.gitignore`.
4. **Tag it (locally, once it's live).** Envoy's `@after` hook runs after each task that succeeds. Right
   after the task that points `current` at the new release, it creates an annotated tag on the pinned commit
   and pushes it to `origin`. Tagging there, not in `@success`, means a release that went live is tagged
   even if a later task such as `cache` fails, so no two builds share a number. If tagging or pushing
   fails, the release is still live, and Envoy prints the commit and version to tag by hand.
5. **Show it.** Call `ReleaseVersion::label()` for signed-in users or `ReleaseVersion::number()` for external
   pages. RPS shows the label in its main layout footer and in the Filament panel's sidebar footer
   (`PanelsRenderHook::SIDEBAR_FOOTER`). Use the sidebar hook, not `PanelsRenderHook::FOOTER`, because the
   plain footer hook also renders on the panel's login page.

Rollback needs nothing extra. Each release directory keeps its own `VERSION` and config cache, so pointing
`current` back at an older release shows that release's version. A rollback takes no new number.

## Adding it to an app

The app needs to deploy with Envoy from a local clone whose `origin` the developer can push tags to, and to
depend on `sage-counseling/helpers`.

1. Do not copy the class or its test. Use `SageCounseling\Helpers\Laravel\ReleaseVersion`.
2. Add the `version` key to `config/app.php`:
   `'version' => \SageCounseling\Helpers\Laravel\ReleaseVersion::fromFile(base_path('VERSION'))`.
   Add `/VERSION` to `.gitignore`.
3. In `Envoy.blade.php` (RPS's `Envoy.blade.php` is the reference; the blocks below are named after it):
   - Make sure `@setup` loads `vendor/autoload.php` and the `.env` so it can use `ReleaseVersion` and
     `env()`.
   - Copy the `if ($__task === 'deploy') { … }` block from RPS's `@setup`, using the package class. Change
     `'deploy'` if the app's production story has another name.
   - In the task that clones the release, copy the `@if (isset($version)) … @endif` block after the clone.
     Adjust the directory variable if it isn't `$currentReleaseDir`.
   - Make sure `config:cache` runs after the clone task, so the cached config picks up `VERSION`.
   - Copy the `@after` tagging block. Change `'update_symlinks'` to whichever task switches the live
     release in that app.
4. Show `ReleaseVersion::label()` (or `number()`) wherever users should see it, plus the Filament sidebar
   render hook if the app has a panel. Add a feature test asserting it renders there, using RPS's
   `tests/Feature/ReleaseVersionDisplayTest.php` as the model.
5. Check the deploy without running it: `envoy run deploy --pretend` prints the server script. The clone task
   should show the commit check and `echo "YYYY.MM.NN" > …/VERSION`. Pretend mode fetches tags locally but
   never tags.
6. On the first real deploy, confirm the new tag on GitHub (`git ls-remote --tags origin`) and the version in
   the footer.

## Edge cases

- **Two deploys at once** (two people, or a retry while one is running) could pick the same number. The second
  tag push fails, and Envoy prints the fallback message. Tag that commit with the next free number by hand.
- **A task fails before `current` switches** (e.g. `composer` or `migrate`): nothing went live and nothing
  is tagged, so the next deploy reuses the number. The failed release directory keeps a `VERSION` file but
  is never served.
- **A task fails after `current` switches** (e.g. `cache` or `restart_workers`): the release is already
  tagged. Fix the cause and deploy again; that deploy takes the next number.
- **Tags made by hand** must follow `YYYY.MM.NN` exactly, or `next()` ignores them.
