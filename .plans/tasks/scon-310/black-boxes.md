# Black Boxes

Each section is a system/codebase involved in the credit flow.

> **Repo links:** Kadence Blocks at [`7a88d8bd`](https://github.com/stellarwp/kadence-blocks/commit/7a88d8bd). Licensing at [`15eafb31`](https://github.com/stellarwp/licensing/commit/15eafb31).

---

## 1. Kadence Blocks (FREE WordPress plugin)

- **Repo:** [stellarwp/kadence-blocks](https://github.com/stellarwp/kadence-blocks)
- **Role in credit flow:** ALL credit code lives here — AI UI, credit balance fetching, Prophecy proxy calls, event tracking.
- **Key files:**
  - [`includes/class-kadence-blocks-prebuilt-library-rest-api.php`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php) — REST controller, credit fetching, token header generation
  - [`includes/class-kadence-blocks-ai-events.php`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php) — Prophecy event tracking (credits_before/after/used)
  - [`includes/helper-functions.php`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php) — License data retrieval (`kadence_blocks_get_current_license_data()`)
  - [`src/blocks/advancedheading/ai-text/fetch-ai.js`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js) — Direct browser→Prophecy API calls
  - [`src/blocks/advancedheading/ai-text/ai-text.js`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js) — AI text block UI, credit display, localStorage caching
  - [`src/plugins/prebuilt-library/data-fetch/constants.js`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/plugins/prebuilt-library/data-fetch/constants.js) — `CONTEXT_PROMPTS` credit cost matrix
  - [`includes/resources/Cache/Ai_Cache.php`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/resources/Cache/Ai_Cache.php) — Server-side AI content caching

---

## 2. Kadence Blocks Pro (WordPress plugin)

- **Repo:** `stellarwp/kadence-blocks-pro`
- **Role in credit flow:** NONE. Zero matches for credit, prophecy, get-remaining, or 423.

---

## 3. Prophecy SaaS (AI engine)

- **URL:** `https://content.startertemplatecloud.com/wp-json/prophecy/v1/`
- **Repo:** [`stellarwp/prophecy-wordpress`](https://github.com/stellarwp/prophecy-wordpress) (also [`stellarwp/composer-prophecy-wordpress`](https://github.com/stellarwp/composer-prophecy-wordpress))
- **Deployment:** [`stellarwp/prophecy-kadence`](https://github.com/stellarwp/prophecy-kadence) (site for `gen.startertemplatecloud.com`). Ansible in [`stellarwp/ansible`](https://github.com/stellarwp/ansible) under `kadence-prophecy/`.
- **Role in credit flow:** AI content generation. Fires `prophecy/rest/authorized` filter before processing — the credit relay plugin (§4) hooks this to validate the license and deduct credits. Prophecy itself does NOT check credit balance. Returns 423 when the relay denies the request.
- **Endpoints:**
  - `POST /proxy/generate/content` — Generate AI content
  - `POST /proxy/transform/{type}` — Transform content (improve, simplify, lengthen, shorten, spelling, tone)
  - `POST /analytics/event` — Event tracking
  - `POST /images/generate-alt-tags` — Image alt tags
  - `POST /suggest/layout` — Page layouts (kadence-creative-kit)
  - `POST /content/create_page` — Page content (kadence-creative-kit)
- **Auth:** `X-Prophecy-Token` header — base64 JSON: `{domain, key, site_name, product_slug, product_version, env}`

---

## 4. Credit Relay Plugin (on startertemplatecloud)

- **URL:** `https://content.startertemplatecloud.com/wp-json/kadence-credits/v1/get-remaining`
- **Repo:** [`stellarwp/kadence-prophecy-licensing-service-connect`](https://github.com/stellarwp/kadence-prophecy-licensing-service-connect)
- **Hosted where:** Same WordPress site as Prophecy (`content.startertemplatecloud.com`)
- **Role in credit flow:** A **pure relay** — stores nothing locally. Proxies all credit operations to the v3 licensing service at `licensing.kadencewp.com`.
- **Operations:**
  1. **Balance check** (`GET /kadence-credits/v1/get-remaining`): calls `licensing.kadencewp.com/api/plugins/v2/license/validate`, returns `results[0].credits.available` as a plain integer
  2. **Credit deduction** (on Prophecy requests): hooks `prophecy/rest/authorized`, calls `licensing.kadencewp.com/api/stellarwp/v3/license/credits/usage`
  3. **Admin operations** (`add-credits`, `remove-credits`): protected by a hardcoded secret
- **Credit cost logic:** `count($prompts)` if prompts array present, otherwise 1; overridden to 2 if the route is `/prophecy/v1/proxy/` and text > 400 chars (the text-length check **overwrites** the prompts count, not max/add).
- **Auth:** SHA256 keyed hash (`hash('sha256', $data . $secret)`) for signed requests to the licensing service. License key as query param for incoming balance requests.
- **Source files:** [`class-kadence-plsc.php`](https://github.com/stellarwp/kadence-prophecy-licensing-service-connect), [`class-kadence-license-relay.php`](https://github.com/stellarwp/kadence-prophecy-licensing-service-connect)

---

## 5. V4 Licensing Service

- **Repo:** [stellarwp/licensing](https://github.com/stellarwp/licensing) (private)
- **Local path:** `/Users/owl/www/startups/licensing-service/wp-content/plugins/licensing`
- **Role in credit flow:** Future source of truth. Has a complete generic credit system ready but not yet used for Kadence.

Full technical details (tables, endpoints, response shapes, business logic) are in `complete-credit-flow.md` §9. Summary:

- **Three layers:** pool (license-level) → site allocation → usage tracking
- **8 credit types:** `DEFAULT`, `AI`, `AUTO_DNS`, `EMAIL`, `EMAIL_OVERAGE`, `IMPORTS`, `SITES`, `STORAGE`
- **5 periods:** `DAY`, `WEEK`, `MONTH`, `YEAR`, `LIFETIME`
- **Separate endpoint:** `GET /api/stellarwp/v4/credits` — NOT part of the validation response
- **Hard cap enforced.** Rejects usage exceeding remaining credits.
- **Concurrency safe.** Row-level locks on allocation.
- **Period refresh** anchored to `subscription.purchase_date`, not calendar boundaries.

Docs in the licensing repo: [`docs/credits.md`](https://github.com/stellarwp/licensing/blob/15eafb31/docs/credits.md), [`docs/rest/v3/signed.md`](https://github.com/stellarwp/licensing/blob/15eafb31/docs/rest/v3/signed.md), [Postman collection](https://github.com/stellarwp/licensing/blob/15eafb31/docs/stellar-licensing-api-v4.postman_collection.json).

---

## 6. Kadence's Uplink branch

- **Branch:** `bugfix/multisite-token-logic`
- **What it does:** Multisite token & licensing refactor — network-level license/token storage, `Token_Factory`, `License_Manager` pipeline. NOT related to credits.
- **Why:** Kadence supports multisite WP installs where one license key covers the whole network.

---

## 7. Commerce Portal

- **Role in credit flow:** Unknown. No evidence found in any client codebase.

---

## 8. Browser (Kadence AI UI)

See `complete-credit-flow.md` §2-4 for full details. Summary:

- AI generation calls go **directly** from browser to Prophecy (not proxied through WP)
- Credit balance fetch goes through WP REST → PHP → startertemplatecloud → relay → licensing service
- Credits cached in localStorage key `kadenceBlocksPrebuilt` as `{ credits: integer }`
- CSS class `kb-ai-credits-out` when credits = 0
- Event tracking sends `credits_before`, `credits_after`, `credits_used`
