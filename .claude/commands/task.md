Create a task file in `.plans/tasks/`.

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

## Instructions

1. If $ARGUMENTS includes a description, use it. Otherwise ask the user what the task is about.
2. If the user provides a SCON number, use the ticket template and `scon-` filename. Otherwise use the draft template and `draft-` filename.
3. Write the task file following the template and writing style above.
4. Report the file path when done.
