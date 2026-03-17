# SCON-310: Kadence AI Credits Discovery

> **Status:** Investigation complete — remaining questions need team input
> **Ticket:** <https://stellarwp.atlassian.net/browse/SCON-310>
> **Goal:** Determine what Uplink v3 needs to provide for Kadence AI credits.

## Projects and repositories

| #  | System                    | Repo                                                                                                                            | Role                                                                       |
| -- | ------------------------- | ------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| 1  | **Kadence Blocks** (free) | [stellarwp/kadence-blocks](https://github.com/stellarwp/kadence-blocks)                                                         | All credit UI, balance fetching, AI generation calls, event tracking       |
| 2  | **Kadence Blocks Pro**    | stellarwp/kadence-blocks-pro                                                                                                    | No credit involvement (confirmed zero matches)                             |
| 3  | **Prophecy SaaS**         | [stellarwp/prophecy-wordpress](https://github.com/stellarwp/prophecy-wordpress)                                                 | AI content generation; fires `prophecy/rest/authorized` filter             |
| 4  | **Prophecy (composer)**   | [stellarwp/composer-prophecy-wordpress](https://github.com/stellarwp/composer-prophecy-wordpress)                               | Composer package for Prophecy                                              |
| 5  | **Prophecy deployment**   | [stellarwp/prophecy-kadence](https://github.com/stellarwp/prophecy-kadence)                                                     | Site config for `gen.startertemplatecloud.com`                             |
| 6  | **Ansible**               | [stellarwp/ansible](https://github.com/stellarwp/ansible)                                                                       | Deployment automation (`kadence-prophecy/` directory)                      |
| 7  | **Credit relay plugin**   | [stellarwp/kadence-prophecy-licensing-service-connect](https://github.com/stellarwp/kadence-prophecy-licensing-service-connect) | Pure relay on `startertemplatecloud.com` — proxies credit ops to licensing |
| 8  | **Licensing service**     | [stellarwp/licensing](https://github.com/stellarwp/licensing)                                                                   | Source of truth for credits (v3 current, v4 ready but unused for Kadence)  |
| 9  | **Uplink**                | [stellarwp/uplink](https://github.com/stellarwp/uplink)                                                                         | Provides license key + domain; no credit involvement today                 |

**Not a repo:** Commerce Portal (role in credit provisioning unknown/undocumented).

## Reading order

1. **[complete-credit-flow.md](complete-credit-flow.md)** — Full technical reference with exact code links. Start here.
2. **[uplink-responsibilities.md](uplink-responsibilities.md)** — What Uplink must/must not/can do.
3. **[migration-plan.md](migration-plan.md)** — V3 → V4 credit migration options and open questions.

## Supporting docs

| File                                             | What it covers                                            |
| ------------------------------------------------ | --------------------------------------------------------- |
| [system-map.html](system-map.html)               | Full block diagram — all codebases, services, connections |
| [grand-plan.md](grand-plan.md)                   | Verified facts summary and remaining team questions       |
| [black-boxes.md](black-boxes.md)                 | Inventory of every system/codebase involved               |
| [evidence-log.md](evidence-log.md)               | Timestamped session summaries (details in the docs above) |
