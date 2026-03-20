# Complete Credit Flow: Technical Reference

> Full technical reference with exact code links. This is the canonical source
> for how credits work — other docs reference sections here.

## Repositories

| Repo                                                                    | Branch / Commit                                                                      | Role                          |
| ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | ----------------------------- |
| [stellarwp/kadence-blocks](https://github.com/stellarwp/kadence-blocks) | `master` / [`7a88d8bd`](https://github.com/stellarwp/kadence-blocks/commit/7a88d8bd) | All credit code lives here    |
| [stellarwp/licensing](https://github.com/stellarwp/licensing)           | [`15eafb31`](https://github.com/stellarwp/licensing/commit/15eafb31)                 | V4 licensing service (server) |
| [stellarwp/uplink](https://github.com/stellarwp/uplink)                 | `bucket/consolidation-program`                                                       | Library under development     |

---

## 1. License Key Resolution

Credits depend on a license key. The key is resolved with a priority fallback chain.

### Function: `kadence_blocks_get_current_license_key()`

[`includes/helper-functions.php:154-164`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L154-L164)

```
Priority 1: kadence-blocks-pro   (only if class Kadence_Blocks_Pro exists)
Priority 2: kadence-creative-kit (only if class KadenceWP\CreativeKit\Core exists)
Priority 3: kadence-blocks       (always checked, this is the free plugin)
```

Each call uses Uplink's `get_license_key($slug)` ([imported at line 14](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L14)). First non-empty key wins.

### Function: `kadence_blocks_get_current_license_data()`

[`includes/helper-functions.php:218-233`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L218-L233)

Returns a statically-cached array:

```php
[
    'key'     => kadence_blocks_get_current_license_key(),     // :154
    'email'   => kadence_blocks_get_current_license_email(),   // :184
    'product' => kadence_blocks_get_current_product_slug(),    // :169
    'env'     => kadence_blocks_get_current_env(),             // :200
]
```

- `email` is only populated from legacy `kt_api_manager_kadence_gutenberg_pro_data` option when no Pro key exists ([`:184-195`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L184-L195))
- `product` follows the same fallback chain as the key but returns the slug string ([`:169-179`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L169-L179))
- `env` maps `STELLARWP_UPLINK_API_BASE_URL` to `'dev'`/`'staging'`/`''` ([`:200-211`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L200-L211))

### Function: `kadence_blocks_get_deprecated_pro_license_data()`

[`includes/helper-functions.php:263-290`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L263-L290)

Legacy fallback for old Kadence themes (Pinnacle Premium, Ascend Premium, Virtue Premium). Reads from `kt_api_manager` option. This is NOT the main path — only used for email retrieval.

---

## 2. Credit Balance Fetching

### Path: Browser → WP REST → PHP → startertemplatecloud → relay plugin → licensing service

```
Browser JS
  └─ apiFetch({ path: '/kb-design-library/v1/get_remaining_credits' })
      └─ PHP: get_remaining_credits()          // :769-779
          └─ PHP: get_license_keys()           // :783-797
          └─ PHP: get_remote_remaining_credits() // :2518-2552
              └─ wp_safe_remote_get() to content.startertemplatecloud.com
                  └─ Credit relay plugin (kadence-prophecy-licensing-service-connect)
                      └─ licensing.kadencewp.com/api/plugins/v2/license/validate
                          └─ Returns results[0].credits.available (plain integer)
```

### WP REST endpoint registration

[`includes/class-kadence-blocks-prebuilt-library-rest-api.php`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php)
(search for `get_remaining_credits` route registration — it's in the `register_routes()` method)

### Remote API call details

[`includes/class-kadence-blocks-prebuilt-library-rest-api.php:2518-2552`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L2518-L2552)

**URL:** `https://content.startertemplatecloud.com/wp-json/kadence-credits/v1/get-remaining`

**Query params:**

| Param         | Source                                       | Required |
| ------------- | -------------------------------------------- | -------- |
| `site`        | `get_license_domain()` (Uplink function)     | Yes      |
| `key`         | `$this->api_key` (from license resolution)   | Yes      |
| `plugin_slug` | `'kadence-blocks-pro'` or `'kadence-blocks'` | Yes      |
| `email`       | From legacy license data (if present)        | No       |
| `env`         | `'dev'`/`'staging'`/`''`                     | No       |

**Auth:** NONE (no header auth — license key is in the query string)

**Product slug logic** ([`:2519`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L2519)): If the resolved product is `kadence-blocks-pro`, send that; otherwise default to `kadence-blocks`. The `kadence-blocks-auth-slug` filter can override this.

**Response:** Raw body string passed through. The JS side calls `parseInt(response)` (see [`ai-text.js:93`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L93)), which means the remote API returns a **plain integer as a string** (e.g. `"47"`).

**Error handling:** Returns the string `'error'` on WP error or non-2xx response code.

**Caching:** NONE on the PHP side. No transients, no file cache. Every call hits the remote API.

### Browser-side caching

[`src/blocks/advancedheading/ai-text/ai-text.js:75-101`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L75-L101)

- Credits stored in `localStorage` key `kadenceBlocksPrebuilt` as `{ credits: <integer> }`
- On component mount, reads from localStorage; if missing, uses sentinel value `'fetch'`
- When `currentCredits === 'fetch'`, triggers `getRemoteAvailableCredits()` ([`:97-100`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L97-L100))
- After each AI operation, sets `credits` to `'fetch'` to trigger a refetch ([`:162`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L162), [`:218`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L218), [`:276`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L276))
- `tempCredits` provides an optimistic UI update: `currentCredits - promptCost` ([`:161`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L161), [`:217`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L217), [`:275`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L275))

**Same pattern also in:** [`src/plugins/prebuilt-library/pattern-library.js:241-246, 904-908`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/plugins/prebuilt-library/pattern-library.js#L241-L246)

---

## 3. AI Content Generation (Credit Consumption)

### Path: Browser → Prophecy directly (NOT proxied through WP)

[`src/blocks/advancedheading/ai-text/fetch-ai.js`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js)

### API functions exposed

| Function                | Endpoint                                                    | Code                                                                                                                                        |
| ----------------------- | ----------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `getAIContent()`        | `POST /prophecy/v1/proxy/generate/content`                  | [`fetch-ai.js:25-65`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js#L25-L65)     |
| `getAITransform()`      | `POST /prophecy/v1/proxy/transform/{type}`                  | [`fetch-ai.js:66-115`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js#L66-L115)   |
| `getAIEdit()`           | `POST /prophecy/v1/proxy/transform/{type}`                  | [`fetch-ai.js:116-161`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js#L116-L161) |
| `getAvailableCredits()` | `GET /kb-design-library/v1/get_remaining_credits` (WP REST) | [`fetch-ai.js:13-24`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js#L13-L24)     |

### X-Prophecy-Token (browser-side construction)

[`src/blocks/advancedheading/ai-text/fetch-ai.js:30-39`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js#L30-L39)

```javascript
const token = {
    domain:          window.kadence_blocks_params.proData.domain || url.hostname,
    key:             window.kadence_blocks_params.proData.api_key || '',
    site_name:       window.kadence_blocks_params.site_name || '',
    product_slug:    window.kadence_blocks_params.pSlug || '',
    product_version: window.kadence_blocks_params.pVersion || '',
    env:             window.kadence_blocks_params.env || '',
};
// Header value: btoa(JSON.stringify(token))
```

### X-Prophecy-Token (PHP-side construction)

[`includes/class-kadence-blocks-prebuilt-library-rest-api.php:3017-3037`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L3017-L3037)

```php
$defaults = [
    'domain'          => get_original_domain(),
    'key'             => $license_data['key'],
    'site_name'       => sanitize_title( get_bloginfo('name') ),
    'product_slug'    => apply_filters('kadence-blocks-auth-slug', $product_slug),
    'product_version' => KADENCE_BLOCKS_VERSION,
];
// Optional: 'env' added if non-empty
// Returns: base64_encode( json_encode( $parsed_args ) )
```

### Request bodies

**`getAIContent(prompt)`** ([`:41-52`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js#L41-L52)):

```json
{ "prompt": "<string>", "lang": "en-US", "stream": true }
```

**`getAITransform(content, type)`** ([`:81-101`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js#L81-L101)):

```json
{ "text": "<string>", "stream": true, "lang": "en-US" }
```

Types: `improve`, `simplify`, `lengthen`, `shorten`, `spelling`, `tone`

**`getAIEdit(content, prompt, type)`** ([`:131-147`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js#L131-L147)):

```json
{ "text": "<string>", "prompt": "<string>", "stream": true, "lang": "en-US" }
// For type==='tone': uses "tone" key instead of "prompt"
```

### Response handling

- **HTTP 200:** Returns `response.body` (a `ReadableStream` — content is streamed)
- **HTTP 423:** Returns `Promise.reject('credits')` — credits exhausted
- **Other errors:** Returns `Promise.reject(message)`

### Credit cost

Credits are NOT a flat rate — costs vary by operation type.

**Inline AI text** ([`src/blocks/advancedheading/ai-text/ai-text.js`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js)):

- `promptCost` defaults to `1` ([`:62`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L62))
- If text length > 400 characters, cost becomes `2` ([`:136-140`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L136-L140))
- Events report `credits_used: 1` ([`:170`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L170), [`:227`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L227), [`:284`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L284)) — but this is hardcoded and may not match actual server deduction

**AI Wizard content generation** ([`src/plugins/prebuilt-library/data-fetch/constants.js:128-156`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/plugins/prebuilt-library/data-fetch/constants.js#L128-L156)):

Each content context has a different credit cost (the `CONTEXT_PROMPTS` map):

| Context             | Credits | Context          | Credits |
| ------------------- | ------- | ---------------- | ------- |
| `value-prop`        | 6       | `team`           | 5       |
| `products-services` | 8       | `work`           | 5       |
| `about`             | 5       | `faq`            | 1       |
| `achievements`      | 4       | `welcome`        | 5       |
| `call-to-action`    | 4       | `news`           | 1       |
| `testimonials`      | 1       | `blog`           | 2       |
| `get-started`       | 4       | `contact-form`   | 1       |
| `pricing-table`     | 1       | `subscribe-form` | 1       |
| `location`          | 3       | `careers`        | 5       |
| `history`           | 4       | `donate`         | 5       |
| `mission`           | 4       | `events`         | 5       |
| `profile`           | 4       | `partners`       | 3       |
|                     |         | `industries`     | 5       |
|                     |         | `volunteer`      | 5       |
|                     |         | `support`        | 4       |

**AI Wizard full site generation** costs ~70 credits total (displayed in wizard UI).

**Key insight:** The `CONTEXT_PROMPTS` values appear to represent the number of AI prompts/jobs for each context, not direct credit costs. Each prompt likely costs 1 credit on the server. So "products-services: 8" means 8 AI prompts = 8 credits.

---

## 4. Credit Exhaustion Handling

### Browser-side credit display (4 locations)

1. **Inline AI text toolbar** — [`ai-text.js:978`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L978)
   Shows "Credits Remaining: X", red when 0 (`kb-ai-credits-out` CSS class, [`editor.scss:599`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/editor.scss#L599))

2. **Pattern library buttons** — [`pattern-list.js:185`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/plugins/prebuilt-library/pattern-list.js#L185)
   Shows "Generate Content (X Credits)" with context-specific amounts

3. **AI Wizard** — [`ai-wizard/components/wizard/index.js`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/plugins/prebuilt-library/ai-wizard/components/wizard/index.js)
   Shows "(70 Credits)" for full site content generation

4. **Dashboard banner** — [`src/dashboard/components/large-banner/index.js:154`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/dashboard/components/large-banner/index.js#L154)
   Shows "X Credits Available" in info icon popover

- Error message: "Error, Can not generate AI content because of insufficient credits."

### PHP-side (wizard content generation)

[`includes/class-kadence-blocks-prebuilt-library-rest-api.php:2240-2244`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L2240-L2244)

```php
if ( ! empty( $error_message['detail'] ) && 'Failed, unable to use credits.' === $error_message['detail'] ) {
    return 'credits';
}
```

The Prophecy server returns JSON `{ "detail": "Failed, unable to use credits." }` alongside a non-2xx status when credit use fails. The string `'credits'` is returned to the frontend.

---

## 5. Server-Side AI Content Caching

[`includes/resources/Cache/Ai_Cache.php`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/resources/Cache/Ai_Cache.php) (extends `Block_Library_Cache`)

- Caches **AI-generated content** (NOT credit balances)
- Storage: filesystem via a `Storage` driver, with hashed filenames
- Cache key: `hash(['kadence-ai-generated-content', $identifier])`
- Used at [`:634`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L634) — after a successful AI content job, the response body is cached
- **Impact on credits:** Using cached content avoids a Prophecy API call, so NO credits are consumed for cached results

---

## 6. Event Tracking

### PHP event handler

[`includes/class-kadence-blocks-ai-events.php`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php)

Events are sent via `do_action('kadenceblocks/ai/event', $name, $context)` and forwarded to `POST https://content.startertemplatecloud.com/wp-json/prophecy/v1/analytics/event`.

### Gating: events only fire if authorized

[`includes/class-kadence-blocks-ai-events.php:100-110`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L100-L110)

```php
$token         = get_authorization_token('kadence-blocks');
$license_key   = kadence_blocks_get_current_license_key();
$is_authorized = is_authorized($license_key, 'kadence-blocks', $token, get_license_domain());
if (!$is_authorized) return;
```

Uses three Uplink functions: `get_authorization_token()`, `is_authorized()`, `get_license_domain()`.

### Events tracked

| Event label                    | Context fields                                                                         | Code                                                                                                                                                      |
| ------------------------------ | -------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `ai_wizard_started`            | (none)                                                                                 | [`ai-events.php:179-180`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L179-L180)                |
| `ai_wizard_update`             | org type, location, industry, description, keywords, tone, etc.                        | [`ai-events.php:183-197`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L183-L197)                |
| `ai_wizard_complete`           | Same as update                                                                         | [`ai-events.php:198-212`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L198-L212)                |
| `pattern_added_to_page`        | pattern id, slug, name, style, is_ai, context, categories                              | [`ai-events.php:213-224`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L213-L224)                |
| `ai_inline_completed`          | tool_name, type, initial_text, result, **credits_before, credits_after, credits_used** | [`ai-text.js:163-171`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/ai-text.js#L163-L171)                 |
| `Context Generation Requested` | context_name, is_regeneration                                                          | [`REST API:1762-1768`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L1762-L1768) |
| `Context Generation Failed`    | context_name, is_regeneration, errors                                                  | [`REST API:1781-1788`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L1781-L1788) |
| `Context Generation Completed` | context_name, **credits_after**, is_regeneration                                       | [`REST API:1822-1829`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L1822-L1829) |

**Key insight:** `credits_after` in "Context Generation Completed" actually calls `get_remote_remaining_credits()` ([`:1826`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L1826)) — it makes a fresh API call to get the real balance after content generation.

---

## 7. All Uplink Functions Used by Kadence Credits

| Uplink Function                    | Used In                                                                                                                                                                                                                                                                                                                                                                                                                                          | Purpose                                 |
| ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------- |
| `get_license_key($slug)`           | [`helper-functions.php:14`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L14), [`:155`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L155), [`:159`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L159), [`:163`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/helper-functions.php#L163) | Resolve license key with fallback chain |
| `get_license_domain()`             | [`REST API:18`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L18), [`:2521`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L2521), [`ai-events.php:106`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L106)                            | Domain for credit API `site` param      |
| `get_original_domain()` [1]        | [`REST API:19`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L19), [`:3019`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L3019), [`ai-events.php:146`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L146)                            | Domain for Prophecy token               |
| `is_authorized()`                  | [`ai-events.php:106`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L106)                                                                                                                                                                                                                                                                                                                | Gate event tracking                     |
| `get_authorization_token($slug)`   | [`ai-events.php:102`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L102)                                                                                                                                                                                                                                                                                                                | Token for authorization check           |

**[1] Branch note:** `get_original_domain()` exists only on Uplink's `bugfix/multisite-token-logic` branch (line 277 of `functions.php`), which Kadence pins via Composer (`"stellarwp/uplink": "dev-bugfix/multisite-token-logic"`). It is **not on Uplink `main`**. It returns the site domain without any hash suffix via `Data::get_domain( true )`.

**Note:** `get_license_domain()` and `get_original_domain()` are used in DIFFERENT contexts:

- `get_license_domain()` → credit balance API `site` param ([`:2521`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L2521))
- `get_original_domain()` → Prophecy token `domain` field ([`:3019`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L3019)) and some library calls ([`:1365`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L1365), [`:1443`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L1443), [`:2301`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L2301), [`:2357`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L2357))

---

## 8. Two Authentication Patterns (Summary)

| Operation      | Auth Method                             | Where Constructed                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| -------------- | --------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Credit balance | License key as query param (`?key=...`) | PHP only: [`REST API:2520-2524`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L2520-L2524)                                                                                                                                                                                                                                                                                     |
| AI generation  | `X-Prophecy-Token` header (base64 JSON) | JS: [`fetch-ai.js:30-45`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/src/blocks/advancedheading/ai-text/fetch-ai.js#L30-L45), PHP: [`REST API:3017-3037`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-prebuilt-library-rest-api.php#L3017-L3037), [`ai-events.php:145-162`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L145-L162) |
| Event tracking | `X-Prophecy-Token` header (base64 JSON) | PHP: [`ai-events.php:125`](https://github.com/stellarwp/kadence-blocks/blob/7a88d8bd/includes/class-kadence-blocks-ai-events.php#L125)                                                                                                                                                                                                                                                                                                                  |

---

## 9. V4 Licensing Service Credit System

The v4 licensing service ([stellarwp/licensing](https://github.com/stellarwp/licensing)) already has a **complete generic credit system** that will replace the legacy `startertemplatecloud.com` credits API.

### Three-layer architecture

```
Credit Pool  (license-level)     ──  total credits for a license/product/type
  └─ Site Credit  (site-level)   ──  allocation from pool to a specific site activation
       └─ Site Credit Usage      ──  consumption tracking per site
```

### Database tables

| Table                                                                                                                                             | Purpose                       |
| ------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------- |
| [`wp_pu_v4_credit_pools`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Infrastructure/Tables/Credit_Pools.php)           | License-level credit totals   |
| [`wp_pu_v4_site_credits`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Infrastructure/Tables/Site_Credits.php)           | Per-site allocation from pool |
| [`wp_pu_v4_site_credit_usage`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Infrastructure/Tables/Site_Credit_Usage.php) | Consumption tracking          |

### Credit types ([`Credit_Type_Enum`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/Enums/Credit_Type_Enum.php))

`DEFAULT`, `AI`, `AUTO_DNS`, `EMAIL`, `EMAIL_OVERAGE`, `IMPORTS`, `SITES`, `STORAGE`

### Credit periods ([`Credit_Period`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Domain/Enums/Credit_Period.php))

`DAY`, `WEEK`, `MONTH`, `YEAR`, `LIFETIME`

> A legacy `Credit_Period_Enum` also exists at `src/Licensing/Enums/Credit_Period_Enum.php` with additional values (`HOUR`, `INHERIT`). The v4 credit system uses the domain-level enum above.

### REST endpoints (base: `/api/stellarwp/v4`)

| Route                  | Method | Auth      | Purpose                  | Code                                                                                                                                                             |
| ---------------------- | ------ | --------- | ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/credits`             | GET    | Public    | Get site credit balance  | [`Credits_Controller::balance()`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Infrastructure/Http/Controllers/Credits_Controller.php)  |
| `/credits/pools`       | PUT    | Protected | Set/replace credit pool  | [`Credits_Controller::set_pool()`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Infrastructure/Http/Controllers/Credits_Controller.php) |
| `/credits/allocations` | POST   | Protected | Allocate credits to site | [`Credits_Controller::allocate()`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Infrastructure/Http/Controllers/Credits_Controller.php) |
| `/credits/allocations` | DELETE | Protected | Reclaim from site        | [`Credits_Controller::reclaim()`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Infrastructure/Http/Controllers/Credits_Controller.php)  |
| `/credits/usage`       | POST   | Protected | Record consumption       | [`Credits_Controller::usage()`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Infrastructure/Http/Controllers/Credits_Controller.php)    |

### Balance request (`GET /credits`)

| Param          | Required | Purpose                             |
| -------------- | -------- | ----------------------------------- |
| `key`          | Yes      | License key                         |
| `domain`       | Yes      | Site domain (identifies activation) |
| `product_slug` | No       | Filter by product                   |
| `credit_type`  | No       | Filter by credit type               |

### Balance response shape

```json
{
  "credits": [
    {
      "credit_type": "ai",
      "product_slug": "kadence",
      "allocated": 5000,
      "used": 1200,
      "remaining": 3800,
      "period": "month"
    }
  ]
}
```

Returned by [`Credit_Balance_Result::toResponse()`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Domain/Results/Credit_Balance_Result.php).

### Business logic

| Command/Query                                                                                                                                | Purpose                                                             |
| -------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------- |
| [`Set_Credit_Pool`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Application/Commands/Credit/Set_Credit_Pool.php)   | Idempotent pool create/replace                                      |
| [`Allocate_Credits`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Application/Commands/Credit/Allocate_Credits.php) | Distribute pool credits to site (row-level locks for concurrency)   |
| [`Reclaim_Credits`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Application/Commands/Credit/Reclaim_Credits.php)   | Remove site allocation                                              |
| [`Record_Usage`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Application/Commands/Credit/Record_Usage.php)         | Track consumption (hard cap: rejects if `credits_used > remaining`) |
| [`Get_Credit_Balance`](https://github.com/stellarwp/licensing/blob/15eafb31/src/Licensing/V4/Application/Queries/Get_Credit_Balance.php)     | Compute balances, period calculations anchored to `purchase_date`   |

### Key behaviors

- **Credits are NOT in the validation response.** `POST /licenses/validate` returns license/subscription/activation only. Credits require a separate `GET /credits` call.
- **Plan-wide credits.** When `product_slug` is null in a pool, credits apply across all products on the license.
- **Period refresh.** Anchored to `subscription.purchase_date`, not calendar boundaries.
- **Concurrency safe.** `Allocate_Credits` uses `for_update` row locks.

### Docs in licensing repo

- [`docs/credits.md`](https://github.com/stellarwp/licensing/blob/15eafb31/docs/credits.md) — Credit system overview
- [`docs/rest/v3/signed.md`](https://github.com/stellarwp/licensing/blob/15eafb31/docs/rest/v3/signed.md) — V3 credit endpoints (lines 530-839)
- Postman: [`docs/stellar-licensing-api-v4.postman_collection.json`](https://github.com/stellarwp/licensing/blob/15eafb31/docs/stellar-licensing-api-v4.postman_collection.json)

---

## 10. Open items

- **Credit renewal/reset** — no renewal logic in the relay plugin or client code. Must happen via Commerce Portal → licensing service.
- **Migration from v3 to v4** — see [`migration-plan.md`](migration-plan.md).
