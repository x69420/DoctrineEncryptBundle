<?php

namespace Combodo\DoctrineEncryptBundle\Subscribers;

use Combodo\DoctrineEncryptBundle\Security\SensitiveValue;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Util\ClassUtils;
use \ReflectionClass;
use Combodo\DoctrineEncryptBundle\Encryptors\EncryptorInterface;

/**
 * Doctrine event subscriber which encrypt/decrypt entities
 */
class DoctrineEncryptSubscriber implements EventSubscriber {

    /**
     * Encryptor interface namespace
     */
    const ENCRYPTOR_INTERFACE_NS = 'Combodo\DoctrineEncryptBundle\Encryptors\EncryptorInterface';

    /**
     * Encrypted annotation full name
     */
    const ENCRYPTED_ANN_NAME = 'Combodo\DoctrineEncryptBundle\Configuration\Encrypted';

    /**
     * Encryptor
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Annotation reader
     * @var \Doctrine\Common\Annotations\Reader
     */
    private $annReader;

    /**
     * Secret key
     * @var string
     */
    private $secretKey;

    /**
     * Used for restoring the encryptor after changing it
     * @var string
     */
    private $restoreEncryptor;

    /**
     * Count amount of decrypted values in this service
     * @var integer
     */
    public $decryptCounter = 0;

    /**
     * Count amount of encrypted values in this service
     * @var integer
     */
    public $encryptCounter = 0;

    /** @var array this is a cache of the fields that need to be encrypted/decrypted indexed by entities anf of refelctionClass */
    private static $cacheByEntity = [];



    /**
     * Initialization of subscriber
     *
     * @param Reader $annReader
     * @param string $encryptorClass  The encryptor class.  This can be empty if a service is being provided.
     * @param string|SensitiveValue $secretKey The secret key.
     * @param EncryptorInterface|NULL $service (Optional)  An EncryptorInterface.
     *
     * This allows for the use of dependency injection for the encrypters.
     */
    public function __construct(Reader $annReader, $encryptorClass, $secretKey, EncryptorInterface $service = NULL) {
        $this->annReader = $annReader;

        if ($secretKey instanceof SensitiveValue) {
            $this->secretKey = $secretKey;
        } else {
            $this->secretKey = new SensitiveValue($secretKey);
        }


        if ($service instanceof EncryptorInterface) {
            $this->encryptor = $service;
        } else {
            $this->encryptor = $this->encryptorFactory($encryptorClass, $this->secretKey);
        }

        $this->restoreEncryptor = $this->encryptor;

    }

    /**
     * Change the encryptor
     *
     * @param $encryptorClass
     */
    public function setEncryptor($encryptorClass) {

        if(!is_null($encryptorClass)) {
            $this->encryptor = $this->encryptorFactory($encryptorClass, $this->secretKey);
            return;
        }

        $this->encryptor = null;
    }

    /**
     * Get the current encryptor
     */
    public function getEncryptor() {
        if(!empty($this->encryptor)) {
            return get_class($this->encryptor);
        } else {
            return null;
        }
    }

    /**
     * Restore encryptor set in config
     */
    public function restoreEncryptor() {
        $this->encryptor = $this->restoreEncryptor;
    }

