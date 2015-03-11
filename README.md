#DoctrineEncryptBundle

Bundle allows to create doctrine entities with fields that will be protected with 
help of some encryption algorithm in database and it will be clearly for developer, because bundle is uses doctrine life cycle events

This is an fork from the original bundle created by vmelnik-ukrain (Many thanks to him) which can be found here:
[vmelnik-ukraine/DoctrineEncryptBundle](https://github.com/vmelnik-ukraine/DoctrineEncryptBundle)

I improved several things, i make better use of the doctrine events. and it works with lazy loading (relationships)!
This will be an long term project we will be working on with long-term support and backward compatibility. We are using this bundle in all our own symfony2 project.
More about us can be found on our website. [Ambta.com](https://ambta.com)

###What does it do exactly

It gives you the opportunity to add the @Encrypt annotation above each string property

```
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

The bulk of the documentation is stored in the `Resources/doc/index.md` file in this bundle

[Read the Documentation](https://github.com/marcel-ambta/DoctrineEncryptBundle/blob/master/Resources/doc/index.md)

###License

This bundle is under the MIT license. See the complete license in the bundle

###Versions

I'm using Semantic Versioning like described [here](http://semver.org)

###Todos

The following items will be done in order

1. Review of complete code + fixes/improvements and inline documentation
2. Add support for the other doctrine relationships (manyToMany, ManyToOne)
3. Add "Encryption" (reformating based on key) of integers, data time object
4. Recreate documentation
5. Create example code
6. Create an function to encrypt unencrypted database and vice versa
7. Look for a posibility of automatic encryption of query parameters
8. Look for a positbility to override findOneBy for automatic encryption of parameters
9. Add "Encryption" (reformating based on key) on all other database types) [Doctrine documentation Types](http://doctrine-dbal.readthedocs.org/en/latest/reference/types.html)
