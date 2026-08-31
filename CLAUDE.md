# CLAUDE.md

Guidance for working in this repository.

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
