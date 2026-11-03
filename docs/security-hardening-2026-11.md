# Security hardening: deployment notes (2026-11)

These changes harden session handling, Twilio webhook handling and one-time login keys in the legacy codebase.

## What changed

| Change | Files |
|---|---|
| Server name and encryption key removed from tracked config; loaded from git-ignored `config-local.php` | `config/config.php`, `config/config-local-sample.php` |
| Empty `encryption_key` no longer allowed: one is derived from the install `salt` | `libraries/MY_Controller.php` |
| `cookie_domain`/`cookie_path` restored to derive from `base_url` | `config/config.php` |
| Every `twiml/*` endpoint, the audiofiles recording callbacks and the Connect deauthorize callback now require a valid `X-Twilio-Signature` | `helpers/twilio_helper.php` (`require_twilio_signature()`), `controllers/twiml.php`, `controllers/audiofiles.php`, `controllers/auth/connect.php` |
| Signature URL rebuilt with the raw original query string (previously dropped with mod_rewrite on, so URLs like `twiml/dial?to=…` could never validate) | `libraries/OpenVBX.php` |
| Fatal error (undefined `$ci`) on rejected `start_voice` and audiofiles callbacks removed | `controllers/twiml.php`, `controllers/audiofiles.php` |
| Exception messages no longer spoken to callers | `controllers/twiml.php` |
| One-time REST login keys use 128 bits of CSPRNG output instead of `md5(rand(10000,99999).date())` | `models/vbx_rest_access.php` |
| Connect deauthorize returned 400 even on success; now 204 | `controllers/auth/connect.php` |

Logged-in admins (with a valid session signature) can still open TwiML URLs directly, so **View TwiML** in the flow editor keeps working.

## Deploying to a live server

1. **Create `OpenVBX/config/config-local.php`** from the sample:
   ```php
   $config['server_name'] = 'example.com';
   $config['encryption_key'] = '<new 64-hex-char value>';
   $config['twilio_signature_mode'] = 'log';   // first rollout only
   ```
   Generate the key on the server:
   ```bash
   php -r "echo bin2hex(openssl_random_pseudo_bytes(32)), PHP_EOL;"
   ```
   **Do not reuse** any key that has ever been committed to a repository. Changing the key logs everyone out once.
2. Deploy the code. With `log` mode, nothing is rejected yet.
3. Place a test call through a flow with a menu, dial and voicemail. Send a test SMS, use click-to-call, and record a greeting by phone. Then check `system/logs/log-*.php` for `Twilio signature validation failed`.
   - **No failures** → remove the `twilio_signature_mode` line (defaults to `enforce`).
   - **Failures on legitimate traffic** usually mean the URL the server rebuilds differs from what Twilio called: http vs https behind a proxy without `X-Forwarded-Proto`, a different hostname, or a port. Fix `server_name` or the proxy headers, then retest.

## Tests

The legacy CIUnit applet tests in `OpenVBX/tests` call the TwiML controller without a signature. They already can't run on modern PHPUnit, but if they're revived they'll need a signed-request fixture or `twilio_signature_mode = 'log'`.

## Reporting security issues

Please don't open public issues for security problems. Report them privately through this repository's **Security** tab (*Report a vulnerability*).
