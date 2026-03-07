---
ticket: SCON-258
url: https://stellarwp.atlassian.net/browse/SCON-258
status: done
---

# Plugin update delivery via consolidated server

## Problem

WordPress plugins registered with Uplink currently have no way to receive updates through the unified catalog and licensing system. WordPress's native plugin update mechanism (`plugins_api`, `pre_set_site_transient_update_plugins`) needs to be fed by the consolidated server so that plugin features, plugins delivered as downloadable archives, get version checks and download URLs through the standard WordPress update flow.

The catalog knows which features exist and their versions (actually not yet, add what is necessary to the catalog fixure data); the licensing client knows what the site's key covers. The update system needs to join these with the installed plugin versions and surface updates through WordPress's existing UI.

## Proposed solution

Hook into WordPress's plugin update filters to collect installed Zip (plugin) features, check them against the license and catalog, and return update information in WordPress's expected format.
