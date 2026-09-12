# ElimuTaifa Education Information Platform

ElimuTaifa is an independent education-information and announcement platform for students, parents, teachers, schools and organisations. Its wider mission covers education announcements, examination results, selection information, admission updates and other useful opportunities. The currently implemented result service provides dedicated search pages for several Tanzanian national examination levels.

ElimuTaifa is not affiliated with, endorsed by, sponsored by, or operated by the Government of Tanzania, NECTA, NACTVET, TCU, TIE, or any other government institution. Information originating from an external organisation should be verified with that organisation.

## Available services

| Service | Examination level | Candidate format |
|  -- | --- | --- |
| ACSEE | Form Six | `S1234/0001` or `P1234/0001` |
| CSEE | Form Four | `S1234/0001` or `P1234/0001` |
| FTNA | Form Two | `S1234/0001` or `P1234/0001` |
| PSLE | Standard Seven | `PS1234567-0001` |
| SFNA | Standard Four | `PS1234567-0001` |

The portal also includes Home, About, Contact, Privacy, current-announcement and community-feedback interfaces. Selection, admission and broader announcement-management services are planned extensions.

## How result search works

1. A user selects an examination year and enters a candidate index number.
2. The examination page validates the submitted index number and constructs the school results URL.
3. PHP retrieves the source page from NECTA or the historical TETEA archive.
4. The result parser finds the candidate row, extracts subjects and grades, and displays them in the portal.
5. If a result cannot be retrieved or parsed, the user is redirected to the examination-specific error page.

Each result page also provides a direct link to the source page for confirmation.

## Technology

- HTML, CSS, and vanilla JavaScript for the interface
- PHP for validation, result retrieval, parsing, sessions, and error handling
- PHP cURL and `DOMDocument` for remote result pages
- Apache configuration through `.htaccess`
- Font Awesome and Google Fonts loaded from their CDNs

## Local setup (XAMPP)

1. Place this project in the XAMPP web root, for example:

   ```text
   C:\xampp\htdocs\get-results-faster
   ```

2. Start Apache from the XAMPP Control Panel.
3. Ensure PHP has the following extensions enabled:

   - `curl`
   - `dom`
   - `fileinfo`
   - `libxml`
   - `pdo`
   - `pdo_sqlite`

4. Open the application:

   ```text
   http://localhost/get-results-faster/
   ```

The host running PHP must be able to make HTTPS requests to the NECTA and TETEA result sources.

## Project structure

```text
.
|-- index.html                 # Education portal and live announcement interface
|-- assets/                    # Shared styles, client JavaScript, cookie notice
|-- includes/
|   |-- result_request.php     # Shared cURL, cache, and rate-limit helper
|   |-- admin_db.php           # SQLite connection and automatic schema migration
|   |-- admin_auth.php         # Admin authentication, CSRF and audit helpers
|   `-- content.php            # Publication and sitemap helpers
|-- admin/                     # Protected content-management interface
|-- announcements/             # Public announcement listing and article pages
|-- api/                       # Same-origin announcement and contribution endpoints
|-- storage/                   # Protected runtime SQLite database
|-- acsee/                     # Form Six search, result, and error pages
|-- csee/                      # Form Four search, result, and error pages
|-- ftna/                      # Form Two search, result, and error pages
|-- psle/                      # Standard Seven search, result, and error pages
|-- sfna/                      # Standard Four search, result, and error pages
|-- about/                     # About page
|-- contact/                   # Contact page
|-- contribution/              # Community feedback interface
`-- privacy/                   # Privacy policy and terms
```

## Admin MVP setup

The admin interface uses a protected SQLite database in `storage/`. Database tables are created automatically. Create the first administrator from the command line; there is intentionally no public web-based setup page.

In PowerShell:

```powershell
$env:ELIMUTAIFA_ADMIN_PASSWORD = 'use-a-long-unique-password'
C:\xampp\php\php.exe scripts\create_admin.php admin "Site Owner"
Remove-Item Env:\ELIMUTAIFA_ADMIN_PASSWORD
```

