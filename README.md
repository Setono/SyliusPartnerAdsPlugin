# Sylius Partner Ads Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

This plugin will track sales made by Partner Ads affiliates.

It works by remembering the affiliate partner id for the visitor when they land on your shop through an affiliate link. Visitors are recognised through [setono/client-bundle](https://github.com/Setono/client-bundle), which gives every visitor a client id (its `setono_client_id` cookie) and lets the plugin keep the partner id in that client's metadata for the length of the attribution window - the plugin sets no cookie of its own. When the visitor completes an order, the plugin stores a *conversion* referencing the order and the partner id. A console command - meant to be run periodically via cron - then notifies Partner Ads about conversions whose orders have been **completed** (or, if you prefer, **paid** - see [step 8](#step-8-optional-choose-when-to-notify-partner-ads)), telling them to credit the affiliate partner.

Because the notification happens out-of-band, a slow or failing Partner Ads endpoint can never affect your customers' checkout, orders that get cancelled before the command runs are never reported, and failed notifications are retried automatically on the next run.

## Requirements

| Package      | Version    |
|--------------|------------|
| PHP          | >=8.2      |
| sylius/sylius| ^2.0       |
| Symfony      | ^6.4 \|\| ^7.4 |

> For Sylius 1.10 use the [`2.x`](https://github.com/Setono/SyliusPartnerAdsPlugin/tree/2.x) version of this plugin.
>
> Upgrading from 2.x? Read [UPGRADE.md](UPGRADE.md) - several steps are required.

## Installation

### Step 1: Download the plugin

```bash
composer require setono/sylius-partner-ads-plugin
```

### Step 2: Enable the plugin

Enable the plugin by adding it to the list of registered plugins/bundles in the `config/bundles.php` file of your
project, **before** `SyliusGridBundle` (this is required so the plugin's resource is registered before the grid is built).
Also register [setono/client-bundle](https://github.com/Setono/client-bundle), which the plugin uses to recognise returning visitors (Symfony Flex may already have done that for you):

```php
<?php
# config/bundles.php
return [
    // ...
    Setono\ClientBundle\SetonoClientBundle::class => ['all' => true],
    Setono\SyliusPartnerAdsPlugin\SetonoSyliusPartnerAdsPlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
    // ...
];
```

### Step 3: Import routing

```yaml
# config/routes/setono_sylius_partner_ads.yaml
setono_sylius_partner_ads:
    resource: "@SetonoSyliusPartnerAdsPlugin/config/routes.yaml"
```

### Step 4: HTTP client

The plugin sends its notifications through a [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client and ships none of its own. By default it uses `psr18.http_client`, the adapter Symfony registers automatically when [`symfony/http-client`](https://symfony.com/doc/current/http_client.html#psr-18-and-psr-17) is installed - which Sylius already requires. The adapter (like the plugin itself) needs a PSR-17 factory, so make sure one is installed:

```bash
composer require nyholm/psr7
```

To use another PSR-18 client, point the plugin at its service id (with or without a leading `@`):

```yaml
# config/packages/setono_sylius_partner_ads.yaml
setono_sylius_partner_ads:
    http_client: my_psr18_client
```

Timeouts are the client's concern - Symfony's HTTP client has sensible defaults, and you can tune them through its own configuration.

### Step 5: Update your database schema

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

This creates the plugin's tables and, unless your application has it already, the client bundle's `setono_client__metadata` table where the partner id is kept per visitor.

### Step 6: Setup program

Login to your Sylius app admin and go to the Partner Ads page and click "Create" to create a new program. Fill in the program id of your Partner Ads program, make sure "enable" is toggled on, and choose which channel the program should be applied to. Please notice you should only make one program for each channel, or else you will end up with undefined behaviour.

### Step 7: Schedule the process command

Conversions are sent to Partner Ads by a console command that picks up conversions whose orders have been completed (or paid, see step 8). Schedule it via cron (every 5-15 minutes is fine - Partner Ads does not need real-time notifications):

```
*/10 * * * * php /path/to/your/app/bin/console setono:sylius-partner-ads:process-conversions
```

If a notification fails (e.g. Partner Ads is down), the conversion stays pending and is retried on subsequent runs. After 10 unsuccessful tries (configurable with `--max-tries`) the conversion is marked as failed, and the last error is saved on the conversion for debugging.

The command takes a lock while it runs, so overlapping runs (e.g. a slow run and the next cron tick) cannot notify Partner Ads twice about the same order. It uses your application's default lock store - if you run cron on more than one server, configure a shared store (e.g. Redis or your database) as described in the [Symfony lock documentation](https://symfony.com/doc/current/lock.html).

The customer IP sent to Partner Ads is the one Sylius stores on the order (`customerIp`), which Sylius takes from `Request::getClientIp()` when the order is completed. If your shop runs behind a reverse proxy or load balancer, make sure [Symfony's trusted proxies](https://symfony.com/doc/current/deployment/proxies.html) are configured - otherwise Partner Ads receives the proxy's IP instead of the customer's.

### Step 8 (optional): Choose when to notify Partner Ads

By default, Partner Ads is notified as soon as the customer has completed the checkout, i.e. when the order is placed. This is how affiliate networks usually work: the sale is tracked right away, and if the order is never paid you cancel the sale in the Partner Ads panel.

If you would rather only report orders that have actually been paid, configure:

```yaml
setono_sylius_partner_ads:
    notify_when: paid # defaults to 'completed'
```

In both modes, conversions for orders that have been cancelled are never sent.

### Step 9 (optional): Adjust the attribution window

An order is credited to a partner when it is completed within the attribution window after the visitor clicked the affiliate link. Partner Ads documents 40 days, which is the default:

```yaml
setono_sylius_partner_ads:
    attribution_window: 40 # days
```

The window cannot outlast the client bundle's own cookie (`setono_client.cookie.expiration`, `+365 days` by default).

## Consent and privacy

The plugin sets no cookie of its own. Visitors are recognised through the client bundle's `setono_client_id` cookie, and the partner id is kept in the `setono_client__metadata` table against that anonymous client id. If your shop needs consent before setting that cookie, listen to the bundle's `PreStoreCookieEvent` and set `$event->store = false` until consent is given - visitors without the cookie are simply never attributed. When a conversion is sent, Partner Ads receives the order number, the order total and the customer's IP address.

## Design notes

A few decisions in this plugin are deliberate and worth knowing about:

- **Nothing that can fail happens during checkout.** The plugin only stores a small conversion row when an order is completed; the HTTP request to Partner Ads happens later in the console command. A slow or failing Partner Ads endpoint can therefore never affect your customers.
- **There is no unique constraint on the conversion's order.** Two concurrent checkout-complete requests for the same cart (a double click, a browser retry, a payment return racing the customer's return) can both create a conversion for the same order. A unique constraint would stop the duplicate by throwing an exception *inside the checkout*, failing the order for the customer. Instead, duplicates are allowed to exist, and the console command guarantees that Partner Ads is notified at most once per order: once a conversion for an order has been notified, any other conversion for that order is marked as *skipped*.
- **The console command is locked** so that two overlapping runs cannot both send the same conversion.
- **The partner id lives in the client metadata, not in a plugin cookie.** Reading it at checkout is a single lazy SELECT by the client bundle, and the plugin never writes metadata during checkout. The storage is a small interface (`PartnerIdStorageInterface`), so it can be replaced if your setup needs something else.

[ico-version]: https://poser.pugx.org/setono/sylius-partner-ads-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-partner-ads-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusPartnerAdsPlugin/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-partner-ads-plugin
[link-github-actions]: https://github.com/Setono/SyliusPartnerAdsPlugin/actions