    /**
     * @deprecated present in the original source code, but I don't think it is nescessary
     * Listen a postUpdate lifecycle event.
     * Decrypt entities property's values when post updated.
     *
     * So for example after form submit the preUpdate encrypted the entity
     * We have to decrypt them before showing them again.
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args) {

        $entity = $args->getEntity();
        $this->processFields($entity, false);

    }

    /**
     * Listen a preUpdate lifecycle event.
     * Encrypt entities property's values on preUpdate, so they will be stored encrypted
     *
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args) {
        $changedProperties = array_keys($args->getEntityChangeSet());

        if (count($changedProperties) == 0) {
            return;
        }

        $entityFqcn = get_class($args->getEntity());

        if (empty(self::$cacheByEntity[$entityFqcn])) {
            return null;
        }
        if(empty($this->encryptor)) {
            return null;
        }

        $aEncryptFields  = self::$cacheByEntity[$entityFqcn]['properties'];
        $reflectionClass = self::$cacheByEntity[$entityFqcn]['ReflectionClass'];


        foreach ($changedProperties as $changedPropertyName) {
            if (! in_array($changedPropertyName, $aEncryptFields)) {
                continue;
            }

            $newValue = $args->getNewValue($changedPropertyName);

            if (is_null($newValue) || empty($newValue) || substr($newValue, -5) == '<ENC>') {
                continue;
            }

            $encryptedNewValue = $this->encryptor->encrypt($newValue);
            $args->setNewValue($changedPropertyName, $encryptedNewValue);
            $this->encryptCounter++;

            $setterDecrypted = 'set' . ucfirst($changedPropertyName) . 'Decrypted';

            if ($reflectionClass->hasMethod($setterDecrypted)) {
                $args->getEntity()->$setterDecrypted($newValue);//we re-store the value non encrypted since writting the encrypted on erase the decrypted
            }
        }
    }

    /**
     * Listen a postLoad lifecycle event.
     * Decrypt entities property's values when loaded into the entity manger
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args) {

        //Get entity and process fields
        $entity = $args->getEntity();
        $this->processFields($entity, false);

    }
    /**
     * Listen a prePersist lifecycle event. Checking and encrypt entities
     * which have @Encrypted annotation
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $this->processFields($entity);
    }

    /**
     * Listen to preflush event
     * Encrypt entities that are inserted into the database
     *
     * @param PreFlushEventArgs $preFlushEventArgs
     */
    public function preFlush(PreFlushEventArgs $preFlushEventArgs) {
        $unitOfWork = $preFlushEventArgs->getEntityManager()->getUnitOfWork();
        foreach($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->processFields($entity);
        }
    }

    /**
     * @deprecated present in the original source code, but I don't think it is nescessary
     * Listen to postFlush event
     * Decrypt entities that after inserted into the database
     *
     * @param PostFlushEventArgs $postFlushEventArgs
     */
    public function postFlush(PostFlushEventArgs $postFlushEventArgs) {
        $unitOfWork = $postFlushEventArgs->getEntityManager()->getUnitOfWork();
        foreach($unitOfWork->getIdentityMap() as $entityMap) {
            foreach($entityMap as $entity) {
                $this->processFields($entity, false);
            }
        }
    }

    public function getCache($entity)
    {
        $fqcn            = get_class($entity);
        if (! empty(self::$cacheByEntity[$fqcn])) {
            return self::$cacheByEntity[$fqcn];
        }

        $reflectionClass = new ReflectionClass($entity);

        self::$cacheByEntity[$fqcn]['properties']      = [];
        self::$cacheByEntity[$fqcn]['ReflectionClass'] = $reflectionClass;

        $properties      = $this->getClassProperties($reflectionClass);


        foreach ($properties as $refProperty) {
            //If property is an normal value and contains the Encrypt tag, lets encrypt/decrypt that property
            if (!$this->annReader->getPropertyAnnotation($refProperty, self::ENCRYPTED_ANN_NAME)) {
                continue;
            }

            self::$cacheByEntity[$fqcn]['properties'][] = $refProperty->getName();
        }

        return self::$cacheByEntity[$fqcn];
    }

