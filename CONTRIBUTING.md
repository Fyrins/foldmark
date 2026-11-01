# Contributing

Issues and pull requests are welcome on https://github.com/Fyrins/foldmark .

## Setup

```bash
git clone git@github.com:Fyrins/foldmark.git
cd foldmark
composer install
```

There is no build step: the plugin is plain PHP.

## Tests

The suite runs against a real WordPress install and a MySQL database it wipes on
every run. Point it at both with environment variables:

| Variable | Default | Meaning |
|---|---|---|
| `WP_CORE_DIR` | none, required | Path to a WordPress install |
| `WP_TESTS_DB_HOST` | `127.0.0.1` | Database host, with its port |
| `WP_TESTS_DB_NAME` | `foldmark_tests` | Database name, wiped on every run |
| `WP_TESTS_DB_USER` | `db` | Database user |
| `WP_TESTS_DB_PASS` | `db` | Database password |

With a DDEV WordPress site, for example:

```bash
ddev mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS foldmark_tests; GRANT ALL ON foldmark_tests.* TO 'db'@'%';"
export WP_CORE_DIR=~/Sites/my-site
export WP_TESTS_DB_HOST=127.0.0.1:$(ddev describe -j | jq -r '.raw.dbinfo.published_port')
composer test
```

## Coding standards

```bash
composer lint       # PHPCS, WordPress ruleset, prefix and text domain checks
composer lint:fix   # PHPCBF
```

The plugin must also pass [Plugin Check](https://wordpress.org/plugins/plugin-check/)
with no error and no warning, on the packaged plugin rather than the repository.
CI runs all of this on every push and pull request.

## Translations

```bash
wp i18n make-pot . languages/foldmark.pot --slug=foldmark --domain=foldmark \
  --exclude=vendor,node_modules,tests,.github,docs
wp i18n update-po languages/foldmark.pot
msgfmt --check -o languages/foldmark-fr_FR.mo languages/foldmark-fr_FR.po
```

New languages are welcome on translate.wordpress.org once the plugin is listed.
