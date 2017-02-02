#DoctrineEncryptBundle

Bundle allows to create doctrine entities with fields that will be protected with 
help of some encryption algorithm in database and it will be clearly for developer, because bundle is uses doctrine life cycle events

This is an fork from the original bundle created by vmelnik-ukrain (Many thanks to him) which can be found here:
[vmelnik-ukraine/DoctrineEncryptBundle](https://github.com/vmelnik-ukraine/DoctrineEncryptBundle)
this is again a fork from the bundle [nepda/doctrine-encrypt](https://github.com/nepda/doctrine-encrypt) from Marcel van Nuil.

## goal of this fork
I only improved the Encryptor : multiple existed but they where base on mcrypt and had flaws. I replaced them with an openssl alternative, found partly from this fork (again!) : https://github.com/nepda/doctrine-encrypt/blob/master/src/DoctrineEncrypt/Encryptors/OpenSslEncryptor.php
If the author accept to merge my change, I will probably close this fork.

I also borrowed the "SensitiveValue" and "Mask" class from the great [payum](https://github.com/Payum/Payum) library in order to prevent the secret key to be printed (for more information, see [payum doc](https://github.com/Payum/Payum/blob/master/src/Payum/Core/Resources/docs/working-with-sensitive-information.md)).  

## scope of this fork
The rest of this file anf of the doc will probably remain the forked content. Exept for the namespaces and the authors list that I changed (the search & replace way).

###What does it do exactly

It gives you the opportunity to add the @Encrypt annotation above each string property

```php
/**
 * @Encrypt
 */
protected $username;
```

The bundle uses doctrine his life cycle events to encrypt the data when inserted into the database and decrypt the data when loaded into your entity manager.
It is only able to encrypt string values at the moment, numbers and other fields will be added later on in development.

###Advantages and disadvantaged of an encrypted database

####Advantages
- Information is stored safely
- Not worrying about saving backups at other locations
- Unreadable for employees managing the database

####Disadvantages
- Can't use ORDER BY on encrypted data
- In SELECT WHERE statements the where values also have to be encrypted
- When you lose your key you lose your data (Make a backup of the key on a safe location)

###Documentation

This bundle is responsible for encryption/decryption of the data in your database.
All encryption/decryption work on the server side.

The following documents are available:

* [Installation](https://github.com/combodo/DoctrineEncryptBundle/blob/master/Resources/doc/installation.md)
* [Configuration](https://github.com/combodo/DoctrineEncryptBundle/blob/master/Resources/doc/configuration.md)
* [Usage](https://github.com/combodo/DoctrineEncryptBundle/blob/master/Resources/doc/usage.md)
* [Console commands](https://github.com/combodo/DoctrineEncryptBundle/blob/master/Resources/doc/commands.md)
* [Custom encryption class](https://github.com/combodo/DoctrineEncryptBundle/blob/master/Resources/doc/custom_encryptor.md)

###License

This bundle is under the MIT license. See the complete license in the bundle

###Versions

I'm using Semantic Versioning like described [here](http://semver.org)

###Todos

The following items will be done in order

1. ~~Review of complete code + fixes/improvements and inline documentation (2.1.1)~~
2. ~~Add support for the other doctrine relationships (manyToMany, ManyToOne) (2.2)~~
4. ~~Recreate documentation (2.3)~~
5. ~~Create example code (2.3)~~
6. ~~Create an function to encrypt unencrypted database and vice versa (console command, migration, changed key, etc.) (2.4)~~
7. Look for a posibility of automatic encryption of query parameters (2.5)
8. Look for a posibility to override findOneBy for automatic encryption of parameters (2.6)
9. Add support to encrypt data by reference to other property as key (Encrypt data specific to user with user key etc.) (2.7)
10. Add [Format-preserving encryption](http://en.wikipedia.org/wiki/Format-preserving_encryption) for all data types [Doctrine documentation Types](http://doctrine-dbal.readthedocs.org/en/latest/reference/types.html) (3.0)

