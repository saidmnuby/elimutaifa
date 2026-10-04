# Sponsors & Ads

Direct sponsors continue in Admin → Sponsors & Ads. Network ads & monitoring manages responsive AdSense display units and Adsterra Native Banner units. Only the pinned main owner can configure network scripts; other admins can read local delivery status.

The templates already contain top/bottom slot hooks and one shared renderer. Configure each ad unit once in admin: provider IDs/tag URL, targets, priority, schedule and status. No repeated template editing is necessary. Network units share the existing limits (one top placement, two bottom placements), with priority deciding which units render. Archived pauses future page loads; it cannot withdraw an ad already loaded in an open browser.

AdSense requires data-ad-client (`ca-pub-…`) and data-ad-slot from the approved display code. Adsterra support here is specifically Native Banner: the 32-character container key and matching HTTPS /key/invoke.js URL from its dashboard. Other formats and arbitrary pasted HTML are not supported. URLs are owner-provided; this application does not independently verify their ownership with Adsterra.

Publishing requires the main owner's declaration that the site/unit is approved and the provider's required privacy/consent setup is complete. This declaration does not install a consent-management platform or verify approval. Configure provider-required consent tools on the production domain before publishing. Localhost does not request live ads. Provider account details are not prefilled or invented.

Local mounted / script ready / failed signals describe delivery health, not visibility, fills, billable impressions, ad clicks or money. They are anonymous estimates, deduplicated per event/browser-network identity each hour. Do Not Track and bots are excluded from local signals. Delivery history is retained for up to 60 days. Failed or blocked scripts can produce no signal if the browser closes; zero script-ready count does not prove a provider outage.

The Friday system email also includes the configured network ad-unit count and recent local script-failure signals. It explicitly reports provider revenue/impressions as not connected.

Actual impressions, clicks and earnings stay in the provider's reporting dashboard. AdSense Management API and Adsterra Publisher API can supply provider reports in a future authorized account integration; they are not connected by this update. No API token, account OAuth permission or revenue data has been requested or stored.

Checks: `php tests/ad_networks.php`, `php tests/placements.php`, `php tests/grouped_routes.php`. Provider live delivery needs real approved IDs on the production domain; tests do not load ads.

References: [Google publisher ID / display code](https://support.google.com/adsense/answer/105516), [Google ad-unit reports](https://support.google.com/adsense/answer/9187347), [Adsterra publisher statistics](https://adsterra.com/blog/adsterra-statistics-for-publishers-and-webmasters/), [Adsterra Publisher API](https://adsterra.com/blog/how-to-use-adsterra-publishers-api/).
