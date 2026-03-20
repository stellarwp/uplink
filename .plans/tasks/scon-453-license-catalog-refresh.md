---
ticket: SCON-453
url: https://stellarwp.atlassian.net/browse/SCON-453
status: done
---

# Schedule license & catalog refresh every 12 hours

## Problem

License and catalog data are only fetched on-demand -- when a REST endpoint is called or a feature is resolved. This means a site that activates a higher tier on the Commerce Portal, receives a license renewal, or has its catalog updated may go hours or days without reflecting that change. The data persists indefinitely in WordPress options with no background refresh, so validity and catalog integrity drift over time.

## Proposed solution

Register a single WordPress cron schedule that runs every 12 hours per subsite. On each run it refreshes both the catalog and the license products for that site's domain. These are kept as independent subsystem calls (catalog first, then licensing) unified under one cron hook.

The job should skip the license refresh gracefully if no `LWSW-` key is stored for the site -- no key means nothing to validate, so the API call is unnecessary. Catalog should still refresh regardless, since it does not depend on a key.

On multisite the cron runs per-subsite, using each subsite's own domain for the license products call. This naturally handles both network-keyed and per-site-keyed installs without special-casing, since the license key resolution already accounts for network vs. site option precedence.

The schedule registers on plugin init and unregisters cleanly on deactivation. No retry logic is needed for this first pass -- failed refreshes follow the existing 60-second error throttle, and the next 12-hour run will retry automatically.
