# Upgrade

## From 1.x to 2.x

This release upgrades the plugin to Sylius 2.

### Requirements

| Package       | Old        | New            |
|---------------|------------|----------------|
| PHP           | >=7.4      | >=8.2          |
| sylius/sylius | ~1.10      | ^2.0           |
| Symfony       | ^5.4/^6.0  | ^6.4 \|\| ^7.4 |

### File layout

Following the current Setono plugin convention, configuration moved out of `src/Resources/` to the
repository root:

| Old                                                       | New                                  |
|-----------------------------------------------------------|--------------------------------------|
| `src/Resources/config/services.xml` (+ `services/*.xml`)  | `config/services.php` (PHP DSL)      |
| `src/Resources/config/routing.yaml`                       | `config/routes.yaml`                 |
| `src/Resources/config/routing/admin.yaml`                 | `config/routes/admin.yaml`           |
| `src/Resources/config/doctrine/model/Program.orm.xml`     | `config/doctrine/model/Program.orm.xml` |
| `src/Resources/config/validation/Program.xml`             | `config/validation/Program.xml`      |
| `src/Resources/translations/*`                            | `translations/*`                     |
| `src/Resources/config/grids/*.yaml`                       | registered automatically by the plugin |
| `src/Resources/config/app/config.yaml`                    | removed                              |

### Things you need to change in your project

1. **Routing import** now points to the new location:
   ```yaml
   # config/routes/setono_sylius_partner_ads.yaml
   setono_sylius_partner_ads:
       resource: "@SetonoSyliusPartnerAdsPlugin/config/routes.yaml"
   ```
2. **Grid registration is automatic.** Remove any import of
   `@SetonoSyliusPartnerAdsPlugin/Resources/config/app/config.yaml` — the admin grid is now registered
   by the plugin via its bundle extension `prepend()`.

### Removed

| Removed                                                       | Replacement                              |
|---------------------------------------------------------------|------------------------------------------|
| `@SetonoSyliusPartnerAdsPlugin/Resources/config/routing.yaml` | `@SetonoSyliusPartnerAdsPlugin/config/routes.yaml` |
| `@SetonoSyliusPartnerAdsPlugin/Resources/config/app/config.yaml` | (no longer needed — grid auto-registered) |
