# CLAUDE.md

Guidance for working in this repository. The README covers installation and configuration for merchants; this
file covers what you need to know to change the plugin.

## What the plugin does

Sylius 2 plugin that tracks sales for the Danish affiliate network Partner Ads:

1. A visitor arrives through an affiliate link carrying `?paid=<partner id>`. `CapturePartnerIdSubscriber` stores
   the partner id - only if it is a positive integer (`PartnerIdParser`) - through `PartnerIdStorageInterface`,
   whose default implementation writes it into the visitor's client metadata (setono/client-bundle) with the
   `attribution_window` (40 days by default) as TTL. The plugin sets no cookie of its own.
2. When that visitor completes an order, `CreateConversionSubscriber` (on `sylius.order.post_complete`) stores a
   pending `Conversion` row referencing the order and the partner id. No HTTP happens here.
3. `setono:sylius-partner-ads:process-conversions`, run via cron, picks up pending conversions whose order has
   been completed (or paid, with `notify_when: paid`), sends one HTTP GET per conversion to Partner Ads
   (`Client` + `NotifyUrlProvider`), and marks the conversion `notified`, retries it later, or marks it `failed`
   after `--max-tries` attempts. Merchants see all of this in the admin under Marketing → Partner Ads Conversions.

## Invariants - do not break these

- **Nothing that can fail runs inside the customer's checkout request.** The only checkout-time work is one
  partner id read (a single lazy SELECT on the client metadata by setono/client-bundle - the storage never writes
  at checkout) and a single insert. No HTTP calls, no try/catch that could leave a closed entity manager
  behind, no constraint that can be violated (see next point). Read the docblock on `CreateConversionSubscriber`
  before touching it.
- **There is deliberately no unique constraint on `conversion.order`** (the association is many-to-one).
  Concurrent checkout-complete requests can create duplicate conversions; that is accepted. "At most one
  notification per order" is enforced in `ProcessConversionsCommand`, which marks any further conversion for
  an already notified order as `skipped`.
- **The process command is locked** (`symfony/lock`, resource `setono_sylius_partner_ads_process_conversions`)
  so overlapping cron runs cannot double-send. The lock is released explicitly in a `finally` block.
- **Only a positive integer is ever a partner id.** Empty, garbage, zero, negative, or array values from the
  query string or the stored metadata are treated as absent - never cast to `0` and reported.
