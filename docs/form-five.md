# Form Five / middle-college selection

Separate public module: `/form-five/`. Uses the shared compact Form One CSS/layout and safe fetch/cache/rate-limit pipeline. Browsing traverses official links: cycle → region → council → previous secondary school/centre → selections. S/P centre codes are supported. Browsing uses GET with cycle/location/school codes only, so browser Back does not resubmit forms. Selection pages remain noindex; raw source HTML is never rendered.

Cycles come from the `form_five_cycles` database table. Only verified Published cycles appear in public browsing; the owner-selected Form Five default is independent of Form One. The initial cycle is 2026 First selection, CSEE 2025, on `selform.tamisemi.go.tz`. Independent index search still requires a cycle-specific school-URL directory job; do not scan all councils during a student request. No university admission integration is included.

## Cycle management

Open **Admin → Result Pages → Form Five / Vyuo vya kati → Simamia cycles**. Owners may write; other admins may view. Choose CSEE year and round; intake defaults to exam year + 1. Automatic source URLs use **intake**, not exam year. Advanced options allow intake override and a different official Manual URL. Save Draft → verify a live hierarchy sample → Published/default. Changes to source/metadata invalidate verification and demote an edited Published cycle to Draft. Archive removes a cycle from public choices; cache clearing removes tracked snapshots for that cycle only. Changes are audited.

Database setup: after the Form One cycles schema, run `scripts/setup_form_five_cycles.sql` with a DDL-capable database administrator. `scripts/activate_form_five_seed.php` verifies and activates the untouched initial Draft once; subsequent management belongs in the owner interface. Run `tests/form_five_cycles.php` for isolated cycle tests.

## Shared system integration

Both selection modules support owner/admin-managed top, bottom and popup placements with separate landing, browsing, result and error targets. Existing all/levels/schools/results/errors groups include the appropriate selection pages. Shared monitoring records traffic without query strings/candidate IDs and reports processing failures with module labels. Only landing pages appear in the sitemap; browsing/results/errors send noindex headers. Home and information-page navigation link to both modules. Healthcheck validates both cycle tables. Test shared hooks with `tests/selection_activity.php` and `tests/placements.php`.

The parser extracts exact school/year candidate rows, destination, course/combination, type, location and optional source notes. Only official TAMISEMI PDF links are shown as joining instructions. Generic college admission links are not mislabeled as PDFs. Fees/notes are source snapshots, not guarantees of current institution charges.

Tests: `C:\xampp\php\php.exe tests/form_five.php`; add `--live` for a four-page official-source smoke test. Terms/republication permissions still need review before public deployment. No bulk student import occurs.
