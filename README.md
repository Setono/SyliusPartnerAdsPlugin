# Sylius Partner Ads Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

This plugin will track sales made by Partner Ads affiliates.

It works by saving the affiliate partner id when the visitor visits any page on your shop. Then when the user successfully completes an order it will send a HTTP request to Partner Ads telling them to credit the affiliate partner.

## Requirements

| Package      | Version    |
|--------------|------------|
| PHP          | >=8.2      |
| sylius/sylius| ^2.0       |
| Symfony      | ^6.4 \|\| ^7.4 |

> For Sylius 1.x use the [`1.x`](https://github.com/Setono/SyliusPartnerAdsPlugin/tree/1.x) version of this plugin.

## Installation

### Step 1: Download the plugin

```bash
composer require setono/sylius-partner-ads-plugin
```

### Step 2: Enable the plugin

Enable the plugin by adding it to the list of registered plugins/bundles in the `config/bundles.php` file of your
project, **before** `SyliusGridBundle` (this is required so the plugin's resource is registered before the grid is built):

```php
<?php
# config/bundles.php
return [
    // ...
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
If you already use a PSR-18 HTTP client you need to inject that service:
```yaml
setono_sylius_partner_ads:
    http_client: '@http_client_service_id'
```

If not, you can just install the Buzz library and it will automatically register the Buzz client as the HTTP client:

```bash
composer require kriswallsmith/buzz
```

### Step 5: Update your database schema

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Step 6: Setup program

Login to your Sylius app admin and go to the Partner Ads page and click "Create" to create a new program. Fill in the program id of your Partner Ads program, make sure "enable" is toggled on, and choose which channel the program should be applied to. Please notice you should only make one program for each channel, or else you will end up with undefined behaviour.

### Step 7 (optional, but recommended): Configure Async HTTP requests
This plugin will make a HTTP request to Partner Ads when a customer completes an order. This will make the 'Thank you' page load slower. To circumvent that you can use a transport (e.g. RabbitMQ) with Symfony Messenger to send this HTTP request asynchronously.

Follow the installation instructions here: [How to Use the Messenger](https://symfony.com/doc/current/messenger.html) and then [configure a transport](https://symfony.com/doc/current/messenger.html#transports).

Then configure the Messenger component:
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            amqp: "%env(MESSENGER_TRANSPORT_DSN)%"
```

```yaml
# .env
###> symfony/messenger ###
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
###< symfony/messenger ###
```

And finally configure the plugin to use your transport:

```yaml
setono_sylius_partner_ads:
    messenger:
        transport: amqp
```

After this the Messenger will be automatically enabled in this plugin and subsequently it will send an asynchronous request to Partner Ads instead of a synchronous.

[ico-version]: https://poser.pugx.org/setono/sylius-partner-ads-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-partner-ads-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusPartnerAdsPlugin/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-partner-ads-plugin
[link-github-actions]: https://github.com/Setono/SyliusPartnerAdsPlugin/actions
