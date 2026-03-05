Help the user think through a task and produce a task file in `.plans/tasks/`.

This is a conversation, not a one-shot generation. Work with the user to understand the problem before writing anything.

## Process

1. **Understand the problem.** If $ARGUMENTS gives context, use it as a starting point. Ask the user what's wrong or missing today and why it matters. Explore the codebase if needed to ground the discussion in what actually exists.

2. **Clarify the desired outcome.** Talk through what the solution should look like at a high level. Push back if the user is jumping to implementation details. The task should capture what and why, not step-by-step how.

3. **Draft the task.** Once the problem and outcome are clear, write the task file and show it to the user. Ask if anything needs to change.

4. **Report the file path** when the user is happy with it.

## Naming

When the user provides a SCON ticket number, use `scon-{number}-{short-title}.md`.

When no ticket number is known yet (the common case), use `draft-{short-title}.md`. The file gets renamed once the Jira ticket is created.

The short title is a few lowercase hyphenated words summarizing the task.

## Writing style

Focus on the **what** and the **why**. The Problem section should make it clear what's wrong or missing today and why it matters. The Proposed solution section should describe the desired outcome and constraints, not step-by-step implementation instructions. Only get into the how if there's a non-obvious technical decision that needs to be captured (e.g., which WordPress hook to use, which existing abstraction to extend).

Write in plain, direct language. No em dashes. No colons as sentence interrupters.

## Metadata

The frontmatter uses these fields:

- `ticket` (optional): The SCON ticket number once assigned, e.g. `SCON-240`. Omit for drafts.
- `status` (required): One of `draft`, `todo`, `in-progress`, `done`
- `pr` (optional): GitHub PR number when work is in progress or done, e.g. `"#145"`
- `url` (optional): Jira link, added when the ticket is created. Pattern: `https://stellarwp.atlassian.net/browse/SCON-{number}`

## Template

```markdown
---
status: draft
---

# {Title}

## Problem

{What's wrong or missing today, and why it matters.}

## Proposed solution

{The desired outcome and any constraints. Keep it focused on what should change, not how to implement it line by line.}
```

## Template (with ticket)

```markdown
---
ticket: SCON-{number}
status: todo
url: https://stellarwp.atlassian.net/browse/SCON-{number}
---

# {Title}

## Problem

{What's wrong or missing today, and why it matters.}

## Proposed solution

{The desired outcome and any constraints. Keep it focused on what should change, not how to implement it line by line.}
```
