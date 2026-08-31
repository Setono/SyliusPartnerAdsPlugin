# Upgrade guide

## From 2.x (Sylius 1.x) to 3.x (Sylius 2)

3.x targets Sylius 2 and changes *how* Partner Ads is notified: instead of an HTTP request during the customer's
thank-you page, the plugin now stores a *conversion* when an order is completed and a console command - run via
cron - sends the notifications. Read the whole list; several steps are required for the plugin to work at all.

### Requirements

| | 2.x | 3.x |
|---|---|---|
| PHP | >= 7.4 | >= 8.2 |
| Sylius | 1.10 | ^2.0 |
| Symfony | ^5.4 \|\| ^6.0 | ^6.4 \|\| ^7.4 |

Dependencies: the plugin no longer requires `symfony/messenger`, `sylius/order` or `ext-mbstring`, and now requires
`symfony/lock`, `symfony/console`, `doctrine/persistence`, `setono/doctrine-orm-trait` and `ext-filter`.

### Installation

1. **Remove the config import.** 2.x required importing
   `@SetonoSyliusPartnerAdsPlugin/Resources/config/app/config.yaml` in `config/packages/_sylius.yaml`. The plugin
   now prepends its grids itself; the file is gone, so the import must go too or the container fails to build.
2. **Change the routing import** from `@SetonoSyliusPartnerAdsPlugin/Resources/config/routing.yaml` to
   `@SetonoSyliusPartnerAdsPlugin/config/routes.yaml`. Route names are unchanged
   (`setono_sylius_partner_ads_admin_program_*`); the new conversion routes are
   `setono_sylius_partner_ads_admin_conversion_index` and `..._delete`.
3. The plugin must still be registered **before** `SyliusGridBundle` in `config/bundles.php`.
4. Plugin files moved from `src/Resources/{config,translations}` to `config/` and `translations/`. This only
   matters if you referenced those paths directly.

### Configuration

- **`messenger` node removed** (`command_bus` and `transport`). Remove it from your configuration, or the
  container fails to build with an unknown-option error. Notifications are no longer dispatched through
  Messenger, so an async transport is no longer needed.
- **`http_client`** now defaults to `psr18.http_client`, the PSR-18 adapter Symfony registers when
  `symfony/http-client` is installed (Sylius requires it). **The Buzz fallback is gone**: if you relied on the
  plugin picking up `kriswallsmith/buzz` automatically, either do nothing (the default client is used) or set
  `http_client` to the service id of your PSR-18 client. A PSR-17 factory such as `nyholm/psr7` must be
  installed. A leading `@` on the service id is now tolerated. If the configured service does not exist, the
  container fails to build with a message telling you what to install or configure.
- **New `notify_when`** (`completed`, the default, or `paid`), see the README. `completed` corresponds to the
  2.x behaviour of reporting the order as soon as it was placed.
- Unchanged: `urls.notify`, `query_parameter`, `cookie.name`, `cookie.expire`, `resources.program`.
  New: `resources.conversion`.

### Database

Run `doctrine:migrations:diff` and `doctrine:migrations:migrate`. The migration will:

- create the new table `setono_sylius_partner_ads__conversion`;
- on `setono_sylius_partner_ads__program`, drop the index `idx_enabled` and make `channel_id` `NOT NULL`. Make
  sure no program row has a `NULL` channel before migrating (the admin form has always required one).

### Schedule the console command (required)

Nothing is sent to Partner Ads until `setono:sylius-partner-ads:process-conversions` runs. Schedule it via cron,
e.g. every 10 minutes:

```
*/10 * * * * php /path/to/your/app/bin/console setono:sylius-partner-ads:process-conversions
```

The command takes a lock (via `symfony/lock`) using your application's default lock store. If cron runs on more
than one server, configure a shared store (Redis, database) - see the README.

### Behaviour changes

- **When and how Partner Ads is notified.** 2.x sent the request while rendering the thank-you page, with no
  retries, and a slow or failing Partner Ads endpoint could break that page. 3.x stores a pending conversion when
  the order is completed and sends it from the console command: failures are retried on later runs (up to
  `--max-tries`, default 10, after which the conversion is marked *failed*), Partner Ads is never notified more
  than once per order, and orders cancelled before the command runs are never reported. Notifications are
  therefore delayed by up to one cron interval.
