#Configuration Reference

All available configuration options are listed below with their default values.

``` yaml

ambta_doctrine_encrypt:

# Secret key for encrypt algorithm. All secret key checks are encryptor tasks only.
# We recommend an 32 character long key (256 bits), Use another key for each project!

    secret_key:           ~ # Required

#  If you want, you can use your own Encryptor. Encryptor must implements EncryptorInterface interface
#  Default: Ambta\DoctrineEncryptBundle\Encryptors\AES256Encryptor

    encryptor_class:      ~ #optional

```
