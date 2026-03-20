# Credit System Migration Plan

> **Status:** Not started — this doc captures what we know so decisions can be made.
> **Parent:** [SCON-310 README](README.md)

## What's migrating

Credits need to move from the **legacy v3 licensing service** to the **v4 licensing service**. The v4 service already has a complete credit system built and ready.

## Current architecture (what exists today)

### Systems and their responsibilities

| System                         | Repo                                                                                                                            | Responsibility                                                                                      | Stores credit data?                          |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- | -------------------------------------------- |
| **Kadence Blocks** (WP plugin) | [stellarwp/kadence-blocks](https://github.com/stellarwp/kadence-blocks)                                                         | UI, credit display, AI generation calls, event tracking                                             | No (localStorage cache only)                 |
| **Prophecy** (AI SaaS)         | [stellarwp/prophecy-wordpress](https://github.com/stellarwp/prophecy-wordpress)                                                 | AI content generation, fires `prophecy/rest/authorized` filter                                      | No                                           |
| **Credit relay plugin**        | [stellarwp/kadence-prophecy-licensing-service-connect](https://github.com/stellarwp/kadence-prophecy-licensing-service-connect) | Intercepts Prophecy requests, deducts credits via licensing API, exposes `get-remaining` endpoint   | No — pure relay                              |
| **V3 licensing service**       | [stellarwp/licensing](https://github.com/stellarwp/licensing) (v3 API)                                                          | **Source of truth.** Stores credit pools, handles deduction, returns balance in validation response | **YES**                                      |
| **V4 licensing service**       | [stellarwp/licensing](https://github.com/stellarwp/licensing) (v4 API)                                                          | Future source of truth. Complete credit system already built (pools, allocations, usage tracking)   | **YES** (ready but not used for Kadence yet) |
| **Uplink** (PHP library)       | [stellarwp/uplink](https://github.com/stellarwp/uplink)                                                                         | Provides license key and domain to Kadence. No credit involvement today                             | No                                           |

### Request flow today

```
Browser (Kadence AI UI)
  │
  ├── GET credit balance ──▶ WP REST ──▶ PHP proxy ──▶ startertemplatecloud
  │                                                      │
  │                                      credit relay plugin
  │                                         │
  │                                         ▼
  │                               licensing.kadencewp.com
  │                                  /api/plugins/v2/license/validate
  │                                  (returns credits.available)
  │
  └── POST AI generation ──▶ startertemplatecloud (direct, browser→server)
                                │
                    credit relay plugin hooks prophecy/rest/authorized
                                │
                                ▼
                      licensing.kadencewp.com
                         /api/stellarwp/v3/license/credits/usage
                         (deducts credits, returns success/fail)
                                │
                                ▼
                      Prophecy processes request (if credits OK)
                      or returns 423 (if credits exhausted)
```

## V3 → V4 differences

| Aspect             | V3 (current)                                                                     | V4 (target)                                                                        |
| ------------------ | -------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| Balance endpoint   | `GET /api/plugins/v2/license/validate` (credits embedded in validation response) | `GET /api/stellarwp/v4/credits` (separate endpoint)                                |
| Balance response   | `results[0].credits.available` → plain integer                                   | `{ credits: [{ credit_type, product_slug, allocated, used, remaining, period }] }` |
| Deduction endpoint | `POST /api/stellarwp/v3/license/credits/usage`                                   | `POST /api/stellarwp/v4/credits/usage`                                             |
| Credit model       | Single pool per license                                                          | Three layers: pool → site allocation → usage                                       |
| Credit types       | Implicit (just "credits")                                                        | Explicit enum: AI, EMAIL, STORAGE, IMPORTS, etc.                                   |
| Per-site tracking  | No                                                                               | Yes (site allocations from pool)                                                   |
| Period handling    | Unknown                                                                          | Anchored to `subscription.purchase_date`                                           |
| Auth               | Signed hash (HMAC-SHA256)                                                        | TBD                                                                                |

## What needs to change (by system)

### 1. Credit relay plugin (`kadence-prophecy-licensing-service-connect`)

This is the main piece that needs updating. Options:

**Option A: Update the relay to call v4 instead of v3.**

- Change `use_credits()` to call `POST /api/stellarwp/v4/credits/usage`
- Change `validate_license()` balance read to call `GET /api/stellarwp/v4/credits`
- Adapt to new response shape (array of credit objects instead of single integer)
- Minimal blast radius — Prophecy and Kadence Blocks don't change

**Option B: Move credit logic into Prophecy itself.**

- Prophecy calls v4 directly for credit checks/deduction
- Remove the relay plugin entirely
- Bigger change but cleaner architecture

**Option C: Move credit logic into Uplink.**

- Uplink provides credit balance and deduction as a library feature
- Kadence calls Uplink, Uplink calls v4
- Doesn't help with Prophecy-side deduction (browser→Prophecy is direct)

### 2. Kadence Blocks

- `get_remaining_credits` currently returns a plain integer. V4 returns a richer object. Either the relay adapts the response (keeps Kadence unchanged) or Kadence updates its credit parsing.
- If Option A, **no changes needed** (relay adapts the response shape).

### 3. Uplink

- No changes required for migration. Uplink's role (providing license key + domain) doesn't change.
- Can optionally add credit balance surfacing later (see `uplink-responsibilities.md`).

### 4. V4 licensing service

- Needs Kadence credit pools created/configured
- Needs credit type mapping (current implicit "credits" → `AI` type in v4)
- Need to confirm: does `POST /credits/usage` on v4 work the same way as v3's deduction endpoint?

## Open questions for the team

1. **Data migration:** Are existing credit balances migrated from v3 to v4, or does v4 start fresh?
2. **Timeline:** When is v4 licensing going live for Kadence?
3. **Commerce Portal:** How does credit provisioning work? When someone buys a Kadence plan, what creates the credit pool?
4. **Dual-running period:** Will v3 and v4 run simultaneously? If so, how do we avoid double-counting?
5. **Who owns the relay plugin update?** Is this Uplink team, Kadence team, or Licensing team?
6. **Prophecy auth:** Will Prophecy's `prophecy/rest/authorized` filter mechanism stay the same in the v4 world?
