#Installation

1. Download AmbtaDoctrineEncryptBundle using composer
2. Enable the Bundle

### Step 1: Download AmbtaDoctrineEncryptBundle using composer

Add AmbtaDoctrineEncryptBundle in your composer.json:

```js
{
    "require": {
        "ambta/doctrine-encrypt-bundle": "dev-master"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update ambta/doctrine-encrypt-bundle
```

Composer will install the bundle to your project's `vendor/ambta` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel by adding it:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Ambta\DoctrineEncryptBundle\AmbtaDoctrineEncryptBundle(),
    );
}
```