Then open:

```text
http://localhost/get-results-faster/admin/login.php
```

The MVP provides:

- login throttling, CSRF validation and time-limited admin sessions;
- draft, scheduled, published and archived content;
- featured announcements and one active pop-up announcement;
- internal announcement articles or validated external destinations;
- optional HTTPS images or privacy-delayed YouTube videos attached to internal articles;
- a contribution inbox with workflow statuses and internal notes;
- audit logs and a read-only result-source overview;
- owner-managed administrator accounts with access removal that preserves audit history;
- first-party traffic summaries and grouped system/upstream error monitoring;
- automatic sitemap regeneration when publication state changes.

The first CLI-created account becomes the `owner`. Additional administrators can be created from **Admin → Admins** or with the same CLI command using a different username. Owners can promote, deactivate, or remove other accounts. Removal revokes access and anonymises the account while retaining historical audit relationships.

Content can be archived by an administrator. Only an owner can permanently delete an archived item; the deletion remains recorded in the audit log. Internal articles can contain one optional uploaded JPG/PNG/WebP image, HTTPS image URL, or YouTube video. Uploaded images are limited to 5 MB and stored under `uploads/content/` with random names. A YouTube cover image is visible before playback, while the player uses the privacy-enhanced domain and is created only after the visitor clicks play. Only one player remains active on a page at a time, and the visitor can close it.

The inbox defaults to `New` and uses 20 messages per page. Audit logs use 50 records per page. Traffic monitoring honours the browser Do Not Track signal, stores aggregate counts rather than raw IP addresses, and groups repeated errors into one system event with an occurrence counter.

Use HTTPS in production. Back up `storage/elimutaifa.sqlite` while the application is in maintenance mode or by using SQLite's backup tooling.

For every examination level, the `results/` directory contains:

- `validationEngine.php` - validates submitted data and creates the source URL.
- `header.php` - fetches and parses the remote result page.
- `body.php` - renders the normalized result view.
- `index.php` - loads the result header and body.

## Request protection and caching

`includes/result_request.php` provides shared protection for remote requests:

- 30 requests per minute per client IP address
- 3-second connection timeout and 10-second overall timeout
- TLS certificate validation
- 5-minute temporary cache, stored in the system temporary directory

The project `.htaccess` file disables directory listing, blocks direct access to common sensitive file types, disables PHP error display, and configures basic caching/compression when supported by Apache.

## Development notes

- The result parsers depend on the HTML structure published by the external source. Re-test each examination level whenever NECTA or the archive changes its result-page layout.
- Result availability depends on the external source and the selected examination year.
- The community-feedback page stores validated submissions in the protected SQLite database for review in the admin inbox.
- The application has no package-manager or frontend build step.

## Testing checklist

Before deployment, verify:

- Apache and the required PHP extensions are enabled.
- Each examination page accepts its documented candidate format.
- Valid and invalid candidate searches show the correct result or error state.
- Remote NECTA/TETEA requests work from the deployment server.
- Mobile navigation, privacy links, and direct source-confirmation links work.
- PHP syntax can be checked with:

  ```text
  C:\xampp\php\php.exe -l path\to\file.php
  ```

- Run the lightweight automated checks:

  ```text
  C:\xampp\php\php.exe tests\run.php
  C:\xampp\php\php.exe scripts\healthcheck.php
  ```

## Operations

- Monitor Apache/PHP errors and upstream NECTA/TETEA response failures.
- Run `scripts/healthcheck.php` in deployment checks to confirm required PHP extensions.
- The shared result cache is stored in the server temporary directory and removes expired entries periodically. Ensure the PHP temporary directory is writable and monitored.
- Re-test result parsers against representative NECTA pages whenever NECTA changes an examination-result page format.

## License

No license has been defined for this repository.
