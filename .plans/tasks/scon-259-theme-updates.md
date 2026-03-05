---
ticket: SCON-259
url: https://stellarwp.atlassian.net/browse/SCON-259
status: todo
---

# Theme update delivery via consolidated server

## Problem

The plugin update work (SCON-258) only covers `plugins_api` and `pre_set_site_transient_update_plugins`. WordPress has a completely separate pair of filters for themes: `themes_api` and `pre_set_site_transient_update_themes`, with a different response shape. Theme-type features in the catalog — if/when they exist — won't receive updates through the WordPress admin until the theme update path is wired up.

The catalog schema already accommodates a `theme` (actually not yet, add what is necessary to the catalog fixure data), but nothing on the update side handles it. WordPress expects a different `stdClass` structure for theme updates.

## Proposed solution

Mirror the plugin update handler pattern for themes. Hook into `themes_api` and `pre_set_site_transient_update_themes`, collect installed theme features, check them against the consolidated server, and translate responses into WordPress's theme update format.
