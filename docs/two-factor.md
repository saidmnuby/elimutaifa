# Admin two-factor authentication

Authenticator-compatible TOTP uses a six-digit code rotating every 30 seconds, with a one-step clock tolerance. All administrators must enrol before accessing other admin modules; password-only sessions are limited to setup. Only an unenrolled owner may use the expiring local-development exception below. No Google API, billing, or external QR service is used.

## Deployment

- Run `composer install --no-dev` with PHP 8.2+, OpenSSL and DOM. Dependencies are pinned in `composer.lock`. The XAMPP CLI can temporarily enable ZIP with `php -d extension=zip`; no global PHP configuration change is required.
- Provision `admin_two_factor` and `admin_recovery_codes` using `scripts/setup_two_factor.php` with a setup account permitted to create tables. Do not grant DDL privileges to the runtime account.
- Provision a random 32-byte encryption key, Base64 encoded, in `ELIMUTAIFA_2FA_KEY` or the protected Git-ignored `storage/two-factor-key.php`. The local key has already been created. Never overwrite it once secrets are enrolled. Protect it with filesystem permissions; retain a separately protected disaster-recovery copy. Database backups alone cannot recover encrypted 2FA secrets.
- Require HTTPS in production and keep OS/server and phone clocks automatically synchronised. Local XAMPP HTTP is for development only.
- `vendor/` must deny web access. `storage/` already denies access to secrets and backups.

## Owner enrolment

### Temporary local development exception

The Git-ignored `storage/development.php` can explicitly allow an owner who has not enrolled to postpone setup. It requires `environment=local`, `allow_owner_without_2fa=true` and an integer Unix `expires_at`. The current local setting expires after seven days. It only accepts direct loopback client/server addresses and a localhost/loopback Host; forwarded/proxy requests and remote LAN requests are denied. The admin displays a development warning. Password login is still required; enrolled accounts must still pass 2FA.

Remove this local file before deployment and set `ELIMUTAIFA_APP_ENV=production` on production, which overrides the file and disables the exception. Never expose the development server through a tunnel or a local reverse proxy that strips forwarding headers. Run `php tests/development.php` to verify the guard.

Login normally. The owner is redirected to the 2FA setup screen. Enter the current password, scan the QR using Authenticator (or enter the setup key manually), then enter the current password and six-digit code to enable. Setup expires after ten minutes.

Ten recovery codes are displayed once after activation. Store them safely outside the phone; every code is single-use. Refreshing or leaving the screen discards their display. Generate replacement codes using the password plus a valid TOTP/recovery code; this invalidates all old recovery codes. Owners cannot disable 2FA through the web interface.

Subsequent logins require password then TOTP or recovery code. Password-only sessions do not gain access to MFA-enabled accounts. A pending challenge expires after five minutes. Five failed MFA/setup attempts block further account MFA attempts for fifteen minutes. Audit logs do not contain submitted codes, QR data or secrets.

## Lost phone

Use a recovery code to log in. If the phone is lost or compromised, remove its enrolment and create a new secret; replacing recovery codes alone does not revoke the phone's secret. A trusted developer can run `php scripts/reset_two_factor.php username --confirm` after verifying the admin's identity. This audited CLI action removes the enrolment and recovery codes; the owner must enrol again before using modules. There is no public recovery/reset endpoint.

## Verification

### Abuse limits

2FA failures are limited per account plus direct client IP (stored as a keyed hash, not a raw IP). Forwarded headers are not trusted. Five failures cause a five-minute source-specific lock that survives starting a new session. Three invalid codes close the pending login, which is also bound to its source and expires after five minutes. Successful password authentication does not clear 2FA failure counters. Password-login throttling is separate and unchanged.

Ten failures across at least three sources within five minutes generate a grouped `two_factor_distributed_attempts` warning in Traffic & Errors, without globally locking the account. This is detection, not complete distributed brute-force prevention; shared NAT users can still share a source lock. Deploy edge/WAF rate limits for public production traffic and investigate warnings.

Run `php tests/two_factor.php`, `php tests/run.php` and `php scripts/check_mysql.php`. The 2FA integration test uses a temporary account in a rolled-back transaction. Also perform browser acceptance tests for QR scanning, wrong/expired/replayed codes, recovery code use, logout, owner setup enforcement and mobile layouts. Authenticator codes improve security but are not phishing-resistant passkeys.
