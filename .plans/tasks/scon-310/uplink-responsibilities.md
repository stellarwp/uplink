# Uplink v3 Credit Responsibilities

## Current state (how it works WITHOUT Uplink)

Today, Kadence Blocks handles credits entirely on its own:

1. **Balance check:** PHP proxies to `content.startertemplatecloud.com` → credit relay → `licensing.kadencewp.com` (v3 API)
2. **AI generation:** Browser JS calls Prophecy directly with `X-Prophecy-Token` header
3. **Auth:** License key from Uplink's `get_license_key()`, resolved: kadence-blocks-pro → kadence-creative-kit → kadence-blocks
4. **Caching:** localStorage in browser, file-based `Ai_Cache` on server (content only, not balance)
5. **Exhaustion:** HTTP 423 from Prophecy, UI error message

Uplink's only current role: providing the license key and domain.

## V4 licensing service

The v4 service already has a complete generic credit system (separate `GET /api/stellarwp/v4/credits` endpoint, 8 credit types, per-license + per-site model, hard cap enforcement). See `complete-credit-flow.md` §9 for full technical details.

## Decisions made

| Decision                          | Answer                                                                                                              |
| --------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| Generic or Kadence-specific?      | **Generic.** Any StellarWP product can use credits. Pools scoped per-product or per-tier (exact TBD).               |
| Part of validation or separate?   | **Separate.** `GET /credits` is its own endpoint.                                                                   |
| Source of truth?                  | **V4 licensing service** (today it's `startertemplatecloud.com` → v3, migrating to v4).                             |
| Data shape?                       | V4 returns `[{ credit_type, product_slug, allocated, used, remaining, period }]`. Uplink can pass through or adapt. |
| Credit exhaustion handling?       | **Deferred.** Each product handles 423 errors on its own today. Uplink helpers possible later.                      |

## MUST do

- **Generic credits concept** — product-agnostic, not Kadence-specific.

## MUST NOT do

- **Credit consumption** — stays in Prophecy. Uplink is read-only for credits.
- **AI content generation proxy** — browser calls Prophecy directly, this should not change.

## CAN do later

- **Surface credit balance** via Uplink's public API (global functions, REST endpoints) so products don't each build their own credit-fetching code.
- **Cache credit balance** alongside licensing data in Uplink's existing cache layer.

No urgency — products can call the v4 credits endpoint directly, or Uplink can wrap it.

## What Uplink already provides that credits depend on

| Function                           | Purpose                                           |
| ---------------------------------- | ------------------------------------------------- |
| `get_license_key($slug)`           | License key for Prophecy token and credit API     |
| `get_license_domain()`             | `site` param in credit balance API calls          |
| `get_original_domain()` [1]        | `domain` field in the Prophecy token              |
| `is_authorized()`                  | Gates event tracking                              |
| `get_authorization_token($slug)`   | Token for authorization check (not Prophecy auth) |

**[1]** Only on Uplink's `bugfix/multisite-token-logic` branch, not `main`. Kadence pins this branch via Composer.

## Remaining dependencies

- Kadence team input on what they need from Uplink (team unavailable — discovery from code only)
- Migration plan from startertemplatecloud to v4 — see `migration-plan.md`