    /**
     * Realization of EventSubscriber interface method.
     *
     * @return array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents() {
        return [
            //Events::postUpdate,
            Events::postLoad,
            Events::preUpdate,
            Events::prePersist,
            Events::preFlush,
            //Events::postFlush,
        ];
    }


    /**
     * Process (encrypt/decrypt) entities fields
     *
     * @param Object $entity doctrine entity
     * @param Boolean $isEncryptOperation If true - encrypt, false - decrypt entity
     *
     * @throws \RuntimeException
     *
     * @return object|null
     */
    public function processFields($entity, $isEncryptOperation = true) {

        $entityCache = $this->getCache($entity);

        if (empty($entityCache)) {
            return null;
        }
        if(empty($this->encryptor)) {
            return null;
        }

        $aEncryptFields  = $entityCache['properties'];
        $reflectionClass = $entityCache['ReflectionClass'];




        foreach ($aEncryptFields as $field) {
            /**
             * If followed standards, method name is getPropertyName, the propertyName is lowerCamelCase
             * So just uppercase first character of the property, later on get and set{$methodName} wil be used
             */
            $methodNameSuffix = ucfirst($field);
            $getter = 'get' . $methodNameSuffix;
            $setter = 'set' . $methodNameSuffix;
            $getterDecrypted = $getter . 'Decrypted';
            $setterDecrypted = $setter . 'Decrypted';
            //first whe try to check if a decrypted version already exists, the class has to add this functionnality, it is very recommanded, because if not done, the entity encrypted fields will be updated each time a persist operation is requested
            if(false == $isEncryptOperation && $reflectionClass->hasMethod($getterDecrypted) && null != ($decryptedValue = $entity->$getterDecrypted())) {
                continue;//the object has already been decrypted
            }

            //If private or protected check if there is an getter/setter for the property, based on the $methodName
            if ($reflectionClass->hasMethod($getter) && $reflectionClass->hasMethod($setter)) {

                //Get the information (value) of the property
                try {
                    $probablyEncryptedValue = $entity->$getter();
                } catch(\Exception $e) {
                    $probablyEncryptedValue = null;
                }

                /**
                 * Then decrypt, encrypt the information if not empty, information is an string and the <ENC> tag is there (decrypt) or not (encrypt).
                 * The <ENC> will be added at the end of an encrypted string so it is marked as encrypted. Also protects against double encryption/decryption
                 */
                if(false == $isEncryptOperation) {
                    if(!is_null($probablyEncryptedValue) and !empty($probablyEncryptedValue)) {
                        if(substr($probablyEncryptedValue, -5) == "<ENC>") {
                            $this->decryptCounter++;
                            $currentPropValue = $this->encryptor->decrypt(substr($probablyEncryptedValue, 0, -5));

                            if ($reflectionClass->hasMethod($setterDecrypted)) {
                                $entity->$setterDecrypted($currentPropValue);
                            }
                        }
                    }
                } else {
                    if(!is_null($probablyEncryptedValue) and !empty($probablyEncryptedValue)) {
                        if(substr($probablyEncryptedValue, -5) != "<ENC>") {
                            $this->encryptCounter++;
                            $currentPropValue = $this->encryptor->encrypt($entity->$getter());
                            $entity->$setter($currentPropValue);

                            if ($reflectionClass->hasMethod($setterDecrypted)) {
                                $entity->$setterDecrypted($probablyEncryptedValue);//we store the value non encrypted in a temporary placeholder
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Recursive function to get an associative array of class properties
     * including inherited ones from extended classes
     *
     * @param string $className Class name
     *
     * @return array
     */
    function getClassProperties(ReflectionClass $reflectionClass){


        $properties = $reflectionClass->getProperties();
        $propertiesArray = array();

        foreach($properties as $property){
            $propertyName = $property->getName();
            $propertiesArray[$propertyName] = $property;
        }

        if($parentClass = $reflectionClass->getParentClass()){
            $parentPropertiesArray = $this->getClassProperties($parentClass);
            if(count($parentPropertiesArray) > 0)
                $propertiesArray = array_merge($parentPropertiesArray, $propertiesArray);
        }

        return $propertiesArray;
    }

    /**
     * Encryptor factory. Checks and create needed encryptor
     *
     * @param string $classFullName Encryptor namespace and name
     * @param string $secretKey Secret key for encryptor
     *
     * @return EncryptorInterface
     * @throws \RuntimeException
     */
    private function encryptorFactory($classFullName, $secretKey) {
        $refClass = new \ReflectionClass($classFullName);
        if ($refClass->implementsInterface(self::ENCRYPTOR_INTERFACE_NS)) {
            return new $classFullName($secretKey);
        } else {
            throw new \RuntimeException('Encryptor must implements interface EncryptorInterface');
        }
    }
}
