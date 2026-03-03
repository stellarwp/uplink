# Unified License Key: System Design

## Summary

StellarWP is moving from per-product license keys to a single unified license key per customer. A customer has one unified key (`LWSW-` prefix). That key is the site's identity to the licensing system. Licensing tells the site what products and tiers are associated with that key, and the site acts on that information.

This document describes how the unified key relates to three systems: **Licensing** (the v4 API), **Portal** (customer-facing management), and **Plugins** (WordPress, via the Uplink library).

## The Unified Key

A customer has one license key. That key is used across many sites, associated by domain. The key owns subscriptions, each of which has a product, a tier, and a site limit.

A site has one unified key. It asks Licensing: "what does this key have access to?" and the answer is definitive.

### One Key Per Site

Earlier exploration considered supporting multiple unified keys on a single site (e.g., a customer's key for Kadence and an agency's key for GiveWP). This has been ruled out.

If multi-key scenarios arise from business needs, agency sharing access with a customer, customer service creating a special license; that is a **Portal concern**. Portal manages key associations, aliases, and access grants. From the site's perspective, there is one key.

### How the Key Enters a Site

The unified key reaches a WordPress site in one of two ways:

- **Embedded** a product purchased from the StellarWP store ships with a license file containing the key
- **User-entered** the user types the key into the admin UI

Products downloaded from WordPress.org don't have an embedded key. The site must already have one from another product, or the user must enter one.

## The Three Systems

### Licensing

Licensing is the authority on what a key is entitled to. When a site presents its key, Licensing returns which products are active, what tiers they're on, and whether the site is within its seat limits.

**Seat consumption** happens when a single product is validated against a key on a domain for the first time. If the product is already active on that domain, no additional seat is consumed. If there are no available seats, the product cannot activate, the customer needs to free seats or upgrade.

**Seat release** is a protected operation. It cannot be done programmatically from a site with just a license key, that would open the door to abuse. Seats are only freed through Portal, where the user has been authenticated.

**Validation** serves two purposes:
- When a product first connects to a key (activation, key entry), validation checks entitlements and may consume a seat
- Periodically, the site checks the status of all its products against the key to confirm everything is still valid, this is read-only and does not consume seats

### Portal

Portal is the customer-facing management interface. It is the only system that can perform administrative operations on a license.

**Portal owns:**
- Seat management: customers free seats by removing site activations
- Key management: regeneration (for stolen keys), aliases
- Multi-key scenarios: if business needs require sharing products across keys, Portal handles the associations on the Licensing side
- Subscription management: purchasing, upgrading, downgrading tiers

**Portal does not push state to sites.** Sites pull their own state from Licensing on a schedule. If a customer frees a seat in Portal, the site picks it up on its next validation check.

**Portal is the upsell target.** When a site is out of seats, the plugin UI links to Portal where the customer can buy more or free existing activations.

### Plugins (WordPress Site)

The WordPress site is a consumer of Licensing data. It stores the unified key, presents it to Licensing, and acts on the response.

**The site stores one key.** All installed StellarWP products share it. Products that shipped with an embedded key contribute it on first activation. Once the site has a key, it's the canonical copy, individual products don't maintain their own.

**The site asks Licensing what the key covers.** The response determines which products are licensed, what tier each is on, and whether seats are available. This is the source of truth for all product entitlement decisions on the site.

**The site cannot release seats.** If a customer changes their key, the new key begins validating immediately. The old key's seats remain consumed until the customer frees them through Portal. This matches how the legacy system works.

**If there's no key, the site is unlicensed.** Products function in whatever their free/unlicensed mode is. No calls are made to Licensing.

## Key Change Scenarios

### Customer enters a key for the first time

Site stores the key. Each installed product validates against it. Products that are on the key and have available seats activate. Products that aren't covered show appropriate status.

### Customer changes to a different key

Site replaces the stored key. Products re-validate against the new key. The old key's seats stay consumed, the customer frees them through Portal.

### Product activates with an embedded key

The product contributes its embedded key. If the site already has a key, the existing key takes precedence. If the site has no key, the embedded key becomes the site's key and the product validates against it.

### Product downloaded from WordPress.org (no key)

The product has no key to contribute. If the site already has a key, the product validates against it. If the site has no key, the user is prompted to enter one.

### Customer runs out of seats

Validation returns that no seats are available. The site shows the customer their options: free existing activations through Portal, or upgrade their plan.

## Boundaries

| Concern | Owner | Why |
|---------|-------|-----|
| Storing the key on a site | Plugin (Uplink) | The site is the consumer |
| Determining what a key covers | Licensing | Licensing is the authority |
| Consuming a seat | Licensing | Side effect of first validation per domain |
| Releasing a seat | Portal | Requires authenticated user, abuse prevention |
| Managing key associations | Portal | Administrative operation |
| Checking entitlement status | Licensing | Read-only periodic check |
| Feature/addon catalog | Commerce Portal | Maps license entitlements to available features |
| Product updates/releases | TBD | Separate concern, not covered here |

