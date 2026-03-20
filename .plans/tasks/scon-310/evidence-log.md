# Evidence Log

Timestamped investigation sessions. Full details live in the docs linked below —
this log exists only to show what was discovered when.

> **Repo links:** Kadence Blocks at [`7a88d8bd`](https://github.com/stellarwp/kadence-blocks/commit/7a88d8bd). Licensing at [`15eafb31`](https://github.com/stellarwp/licensing/commit/15eafb31).

---

## Session 5 (2026-03-17): Credit relay plugin deep dive

Read `kadence-prophecy-licensing-service-connect` end to end. Key findings:

- The plugin is a **pure relay** — stores nothing locally, every credit operation proxies to `licensing.kadencewp.com` (v3 API).
- Credit deduction hooks `prophecy/rest/authorized`, calls `POST /api/stellarwp/v3/license/credits/usage`.
- Balance check calls `GET /api/plugins/v2/license/validate`, returns `results[0].credits.available`.
- Server-side cost: 1 credit default, 2 if text > 400 chars, `count($prompts)` for wizard — matches client-side logic.
- **Correction:** v3 includes credits in the validation response; v4 does NOT (separate endpoint).
- Hardcoded bypass exists for `stellarwp.com` domain (temp workaround).
- No renewal/reset logic — must happen via Commerce Portal → licensing service.

→ Details: `black-boxes.md` §4, `migration-plan.md`

## Session 4 (2026-03-17): Identifying server-side repos

GitHub org search across `stellarwp` for `kadence-credits/v1`, `prophecy/v1`, `startertemplatecloud`.

- `content.startertemplatecloud.com` is a WordPress site running two plugins: `prophecy-wordpress` (AI routes) + `kadence-prophecy-licensing-service-connect` (credit relay).
- Deployment repo: `stellarwp/prophecy-kadence`. Infrastructure: `stellarwp/ansible` under `kadence-prophecy/`.
- Four client repos call the server: kadence-blocks, kadence-starter-templates, kadence-creative-kit, kadence-frontend-wizard.

→ Details: `black-boxes.md` §3-4

## Session 3 (2026-03-17): V4 licensing service investigation

Read the `stellarwp/licensing` codebase. Found a complete credit system already built:

- Three layers: pool → site allocation → usage tracking.
- 8 credit types, 5 period types, REST endpoints for balance/pool/allocation/usage.
- Credits are NOT in the v4 validation response — separate `GET /credits` endpoint.
- Hard cap enforced server-side. Period refresh anchored to `purchase_date`.

→ Details: `complete-credit-flow.md` §9, `black-boxes.md` §5

## Session 2 (2026-03-17): Kadence Blocks deep code analysis

- Mapped the full license key resolution chain (Pro → Creative Kit → Blocks).
- Credit balance = plain integer string from remote API, `parseInt()` on the JS side, no server-side caching.
- Credit costs are variable: 1-2 for inline text, 1-8 per wizard context, ~70 for full site.
- Two Uplink domain functions: `get_license_domain()` (credit API) vs `get_original_domain()` (Prophecy token).
- `Ai_Cache` caches generated content (not balances) — cached content avoids credit consumption.

→ Details: `complete-credit-flow.md` §1-8

## Session 1 (2026-03-17): Initial investigation

- ALL credit code is in kadence-blocks (free plugin). kadence-blocks-pro has NONE.
- Browser JS calls Prophecy directly for AI generation; credit balance goes through WP PHP proxy.
- Auth: `X-Prophecy-Token` header (base64 JSON) for AI generation, plain query param for balance.
- Kadence's Uplink branch (`bugfix/multisite-token-logic`) is multisite support, NOT credit-related.

→ Details: `complete-credit-flow.md` §1-4, `black-boxes.md` §1-2
