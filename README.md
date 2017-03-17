#DoctrineEncryptBundle version 2.0
 

Bundle allows to create doctrine entities with fields that will be protected with 
help of some encryption algorithm in database and it will be clearly for developer, because bundle is uses doctrine life cycle events

This is an fork from the original bundle created by vmelnik-ukrain (Many thanks to him) which can be found here:
[vmelnik-ukraine/DoctrineEncryptBundle](https://github.com/vmelnik-ukraine/DoctrineEncryptBundle)
this is again a fork from the bundle [nepda/doctrine-encrypt](https://github.com/nepda/doctrine-encrypt) from Marcel van Nuil.

## goal of this fork
My main goal is to improve the Encryptor : multiple existed but they where base on mcrypt and had flaws. I replaced them with an openssl alternative, found partly from this fork (again!) : https://github.com/nepda/doctrine-encrypt/blob/master/src/DoctrineEncrypt/Encryptors/OpenSslEncryptor.php
If the author accept to merge my change, I will probably close this fork.

I also borrowed the "SensitiveValue" and "Mask" class from the great [payum](https://github.com/Payum/Payum) library in order to prevent the secret key to be printed (for more information, see [payum doc](https://github.com/Payum/Payum/blob/master/src/Payum/Core/Resources/docs/working-with-sensitive-information.md)).  

I added a twig extension in case of the need to decode the data only on display. (need additional work to be fully satisfying, like optionally do ont decode in the entity)

The encryptor also throw verbose errors and handle a simple integrity check (the length of the data is stored with the data in a json string, and is verified on the decrypt side).

## scope of this fork
The rest of this file anf of the doc will probably remain the forked content. Exept for the namespaces and the authors list that I changed (the search & replace way).

###What does it do exactly

It gives you the opportunity to add the @Encrypt annotation above each string property

```php

    /**
     * @var string
     * @ORM\Column(name="host", type="string", length=255)
     * @Encrypted
     */
    private $host;
    /** @var string store the decryptedVersion of $host */
    private $hostDecrypted;
    
        /**
         * Set host
         * 
         * You MUST assign null to the decryped var 
         * 
         *
         * @param string $host
         *
         * @return ItopUser
         */
        public function setHost($host)
        {
            $this->host             = $host;
            $this->hostDecrypted    = null;//mandatory if you want your host to be encoded again!
            return $this;
        }
    
        /**
         * Get host
         * 
         * You MUST return the decryped var if it is set
         * 
         * @return string
         */
        public function getHost()
        {
            if (!empty($this->hostDecrypted)) {
                return $this->hostDecrypted;
            }
            return $this->host;
        }
    
        /**
         * INTERNAL 
         * Set hostDecrypted
         *
         * @param string $host
         *
         * @return ItopUser
         */
        public function setHostDecrypted($hostDecrypted)
        {
            $this->hostDecrypted = $hostDecrypted;
    
            return $this;
        }
    
        /**
         * INTERNAL 
         * Get hostDecrypted
         *
         * @return string
         */
        public function getHostDecrypted()
        {
            return $this->hostDecrypted;
        }
```

The bundle uses doctrine his life cycle events to encrypt the data when inserted into the database and decrypt the data when loaded into your entity manager.
It is only able to encrypt string values at the moment, numbers and other fields will be added later on in development.
You NEED to add the *Decrypt field, it MUST not be persisted, the gettter/setter MUST exists and have standard names, the getter/setter of the encrypted propterties must follow the pattern above  



## this verstion 2.0 drop support from many functionnalities of the 1.0 :
many unused features was removed for the sake of performance and readability : 
- EmbeddedAnnotation are no more supported (I don't ever know what it is so... if you'r curious, google for `Doctrine\ORM\Mapping\Embedded`)
- the annotation only mode is no more tested, use it knowing the risk ;)   


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
All encryption/decryption work on the PHP's server side.

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

