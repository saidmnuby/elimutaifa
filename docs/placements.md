# Distributed banners and sponsor placements (MVP)

## Admin workflow

Open **Sponsors view** from the admin sidebar. Owner and authenticated admins can create/edit a placement; account/security audit events remain owner-only. Placement activity is an operational audit event.

1. Choose system announcement or sponsor. Sponsor name is mandatory for sponsored items.
2. Choose banner or card, then top, bottom or pop-up. Top/bottom stay in page flow; pop-ups are dismissible and never autoplay video.
3. For pop-ups choose a corner popup or a centered interstitial. Frequency can be every page load, once per session, or at most three times in that browser. Interstitial skip delay is configurable from 0 to 30 seconds.
4. Supply a short title/description and optional HTTPS image or validated JPG/PNG/WebP upload (max 5 MB).
5. Choose an external HTTPS destination or an existing internal article.
6. Select individual pages, groups or all integrated public pages. Groups cover search levels, results, school lists, information pages and errors.
7. Choose priority 0–100, optional start/end in EAT and Draft/Published/Archived. Published items obey their time window automatically. Blank start means immediately; blank end means no expiry. Archive removes a placement without deleting history.
8. Save and use Preview. Preview shows saved presentation, not actual page targeting or active schedule; check the real selected pages after publishing. Internal destinations display only while their article is publicly published and unexpired.

Twenty items per admin list page. The MVP displays at most one placement above, two below and one pop-up on a public page's main content, highest priority first, newest ID breaking ties. All means integrated public pages only, not admin/API/storage. Narrow targeting is additive, not an override of All. An empty or unavailable feed produces no visible placeholder and never prevents result search.

## Integration

`includes/placements.php` owns page/group registry, validation, targeting and public selection. `api/placements.php` returns only current public presentation fields. `assets/js/placements.js` renders text through DOM APIs rather than injected HTML; `assets/css/placements.css` provides responsive isolated styles. Static HTML and PHP result/school/error pages share the same renderer and optional slots. Sponsored links use `rel="sponsored noopener noreferrer"`; labels explicitly identify sponsorship.

Provision the separate `placement_items` table with `scripts/setup_placements.php` using a setup account allowed to create schema. Do not grant DDL to the runtime account. The local MariaDB table has already been provisioned. Its article foreign key becomes NULL on article deletion; an invalid internal destination is omitted publicly. Existing exam/content tables are not replaced. Add this table and uploaded images to ongoing backups.

Image uploads use the existing validated image storage helper. Replaced images are retained to avoid accidentally removing files used elsewhere; permanent deletion/media garbage collection is outside this MVP. Do not manually reuse another content item's uploaded image path: upload a separate file instead. Remote image hosts may receive visitor connection information; use local uploads where possible and avoid tracker images.

No arbitrary sponsor HTML/JavaScript, third-party ad-network tags, autoplay video, paid billing, impression/click analytics, rotation or pop-up placements are included. These require separate design and privacy review.

## Verification

Run `php tests/placements.php`, `php tests/audit_visibility.php`, `php tests/run.php` and syntax checks. Verify each public page has matching top/bottom page IDs and correct relative script paths. Before acceptance test desktop/mobile layouts, admin create/edit/archive, upload failures, sponsor labels, internal/external destinations, publication boundaries and ads-free operation when the API fails.
