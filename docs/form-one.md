# Form One selection module

`/form-one/` is separate from examination results and future admission modules. It reuses the shared education layout and safe server-side fetch/cache pipeline, not an iframe or an external-link gateway.

The initial verified cycle is intake 2026, PSLE 2025, first selection. Cycles are stored in MariaDB, not hardcoded in the public page. Automatic URL mode suggests a path from **PSLE exam year** and the round label (`First selection` → `first-selection`), not intake year. A generated URL is not evidence that a cycle exists; live verification is still required. Use Manual mode for a different official path; existing nonstandard URLs retain Manual mode.

## Owner cycle management

Shared placements, traffic/error monitoring and module-specific page targets are enabled for landing, browsing, results and errors; see [shared integration](form-five.md#shared-system-integration). Candidate searches never put the student index in monitoring URLs.

Open **Result Pages → Simamia cycles** (`/admin/sources.php?section=form-one`). Cycle management shares the Result Pages sidebar entry. Other administrators can view cycles; only owners can change them. The old `/admin/form-one/` address redirects to this section.

1. Choose PSLE exam year, selection round and Draft status. The new cycle key is generated automatically; existing keys stay stable. Intake defaults to PSLE year + 1. The source URL updates automatically. Expand **Chaguzi za ziada** only to override intake year or select a Manual official URL; saved intake years are preserved when editing.
2. Save, then click **Hakiki source (live sample)**. Verification checks the intake heading and traverses region → council → school with actual selection records. This is a sample check, not verification of every school.
3. Change status to Published and optionally select **Weka default**. Only verified, published cycles appear in both public search forms.
4. Changing source or cycle metadata invalidates verification. An edited Published cycle is automatically saved as Draft; verify again before publishing.
5. Archive a cycle to remove it from public choices without deleting its management history. If the configured default is unavailable, the newest published cycle is selected.

**Safisha cache ya cycle** removes tracked source snapshots for that cycle only; the next search fetches fresh data. Management changes are audited. A source outage is never permission to disable HTTPS checks or publish an unverified future cycle.

For another installation, a database administrator must run `scripts/setup_form_one_cycles.sql` on the `elimutaifa` database (DDL permissions required). The app account only needs its existing data privileges. `scripts/activate_form_one_seed.php` is a guarded, one-time CLI initializer: it publishes the initial Draft only after live verification succeeds. Subsequently manage cycles through the owner interface.

School browsing follows official region links, council links and primary school links. Candidate search validates the PSLE index, uses the existing district-code directory as a location hint, matches the official council label, resolves the school URL from the official directory, and extracts an exact index match. If aliases or codes cannot be matched, use school browsing; do not guess a school URL.

Public data includes index, sex, selected secondary school, type, council and optional joining PDF. Names are not provided by the verified sample. Results are noindex. School browsing uses GET with cycle/location/school codes only. Candidate IDs are submitted via POST and redirected to a session-bound opaque search token; the last five searches expire after 30 minutes. This prevents Back/refresh resubmission while keeping candidate IDs out of URLs. Raw upstream HTML is never inserted into the UI.

The existing fetcher supplies five-minute shared URL caching, per-client/global rate limiting, verified HTTPS, 3-second connect/10-second request timeouts, a 2 MB response ceiling and source error monitoring. No bulk student import is performed. A cold search traverses up to four pages; warm searches reuse cached pages. Source timestamps are displayed in EAT. A source error and an index missing from one round are explicitly different outcomes.

Run `C:\xampp\php\php.exe tests/form_one.php` for isolated fixtures, or add `--live` for the official hierarchy smoke test. Run `tests/form_one_cycles.php` for cycle validation, publication/default/archive and verification fixtures. Live verification requires network access and a valid PHP CA bundle. Do not disable TLS verification. Source robots currently resolves to an application shell rather than a usable robots policy; terms/republication permission must be reviewed before broad public deployment.
