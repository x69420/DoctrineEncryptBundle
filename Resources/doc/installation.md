#Installation

1. Download CombodoDoctrineEncryptBundle using composer
2. Enable the database encryption bundle
3. Configure the database encryption bundle

### Requirements

* php 5.4 
* php-mcrypt
* [doctrine/orm](https://packagist.org/packages/doctrine/orm) >= 2.5
* [symfony/framework-bundle](https://packagist.org/packages/symfony/framework-bundle) >= 2.0

### Step 1: Download CombodoDoctrineEncryptBundle using composer

CombodoDoctrineEncryptBundle should be installed usin [Composer](http://getcomposer.org/):

``` js
{
    "require": {
        "combodo/doctrine-encrypt-bundle": "2.4.*"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update combodo/doctrine-encrypt-bundle
```

Composer will install the bundle to your project's `vendor/combodo` directory.

### Step 2: Enable the bundle

Enable the bundle in the Symfony2 kernel by adding it in your /app/AppKernel.php file:

``` php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Combodo\DoctrineEncryptBundle\CombodoDoctrineEncryptBundle(),
    );
}
```

### Step 3: Set your configuration

All configuration value's are optional.
On the following page you can find de configuration information.

#### [Configuration](https://github.com/combodo/DoctrineEncryptBundle/blob/master/Resources/doc/configuration.md)