- **Document the why.** Non-obvious decisions are explained where they live (docblocks, XML mapping comments,
  the README's "Design notes"). Keep that up to date when you change behaviour.

## Layout

- `src/Model` - `Program` (one per channel, admin CRUD) and `Conversion` (system-created, admin index/delete
  only). Both are Sylius resources mapped as `<mapped-superclass>` in `config/doctrine/model`; the resource
  bundle turns them into entities and registers `setono_sylius_partner_ads.{factory,repository,manager}.{program,conversion}`.
- `src/Doctrine/ORM` - repositories. `ConversionRepository::findPending()` holds the eligibility rules
  (pending + checkout completed + not cancelled, plus paid when `NotifyWhen::Paid`).
- `src/EventListener` - the two subscribers above. `src/PartnerIdStorage` - where the partner id lives between
  the affiliate click and the order: `PartnerIdStorageInterface`, implemented by `ClientMetadataPartnerIdStorage`
  (client bundle metadata under a namespaced key, TTL from `attribution_window`, re-validates on read, never
  removes). `src/Parser/PartnerIdParser` - the single place that validates a raw partner id value.
- `src/Command/ProcessConversionsCommand` - the cron command. `src/Enum/NotifyWhen` - the `notify_when`
  config value.
- `src/Client` + `src/UrlProvider` - the HTTP call. Placeholders in the notify URL are URL-encoded; any 2xx is
  a success. `RegisterHttpClientPass` aliases `setono_sylius_partner_ads.http_client` to the configured PSR-18
  service (default `psr18.http_client`, a leading `@` is tolerated). The plugin deliberately ships no
  fallback HTTP client - the application chooses the client and its timeouts.
- `src/DependencyInjection` - `Configuration` (config tree incl. both resources) and the extension, which also
  prepends the two admin grids. `config/services.php` wires everything; `config/routes/admin.yaml` exposes the
  admin resources; `translations/` holds all UI strings.

## Conventions

- Services are `final readonly` classes behind interfaces, wired explicitly in `config/services.php`
  (no autowiring). Reference resource-bundle-generated services by id and say so in a comment
  ("registered by the resource bundle").
- Inject `Doctrine\Persistence\ManagerRegistry` and use `Setono\Doctrine\ORMTrait` (`$this->getManager($entity)`)
  rather than injecting an entity manager.
- Plugin models are mapped superclasses; keep `nullable`/`unique` explicit on join columns and add a matching
  `UniqueEntity` constraint for anything the database enforces uniquely, so admins get form errors, not 500s.
- Native enums live in `src/Enum`. Validate input with `filter_var` rather than casting; PHPStan runs at level
  max and rejects casting `mixed`.
- Breaking changes are acceptable on `3.x` at the moment (it is heading for a major); list them and the upgrade
  notes in the PR description.
- PRs are squash-merged into `3.x`; the branch is deleted on merge. Do not put session links in PR
  descriptions.

## Development

- PHP 8.4 (`.php-version`, honoured by the Symfony CLI). `vendor/` is installed under 8.4; if plain `php` is an
  older version the Composer platform check fails - use `symfony php vendor/bin/...` or
  `/opt/homebrew/opt/php@8.4/bin/php` explicitly. Note that `symfony php` sets `APP_ENV=dev`;
  `phpunit.xml.dist` forces `APP_ENV=test` for the tests, but pass `APP_ENV=test` yourself when running
  `tests/Application/bin/console`.
- Checks, all of which must pass before pushing: `vendor/bin/ecs check --fix`, `vendor/bin/phpstan analyse`,
  `vendor/bin/phpunit`, and `vendor/bin/infection` (`minMsi` is 100 - kill escaped mutants with tests, and
  exclude genuinely equivalent ones per method in `infection.json5` with a comment explaining why).
- The test application lives in `tests/Application`. Functional tests (`tests/Functional`) that need a database
  use the `DATABASE_URL` in `tests/Application/.env` (a local MariaDB/MySQL, database
  `setono_sylius_partner_ads_test`) and **skip themselves when no database is reachable**. Create or update the
  schema with `APP_ENV=test tests/Application/bin/console doctrine:schema:update --force` (this also creates the
  client bundle's `setono_client__metadata`; the test application registers `SetonoClientBundle`).
  `dama/doctrine-test-bundle` wraps every test in a rolled-back transaction (this needs `use_savepoints: true`,
  see `tests/Application/config/packages/doctrine.yaml`). Build fixtures in tests so they tolerate pre-existing
  data - the CI database has the Sylius fixtures loaded (e.g. locale `en_US`, channel `FASHION_WEB`).
- Compare ids, not entities, in database test assertions: a failing `assertSame` on Doctrine entities makes
  PHPUnit export the whole object graph, which effectively hangs (and shows up as timed-out mutants).

## CI (setono/sylius-plugin actions, `.github/workflows/build.yaml`)

- `static-code-analysis` removes `sylius/sylius` before running PHPStan. The test kernel cannot boot there, so
  `tests/PHPStan/console_application.php` falls back to an empty console application; command options are
  then `mixed`, and the resource bundle version resolved there has a non-generic `FactoryInterface`. Keep
  `src/` free of `mixed` casts and generic `FactoryInterface` annotations.
- `dependency-analysis` runs composer-dependency-analyser with `require-dev` unset: every package and PHP
  extension used in `src/` and `config/` must be in `require` (e.g. `ext-filter`), and unused ones must go.
- `mutation-tests` and `code-coverage` run without a database; only `functional-tests` has MySQL (with the
  Sylius fixtures loaded). Codecov's statuses are informational.
- `backwards-compatibility` (Roave, PRs only) fails on any removed public class - expected for a major bump.

## Translations

- All user-facing strings live in `translations/`: `messages.<locale>.yaml` holds the admin UI labels and
  `validators.<locale>.yaml` the validation messages. English (`en`) is the source of truth.
- The plugin ships these locales, and every key must exist in every one of them:
  `en`, `da`, `sv`, `no`, `fi`, `de`, `fr`, `es`, `it`, `nl`, `pl`, `pt`, `cs`, `hu`, `ro`, `uk`.
  When you add, rename or remove a key, update all 16 locale files of that domain in the same change - never
  leave a locale behind. `tests/Unit/Translation/TranslationCataloguesTest.php` fails if a locale is missing
  or has extra keys.
- Conventions: keep "Partner Ads" untranslated (brand name); Norwegian uses the `no` code like Sylius core
  does; Portuguese is European Portuguese; keep validator placeholders such as `{{ compared_value }}`
  verbatim; inflect the conversion state labels ("Notified", "Failed", "Skipped") to agree with
  "conversion" where the language requires it.
- New catalogue files are only picked up by the test application after clearing `tests/Application/var/cache`
  (the translator's resource list is compiled into the container).
