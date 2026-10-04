# Examination monitoring

Open **Admin → Result Pages → Open examination monitoring**. Administrators can view; owners can check and change cycles for ACSEE, CSEE, FTNA, PSLE and SFNA.

## Simple workflow

1. Choose **Examination** and **Year**, then **Open cycle**.
2. Choose **NECTA** or **Maktaba / TETEA**, then **Check source**. The system finds a directory and sample school automatically, including a district for primary results. You do not need to enter a school code or `{school}` template for routine checks.
3. When the dashboard says **Ready to publish**, click **Publish cycle**. Publication requires a successful check within the previous 24 hours. An already published cycle stays published after a successful check of its current mapping.

For year rollover or source relocation, open the affected year, select its new provider and check it. A successfully changed mapping becomes Draft until published. Candidate search and school browsing use the same published mapping. Providers never change automatically.

A failed check shows the reason and flags **Needs attention**. It retains the previous mapping and public status; it does not automatically suspend an existing Published cycle. Investigate **Traffic & Errors**, correct the provider or optional advanced settings, and check again. Use **Pause cycle** explicitly when the year should be removed from public search.

## Dashboard and limits

- **Healthy**: published mapping with a recent successful sample check.
- **Ready to publish**: verified mapping awaiting publication.
- **Needs attention**: failed checks or repeated source errors.
- **Not checked / Check due**: unverified mapping or a check older than 24 hours.
- **Paused**: suspended or archived cycle.

The dashboard records check time, provider, last live HTTP response and source failures. Old errors remain in Traffic & Errors; a newer successful check clears their contribution to the cycle warning. HTTP success alone does not establish result correctness.

Checks run when you press **Check source**. No background scheduler is installed. Each check bypasses result cache, makes at most eight upstream requests, follows only approved examination/year links and verifies a directory plus a readable candidate result. It checks a sample, not every school. A substantially changed result layout can still require developer changes.

## Advanced settings and recovery

**Advanced settings & history** is optional. Use it for unusual source paths. Base URLs must be HTTPS and within the approved NECTA or TETEA examination/year directory. School paths must contain exactly one `{school}`; primary directory paths require exactly one `{district}`. The automatic check normally discovers these paths, filename case and primary school format.

Changing source settings clears verification and returns the mapping to Draft. Previous configurations are retained transactionally; restore creates a Draft and requires a fresh check before publication. The ten most recent configurations are displayed. Checks and changes are audited, and stale concurrent edits are rejected. Changing the mapping changes its cache namespace without deleting other years' caches.

## Installation and checks

Run `C:\xampp\php\php.exe scripts\setup_exam_cycles.php` once per installation before deploying managed routes. It uses the existing `app_settings` table and preserves existing records. Imported historical mappings are marked inherited, without fabricated verification dates. Importing a year is not evidence that its results exist; review each required year.

Keep each examination folder's `.htaccess`; PHP renders the public landing pages and Apache redirects direct template requests.

Validation commands:

- `php tests/exam_monitoring.php`: automatic secondary/primary discovery, request budget, provider boundaries, health and failed-check availability.
- `php tests/exam_cycles.php`: publication, safe URLs, verification, cache revisions, history and concurrency.
- `php tests/secondary_directory.php` and `php tests/secondary_archive_coverage.php`: isolated historical fixtures and school parsing.
- `php tests/grouped_routes.php`: public page references.
- `php scripts/healthcheck.php`: general installation checks, including published cycles.
