# ElimuTaifa Results Portal

ElimuTaifa is a PHP and static HTML portal for searching Tanzanian national examination results. It provides dedicated search pages for multiple NECTA examination levels and presents a simplified candidate-result view after retrieving the relevant published school result page.

## Available services

| Service | Examination level | Candidate format |
| --- | --- | --- |
| ACSEE | Form Six | `S1234/0001` or `P1234/0001` |
| CSEE | Form Four | `S1234/0001` or `P1234/0001` |
| FTNA | Form Two | `S1234/0001` or `P1234/0001` |
| PSLE | Standard Seven | `PS1234567-0001` |
| SFNA | Standard Four | `PS1234567-0001` |

The portal also includes Home, About, Contact, Privacy, and community-feedback pages.

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
   - `libxml`

4. Open the application:

   ```text
   http://localhost/get-results-faster/
   ```

The host running PHP must be able to make HTTPS requests to the NECTA and TETEA result sources.

## Project structure

```text
.
|-- index.html                 # Main portal
|-- assets/                    # Shared styles, client JavaScript, cookie notice
|-- includes/
|   `-- result_request.php     # Shared cURL, cache, and rate-limit helper
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
- The community-feedback page currently provides a user interface only; it does not persist or send submissions to a backend service.
- The application does not use a database, package manager, or build step.

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

## License

No license has been defined for this repository.
