# PHP 8.3 runtime for the Q2A fork

**Decision date: 2026-09-09.** The owner selected `ludekvodicka/Q2A` as the repository for an independently maintained Question2Answer fork. Security fixes to the fork are now our responsibility. Retaining an unmodified upstream core was considered and rejected by the owner.

## Source provenance

The initial source comes from the official release asset, not the master branch or an older local checkout:

- Release: <https://github.com/q2a/question2answer/releases/tag/v1.8.8>
- Asset: `question2answer-1.8.8.zip`
- SHA-256: `1456ef9ebd4e8029e43e819be158a69f73c919ca38350f230fece1a6907ec4d2`
- Q2A version: `1.8.8`, database schema: `67`.

The release originally called PHPMailer **6.6.3** from `qa-include/vendor/PHPMailer6`. The fork now uses **6.12.0** with certificate and hostname verification enabled. The older `PHPMailer` directory is upstream baggage, not the active mail implementation. See [SMTP transport](smtp-transport.md) for provenance and delivery verification.

## Container contract

`Dockerfile` builds from the official PHP 8.3 Apache image on Debian 12, pinned by digest. Debian security updates are installed during the build. PHP extensions include MySQLi/mysqlnd, GD, mbstring and ZIP. Apache serves Q2A with rewrite rules and blocks direct access to configuration and dependency directories.

`docker/qa-config.php` reads configuration directly at runtime. It does not generate a credential-bearing PHP file or read an old installation's configuration:

| Environment variable | Meaning |
|---|---|
| `QUESTION2ANSWER_DB_HOST` | Database hostname, optionally `host:port` |
| `QUESTION2ANSWER_DB_USER` | Application database account |
| `QUESTION2ANSWER_DB_NAME` | Application database |
| `QUESTION2ANSWER_DB_PASSWORD_FILE` | Optional mounted password file |
| `QUESTION2ANSWER_DB_PASSWORD` | Password when no password file is supplied |

Missing configuration fails explicitly. Table prefix is `qa_`. Local blob storage is `/var/www/html/qa-uploads/`; cache storage is outside the document root at `/var/lib/q2a/cache/`. Reverse proxies should provide `X-Forwarded-Proto`; the container itself has no published port in the staging deployment.

PHP reports `E_ALL` to the container error log. Browser error display is disabled. The image has an HTTP health check. Site-specific themes and plugins remain in the downstream deployment repository, so this public fork contains no site credentials or production data.

### Apache configuration and upload isolation

As of 2026-09-10, the Q2A Apache configuration is installed as `conf-enabled/zz-q2a.conf`. Debian's `security.conf` previously loaded after `q2a.conf` and restored `ServerTokens OS` and `ServerSignature On`. Loading the Q2A configuration last makes `ServerTokens Prod` and `ServerSignature Off` effective. This reduces version disclosure; it does not fix application vulnerabilities.

PHP uploads use `/var/lib/q2a/upload-temp`, created with owner `www-data` and mode `0700`. This replaces the implicit shared `/tmp` fallback and keeps temporary uploads outside the web root. Persistent uploads remain in their existing locations.

`allow_url_include` is explicitly disabled. `allow_url_fopen` remains enabled because Q2A's `qa_retrieve_url()` and plugin HTTP integrations use URL-aware file functions. Turning off URL wrappers is not equivalent to disabling remote code inclusion and would change those HTTP paths. Remote URL validation remains the responsibility of each caller.

See the PHP documentation for [URL wrapper settings](https://www.php.net/manual/en/filesystem.configuration.php) and [upload temporary directories](https://www.php.net/manual/en/ini.core.php#ini.upload-tmp-dir), and Apache's [ServerSignature](https://httpd.apache.org/docs/2.4/mod/core.html#serversignature) and [ServerTokens](https://httpd.apache.org/docs/2.4/mod/core.html#servertokens) documentation.

## Database compatibility

The official 1.8.8 source defines `QA_DB_VERSION_CURRENT = 67`. A source database already at version 67 needs validation with `qa_db_check_tables()`, not an invented version increment. PHP 8.3/mysqlnd connects to MySQL 9 using `caching_sha2_password`.

## Build

```powershell
docker --context moonhill build -t q2a-core:1.8.8-php83 Q:/ApplicationsAi/Q2A
```

The deployment repository then builds its site image from this locally built base. Both image builds must target the same Docker engine. Git publication and deployment are separate operations; local image construction does not publish the repository.

## Constraints

- Deploy in stages and review a copy of live data before cutover.
- A package scan does not establish that the Q2A application or manually bundled PHP libraries have no vulnerabilities.
- A production rollback must restore the previous application image and its original database together. It cannot safely point the old application at data modified by a later incompatible schema migration.
