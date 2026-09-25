# Guardrails

These rules are always in effect unless a task explicitly overrides them.

## Critical (must not be violated)

- No secrets in code, state files, logs, commits, or tests.
- Changes to logic, validation, orchestration, or security boundaries require tests.
  - Exception: schema migrations that only add/modify/drop columns, tables, or indexes do not themselves
    need a test. If a migration ships alongside logic that reads/writes the changed schema, that logic
    still needs tests per the normal rule. (This repo has no database — see "Database safety" below.)
- One task per branch. One branch per task.
- Do not change behavior outside the task scope.
- **NO MAIN/MASTER COMMITS:** Under no circumstances should work be performed directly on the main branch
  — **except** the interactive-session exception below.
- **BRANCH VALIDATION:** Before any file modification, the agent must verify it is on a branch matching the
  pattern `ai/claude/<issue-number>-<slug>`.
- **FAILURE STATE:** If the agent is on the main branch, it must STOP and request the user to create a
  branch, provide the command to create one, or create the branch and switch to it — unless the
  interactive-session exception below applies.
- **DOCS/CONFIG EXCEPTION (standing):** Documentation and AI-instruction/config files — `.ai/*.md`,
  `docs/**`, root `CONTEXT.md`, `CLAUDE.md`/`AGENTS.md`-style files, `README.md` — may always be worked on
  and committed directly to main/master. No issue, no branch, no per-turn confirmation needed. This does
  **not** cover code: anything under `src/`, `tests/`, `config/` (the published app config, not agent docs),
  or that changes the app's runtime behavior.
  - **Very small code changes** (a one-line fix, a typo in a comment, a trivial rename) may also skip the
    issue — but need the user's explicit approval in the session first, every time. They still don't need a
    branch unless the user asks for one.
  - Anything bigger than "very small," or that changes behavior, still requires an issue tied to it and the
    full branch/PR flow below.
  - If there is any doubt about whether a change qualifies as docs/config or "very small," treat it as code
    and use the normal branch/issue/PR flow instead.
- **INTERACTIVE-SESSION EXCEPTION:** This rule exists to protect unsupervised/AFK agent runs and
  multi-agent collision avoidance, where a branch is the only checkpoint before a bad change lands. It is
  not needed when a human is live in the session watching each tool call. In a live interactive session,
  the agent may commit directly to main — skipping the issue/branch/PR flow — only when **all** of the
  following hold:
  - The user is present in the session and explicitly authorizes the direct commit (a standing instruction
    from an earlier turn does not count; ask each time) — except for the standing docs/config exception
    above, which needs no per-turn ask.
  - The change is low-risk and small in scope: documentation, comments, or config tweaks — not logic,
    validation, orchestration, security boundaries, or anything requiring tests per the Critical rule above.
  - If there is any doubt about whether a change qualifies, treat it as out of scope for this exception and
    use the normal branch/issue/PR flow instead.
- **UNCOMMITTED CHANGES FOUND IN THE WORKING TREE:** If files show up modified/staged with no clear
  indication of who changed them (not something this session did), do not discard them, stash them away
  silently, or "fix" them back to the committed/default state. Miri may have made local changes and simply
  forgotten to commit. Leave them exactly as found, flag them to her (what changed, in which files), and let
  her decide whether to keep, commit, or discard them.
- **NO DESTRUCTIVE DB/STATE COMMANDS OUTSIDE THE TEST RUNNER:** Never run a full-reset/wipe command (or its
  programmatic equivalent, e.g. from a REPL/console) against real data — even one written specifically to
  "verify" something. Not applicable to this repo's own codebase today (no database — see "Database
  safety"), but applies in full once notification-sending code exists: never fire a real email/Teams/SMS
  send while "verifying" something — see "Database safety" for the equivalent trap in this repo.

## Important (default expectations)

- Keep diffs small and scoped to the task.
- No drive-by refactors unless explicitly allowed by the task.
- Every change must report:
  - Files changed
  - How to test (exact commands)
