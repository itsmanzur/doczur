# Nirdeshio test suites

There are two suites with different jobs.

| Suite | Config | Needs a database | Speed |
| --- | --- | --- | --- |
| Unit | `phpunit.xml.dist` | No | Instant |
| Integration | `phpunit-integration.xml.dist` | Yes | ~2s |

The unit suite stubs WordPress functions (`tests/bootstrap.php`) so it runs
anywhere. The integration suite boots real WordPress via
[`wp-phpunit`](https://github.com/wp-phpunit/wp-phpunit), so post types, REST
routes and the FULLTEXT search index behave exactly as they do in production.

## ⚠️ Before running the integration suite

**The WordPress test suite drops every table in its database on each run.**
It must point at a throwaway database — never at the database behind a real
site. `tests/wp-tests-config.php` refuses to start if `DB_NAME` is a commonly
used live name such as `local`.

## One-time setup

Create the test database:

```bash
mysql -h 127.0.0.1 -P 10114 -u root -proot -e "CREATE DATABASE IF NOT EXISTS doczur_tests DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Defaults assume a Local (Flywheel) site. Override any of these with
environment variables if your setup differs:

| Variable | Default |
| --- | --- |
| `WP_TESTS_DB_NAME` | `doczur_tests` |
| `WP_TESTS_DB_USER` | `root` |
| `WP_TESTS_DB_PASSWORD` | `root` |
| `WP_TESTS_DB_HOST` | `127.0.0.1:10114` |
| `WP_TESTS_TABLE_PREFIX` | `wptests_` |

WordPress core is found automatically, four directories above `tests/`.

## Running

```bash
composer test
```

```bash
composer test:integration
```

### Local (Flywheel) on Windows

Local's bundled PHP CLI starts without a `php.ini`, so `mysqli` and `mbstring`
are missing and the integration bootstrap cannot connect. Point `PHPRC` at your
site's PHP config first — this also covers the sub-process WordPress spawns to
install the test database, which is why passing `php -c` alone is not enough:

```bash
export PHPRC="$APPDATA/Local/run/<SITE_ID>/conf/php"
```

Find `<SITE_ID>` by looking for the folder under `%APPDATA%/Local/run` that
contains your site's `conf/nginx/site.conf`.

## What is covered

- `BootstrapTest` — post types, taxonomies, custom tables and REST routes load.
- `DraftLeakTest` — the roadmap's mandatory check: draft, pending and private
  articles never reach the search index or a public response, even when the
  index is stale.
- `SampleDataLifecycleTest` — the demo generator creates and removes only its
  own content, and never deletes user-authored articles or in-use sections.

### A note on FULLTEXT and transactions

Each test runs inside a transaction that is rolled back afterwards. InnoDB does
not expose FULLTEXT index updates to the transaction that made them, so
`MATCH … AGAINST` finds nothing for rows created inside a test. Draft-leak
coverage therefore drives `Search_Service::hydrate_public_results()` directly —
which is the stronger test anyway, since it can simulate a stale index that the
end-to-end query never would.
