# Sylius Partner Ads Plugin

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-travis]][link-travis]
[![Quality Score][ico-code-quality]][link-code-quality]

This plugin will track sales made by Partner Ads affiliates.

It works by saving the affiliate partner id when the visitor visits any page on your shop. Then when the user successfully completes an order it will send a HTTP request to Partner Ads telling them to credit the affiliate partner.

## Installation

### Step 1: Download the plugin

Open a command console, enter your project directory and execute the following command to download the latest stable version of this plugin:

```bash
$ composer require setono/sylius-partner-ads-plugin
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.

### Step 2: Enable the plugin

Then, enable the plugin by adding it to the list of registered plugins/bundles
in the `config/bundles.php` file of your project:

```php
<?php
# config/bundles.php
return [
    Setono\SyliusPartnerAdsPlugin\SetonoSyliusPartnerAdsPlugin::class => ['all' => true],
];
```

### Step 3: Configure plugin

```yaml
# config/packages/_sylius.yaml
imports:
    # ...
    - { resource: "@SetonoSyliusPartnerAdsPlugin/Resources/config/app/config.yaml" }
    # ...
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

### Step 7 (optional): Configure Symfony Messenger
This plugin will make a HTTP request to Partner Ads when a customer completes an order. This will make the 'Thank you' page load slower. To circumvent that you can use RabbitMQ with Symfony Messenger to send this HTTP request asynchronously.

Follow the installation instructions here: [How to Use the Messenger](https://symfony.com/doc/current/messenger.html) and then [configure a transport](https://symfony.com/doc/current/messenger.html#transports).

Basically you should do:
```bash
$ composer req messenger symfony/serializer-pack
```

And then configure the Messenger component:
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

After this the Messenger will be automatically enabled in this plugin and subsequently it will send an asynchronous request to Partner Ads instead of a synchronous.

For testing purposes you can sign up for a free RabbitMQ cloud service here: [CloudAMQP](https://www.cloudamqp.com/plans.html).

[ico-version]: https://img.shields.io/packagist/v/setono/sylius-partner-ads-plugin.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://travis-ci.com/Setono/SyliusPartnerAdsPlugin.svg?branch=master
[ico-code-quality]: https://img.shields.io/scrutinizer/g/Setono/SyliusPartnerAdsPlugin.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/setono/sylius-partner-ads-plugin
[link-travis]: https://travis-ci.com/Setono/SyliusPartnerAdsPlugin
[link-code-quality]: https://scrutinizer-ci.com/g/Setono/SyliusPartnerAdsPlugin
