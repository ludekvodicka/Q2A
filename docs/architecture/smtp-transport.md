# SMTP transport

**Decision date: 2026-09-09.** Use PHPMailer 6.12.0 and PHP's default certificate and hostname verification. The earlier PHP 5.6 installation's certificate bypass is removed from `qa-include/app/emails.php`.

## Dependency

The existing Q2A integration loads five classes directly from `qa-include/vendor/PHPMailer6`. They now contain the unmodified upstream 6.12.0 files, with the upstream LGPL license included. Keeping the existing loader avoids adding a separate package bootstrap for this update.

- [Upstream release](https://github.com/PHPMailer/PHPMailer/releases/tag/v6.12.0)
- Source commit: `d1ac35d784bf9f5e61b424901d5a014967f15b12`
- Files: `Exception.php`, `OAuth.php`, `OAuthTokenProvider.php`, `PHPMailer.php`, `SMTP.php`, `LICENSE`.
- Active version constant: `PHPMailer\PHPMailer\PHPMailer::VERSION`.

The approved migration selected the 6.x series. The upstream 1.8.8 bundle supplied 6.6.3. The unused older `vendor/PHPMailer` directory remains upstream baggage and is not loaded by the application mail path.

## Resulting behavior

SMTP host, port, authentication and transport mode still come from Q2A options. For STARTTLS, PHP validates the certificate chain and hostname against the current container CA store. The code no longer supplies `verify_peer=false`, `verify_peer_name=false` or `allow_self_signed=true`. See the [upstream TLS example](https://github.com/PHPMailer/PHPMailer/blob/v6.12.0/examples/ssl_options.phps).

An invalid or untrusted certificate causes connection failure. Fix the certificate or CA configuration if this happens; do not restore the blanket bypass.

## Verification

On PHP 8.3, the deployed SMTP provider accepted a connection and authentication with normal verification. A second connection with an intentionally incorrect peer hostname was rejected. A notification sent through `qa_send_notification()` with the updated mailer was accepted and its delivery confirmed through the provider's event API. Deployment-specific evidence and credentials remain outside this public repository.

Application mail remains synchronous. This change does not introduce retries or a mail queue. A package vulnerability scan does not cover every issue in manually bundled PHP libraries.
