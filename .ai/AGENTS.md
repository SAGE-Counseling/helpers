# Agent Protocol

## Required startup steps

1. Read:
    - `.ai/PROJECT_STATE.md`
    - `.ai/GUARDRAILS.md`
    - `.ai/CONTEXT.md`
    - Open issues: `gh issue list --state open --json number,title,body,labels,assignees`

`PROJECT_STATE.md` holds only the current snapshot (what's working, what's broken, current decisions, next
milestone). Task history lives in closed issues — query the tracker instead of keeping a local archive file.

2. Identify project type and toolchain (confirm from `.ai/CONTEXT.md` — don't guess).
3. Select exactly one open, unassigned issue, or create a new one.
4. Claim the issue before coding: `gh issue edit <n> --add-assignee @me`.

## Branching rules

- One branch per task.
- Branch naming: `ai/claude/<issue-number>-<slug>`.
- Never work directly on main/master.

## Collision avoidance

- Do not modify files already touched by another assigned (claimed) issue.
- If unavoidable, stop and coordinate by commenting on both issues.
- Claiming is not atomic. Immediately before assigning yourself, re-check the issue. If a different agent
  claimed it or touched the same files since you last checked, do not assign yourself; pick a different
  issue or stop and surface the conflict.

## Allowed scope

- Work only within the claimed issue's scope.
- No opportunistic fixes or refactors.

## Moving a task to PR status

This is a solo-developer-plus-agents project; PRs here are not a review gate — they exist mainly so the
human maintainer (Miri) has a diff to look at before merging. Keep this step lightweight.

The canonical sequence, in order:

1. Claim the issue (see Required startup steps above).
2. Create the branch (see Branching rules above).
3. Do the work on that branch — commit as you go.
4. All new and existing relevant tests pass locally.
5. If the change is non-trivial (new feature, non-obvious logic, anything touching a security/data boundary,
   or a diff you'd want a second pair of eyes on — use judgment), run whatever review tooling this repo has
   configured and address or consciously dismiss its findings before opening the PR.
6. Push the branch to origin.
7. Post the completion requirements below as a comment on the issue.
8. Open the PR, referencing the issue (so merging auto-closes it); post the PR link back to the issue as a
   comment.
9. Return to the main branch (`git checkout main`/`master`) so the working tree isn't left sitting on the
   task branch.

**Merging is the human's call, not the agent's.** The PR is Miri's review surface for the file changes —
she reviews and merges (or requests changes) on GitHub herself. Do not merge the PR yourself unless
explicitly instructed to (see `.ai/GUARDRAILS.md`'s "Do not merge into main without explicit instruction").

## After a PR merges

If the repo doesn't auto-delete branches on merge:

- Delete the remote branch.
- Delete the local branch if checked out elsewhere.
- Merged branches should not be left around — history is preserved on main regardless.

## Stalled or wrong-approach tasks

- If an issue cannot proceed due to an external dependency or missing information, comment with the reason.
  Leave yourself assigned if you expect to resume; if waiting on someone else, apply the `needs-info` label
  (see `docs/agents/triage-labels.md`).
- If a task is abandoned because the approach was wrong or it's no longer needed, do not leave yourself
  assigned. Comment "abandoned: <why>", remove yourself as assignee, then stop and ask whether to close the
  issue or leave it open for someone else. Return to the main branch before stopping.

## Completion requirements

Each completed task must provide, as an issue comment:

- Summary: what changed and why
- Files changed: list
- How to test: exact commands
- Risks or follow-ups
- Confirmation that `PROJECT_STATE.md` was updated

Then close the issue (or let the merged PR's auto-close do it).
