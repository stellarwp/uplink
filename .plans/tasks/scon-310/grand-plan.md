# Grand Plan: Kadence AI Credits

## The question

Kadence AI uses a credit system. Credits are consumed when users generate AI
content. Uplink v3 is becoming the unified licensing layer for all StellarWP
products. What (if anything) should Uplink do about credits?

## Verified facts

All starting assumptions from SCON-310 have been verified. See
`complete-credit-flow.md` for exact code references.

1. Kadence Blocks (free plugin) has ALL credit code. kadence-blocks-pro has NONE.
2. Balance comes from `content.startertemplatecloud.com/wp-json/kadence-credits/v1/get-remaining` via WP PHP proxy → credit relay plugin → `licensing.kadencewp.com` (v3 API).
3. AI generation goes directly from browser JS to Prophecy. The credit relay plugin hooks `prophecy/rest/authorized` to deduct credits before Prophecy processes the request.
4. Auth: `X-Prophecy-Token` header (base64 JSON) for AI generation; plain query param for balance.
5. HTTP 423 = credits exhausted.
6. V4 licensing has credits as a **separate** endpoint (`GET /api/stellarwp/v4/credits`), not part of the validation response.
7. Credits are per-license + per-site in the v4 model. Optional product scoping.
8. Credits are generic — not Kadence-specific. V4 defines 8 credit types (`AI`, `EMAIL`, `STORAGE`, etc.).
9. Kadence currently uses a single unified pool. Costs vary by operation (1-8 per wizard context, 1-2 for inline text).
10. Kadence's Uplink branch (`bugfix/multisite-token-logic`) is multisite support, unrelated to credits.

## System diagram

Full details in `black-boxes.md`. The diagram below shows the current (v3) flow.

```
┌──────────────────┐         ┌──────────────────────────────────────────────┐
│   Browser        │         │  content.startertemplatecloud.com            │
│  (Kadence AI UI) │         │  (WordPress site running Prophecy + Credits) │
│                  │         │                                              │
│  ai-text.js ─────────────────▶ prophecy/v1/proxy/generate/content        │
│  fetch-ai.js     │  direct │  prophecy/v1/proxy/transform/{type}         │
│                  │         │  prophecy/v1/analytics/event                 │
│  localStorage:   │         │                                              │
│  credits cache   │         │  kadence-credits/v1/get-remaining            │
└────────┬─────────┘         └──────────────────▲───────────────────────────┘
         │ WP REST                              │ wp_remote_get
         ▼                                      │
┌──────────────────┐                            │
│  WordPress Site  │                            │
│  (Kadence Blocks)│────────────────────────────┘
│                  │
│  /kb-design-library/v1/get_remaining_credits  │
│  (proxies to remote credits API)              │
│                  │
│  License key from Uplink:                     │
│  get_license_key('kadence-blocks-pro')        │
│  get_license_key('kadence-creative-kit')      │
│  get_license_key('kadence-blocks')            │
└──────────────────┘
```

## What's left (needs team input)

All code investigation is complete. The remaining questions require people, not code:

1. **Migration timeline** — When is v4 licensing going live for Kadence credits?
2. **Data migration** — Are existing v3 credit balances migrated to v4, or fresh start?
3. **Commerce Portal** — How does buying a plan create credit pools?
4. **Relay plugin ownership** — Who updates `kadence-prophecy-licensing-service-connect` for v4?
5. **Dual-running** — Will v3 and v4 run simultaneously? How to avoid double-counting?

See `migration-plan.md` for the full analysis of migration options.
