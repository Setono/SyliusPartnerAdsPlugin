# Sylius Partner Ads Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

This plugin will track sales made by Partner Ads affiliates.

It works by saving the affiliate partner id when the visitor visits any page on your shop. Then when the user successfully completes an order it will send a HTTP request to Partner Ads telling them to credit the affiliate partner.

## Installation

### Step 1: Download the plugin

```bash
composer require setono/sylius-partner-ads-plugin
```

### Step 2: Enable the plugin

Then, enable the plugin by adding it to the list of registered plugins/bundles
in the `config/bundles.php` file of your project before (!) `SyliusGridBundle`:

```php
<?php
# config/bundles.php
return [
    Setono\SyliusPartnerAdsPlugin\SetonoSyliusPartnerAdsPlugin::class => ['all' => true],
    Sylius\Bundle\GridBundle\SyliusGridBundle::class => ['all' => true],
];
```

### Step 3: Configure plugin

```yaml
# config/packages/setono_sylius_partner_ads.yaml
imports:
    - { resource: "@SetonoSyliusPartnerAdsPlugin/Resources/config/app/config.yaml" }
```

### Step 4: Import routing

```yaml
# config/routes/setono_sylius_partner_ads.yaml
setono_partner_ads_plugin:
    resource: "@SetonoSyliusPartnerAdsPlugin/Resources/config/routing.yaml"
```

### Step 5: Update your database schema

```bash
$ php bin/console doctrine:migrations:diff
$ php bin/console doctrine:migrations:migrate
```

### Step 6: Setup program

Login to your Sylius app admin and go to the Partner Ads page and click "Create" to create a new program. Fill in the program id of your Partner Ads program, make sure "enable" is toggled on, and choose which channel the program should be applied to. Please notice you should only make one program for each channel, or else you will end up with undefined behaviour.

### Step 7 (optional, but recommended): Configure Async HTTP requests



[ico-version]: https://poser.pugx.org/setono/sylius-partner-ads-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-partner-ads-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusPartnerAdsPlugin/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-partner-ads-plugin
[link-github-actions]: https://github.com/Setono/SyliusPartnerAdsPlugin/actions