- **Partner id validation.** The affiliate query parameter (`?paid=`) and the cookie are only accepted when they
  hold a positive integer. Empty, malformed, zero, negative or array values are ignored - 2.x cast them to
  partner id `0`, which overwrote a legitimate attribution. A malformed link can no longer produce a 400.
- **HTTP responses.** Any 2xx response from Partner Ads is a success; 2.x only accepted `200`.
- **Customer IP.** The IP sent to Partner Ads is now the order's `customerIp`, which Sylius records from
  `Request::getClientIp()` when the order is completed. Behind a reverse proxy or load balancer, configure
  Symfony's trusted proxies or Partner Ads receives the proxy's IP.
- **One program per channel** is now validated with a form error instead of failing with a database error.
- **Admin.** A new "Partner Ads Conversions" grid (Marketing menu) lists every conversion with its state
  (`pending`, `notified`, `skipped`, `failed`), tries and last error. Translations for 14 more locales are
  included.

### For integrators extending or decorating the plugin

- **Removed classes**: `EventListener\NotifySubscriber`, `Message\Command\Notify`,
  `Message\Handler\NotifyHandler`, `DependencyInjection\Compiler\RegisterCommandBusPass`,
  `Context\ProgramContext`, `Context\ProgramContextInterface`, `Exception\InterfaceNotFoundException`
  (all in the `Setono\SyliusPartnerAdsPlugin` namespace).
- **New classes** you may want to decorate or extend: `Model\Conversion` / `Model\ConversionInterface`,
  `Repository\ConversionRepositoryInterface`, `EventListener\CreateConversionSubscriber`,
  `Command\ProcessConversionsCommand`, `Enum\NotifyWhen`, `Parser\PartnerIdParser`.
- **Service ids changed.** Services are now registered under their class name, with an alias for each interface.
  If you decorated a 2.x service, use the new id:

  | 2.x id | 3.x id / alias |
  |---|---|
  | `setono_sylius_partner_ads.client.default` | `Setono\SyliusPartnerAdsPlugin\Client\Client` / `...\Client\ClientInterface` |
  | `setono_sylius_partner_ads.calculator.order_total` | `...\Calculator\OrderTotalCalculator` / `...\Calculator\OrderTotalCalculatorInterface` |
  | `setono_sylius_partner_ads.cookie_handler.default` | `...\CookieHandler\CookieHandler` / `...\CookieHandler\CookieHandlerInterface` |
  | `setono_sylius_partner_ads.url_provider.notify` | `...\UrlProvider\NotifyUrlProvider` / `...\UrlProvider\NotifyUrlProviderInterface` |
  | `setono_sylius_partner_ads.event_subscriber.set_cookie` | `...\EventListener\SetCookieSubscriber` |
  | `setono_sylius_partner_ads.event_subscriber.notify` | removed (see `...\EventListener\CreateConversionSubscriber`) |
  | `setono_sylius_partner_ads.context.program` | removed |
  | `setono_sylius_partner_ads.command_bus` | removed |
  | `setono_sylius_partner_ads.http_client.response_factory` | removed |
  | `setono_sylius_partner_ads.form.program` | `...\Form\Type\ProgramType` |

  Unchanged: `setono_sylius_partner_ads.http_client` (alias to the configured PSR-18 client),
  `setono_sylius_partner_ads.http_client.request_factory`, and the resource services
  `setono_sylius_partner_ads.{factory,repository,manager}.program` (plus the same for `conversion`).
- **`CookieHandlerInterface` semantics**: `has()` now returns `false` for a cookie that does not hold a valid
  partner id, and `get()` throws in that case. Signatures are unchanged.
- The public interfaces `ClientInterface`, `OrderTotalCalculatorInterface`, `NotifyUrlProviderInterface`,
  `ProgramRepositoryInterface` and `ProgramInterface` are unchanged.
- The `Program` and `Conversion` models are Doctrine mapped superclasses, so a custom model class configured via
  `resources.*.classes.model` can extend them.
- Translation keys from 2.x are unchanged (only new keys were added), so overridden translations keep working.