- If this repo has a human-facing narrative-docs directory separate from agent context (e.g. a
  `docs/vault/`), don't read or search it when researching code changes. Use it only if explicitly asked to
  update it. (No such directory exists here today — everything in `docs/` is agent/maintainer-facing
  planning material.)

## Progress tracking

- Update `.ai/PROJECT_STATE.md` and post a completion comment on the issue before marking a task done;
  close the issue (or reference it so the merge closes it).
- If progress stalls, comment on the issue with the reason. Leave yourself assigned if you expect to
  resume; unassign if abandoning the approach.

## Git rules

- Do not run git commands unless explicitly instructed by the user or task.
- Do not merge into main without explicit instruction.
- Do not use:
  - `git push --force`
  - `git merge --squash`
  - `git rebase`
  - `git commit --amend`
    unless explicitly instructed and you understand the consequences.
- If unsure, stop and ask.

## PHP guardrails (PHP projects only)

This is a framework-agnostic Composer library (`composer.json` requires only `php: ^8.1` — no
`illuminate/support` or other framework dependency today). Guardrails:

- PSR-4 autoloading under `SageCounseling\Helpers\` (`src/`) and `SageCounseling\Helpers\Tests\` (`tests/`)
  — new files must land in the namespace matching their directory.
- No new global helper functions. This package exists specifically to replace the three consuming apps'
  global `app/Helpers/*.php` files with typed, namespaced classes — see `docs/helper-consolidation-candidates.md`.
  New functionality is a class/method, not a global function.
- Don't add a framework dependency (`illuminate/support` or otherwise) to make an implementation shorter —
  e.g. `docs/helper-consolidation-candidates.md` explicitly flags this tradeoff for a `snake()`-style
  helper. If a task seems to need one, stop and ask rather than adding it opportunistically.
- Favor typed properties, parameters, and return types (PHP 8.1+ features — readonly properties, enums,
  first-class callable syntax) over untyped/dynamic code.
- Follow the routing/entry-point boundaries already decided for the notifications module — see root
  `CONTEXT.md` and `docs/adr/0001-fixed-severity-channel-map.md` / `docs/adr/0002-admininfo-separate-entry-point.md`
  before adding anything that looks like a channel override on `AdminAlert`.

## Database safety

- This repo has no database and no persistent state of its own — it's a pure utility/Composer library.
  There is no test-isolation mechanism to reason about beyond PHPUnit's default per-process execution
  (`phpunit.xml`, bootstrapping `vendor/autoload.php`).
- The equivalent trap here, once the admin-notifications module is implemented: a "quick verification" of
  `AdminAlert`/`AdminInfo` must never fire a real Mail send, real Teams webhook POST, or (later) real
  ClickSend SMS. Tests must fake/mock the transport (e.g. Laravel's `Mail::fake()` in a consuming app's
  integration test, or an injected fake HTTP client in this package's own unit tests) — never hit a real
  endpoint "just to check it works."
- If a task genuinely needs to hit a real external endpoint (e.g. confirming a Teams webhook URL is valid),
  say so explicitly and confirm with the user first — per the general destructive/external-action rules.

## Test stance

- Default test command: `vendor/bin/phpunit`
- Pure logic changes require unit tests in `tests/`, mirroring the `src/` namespace structure (see
  `tests/StrTest.php` for the existing pattern). Any new public class or method needs at least one test
  covering its documented behavior. Once notification channels exist, channel-sending code needs a test
  using a faked/mocked transport (see "Database safety" above) — never a live send.

## Quality tools (optional)

- Formatter: not configured.
- Static analysis: not configured.
- Do not introduce new tools unless instructed.

## Version compatibility

Targets PHP ^8.1 per `composer.json`. This package must stay installable by all three consuming apps
(bi-reflector, rps, compliance-portal) — check their PHP versions before requiring a newer language feature
if that ever becomes unclear. No framework version constraint applies today since the package has no
framework dependency.
