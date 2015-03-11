<?php

namespace Ambta\DoctrineEncryptBundle\Subscribers;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\Annotations\Reader;
use \Doctrine\ORM\EntityManager;
use \ReflectionClass;
use Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Doctrine event subscriber which encrypt/decrypt entities
 */
class DoctrineEncryptSubscriber implements EventSubscriber {
    /**
     * Encryptor interface namespace
     */

    const ENCRYPTOR_INTERFACE_NS = 'Ambta\DoctrineEncryptBundle\Encryptors\EncryptorInterface';

    /**
     * Encrypted annotation full name
     */
    const ENCRYPTED_ANN_NAME = 'Ambta\DoctrineEncryptBundle\Configuration\Encrypted';

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
     * Register to avoid multi decode operations for one entity
     * @var array
     */
    private $decodedRegistry = array();

    /**
     * Initialization of subscriber
     *
     * @param Reader $annReader
     * @param string $encryptorClass  The encryptor class.  This can be empty if a service is being provided.
     * @param string $secretKey The secret key.
     * @param EncryptorInterface|NULL $service (Optional)  An EncryptorInterface.
     *
     * This allows for the use of dependency injection for the encrypters.
     */
    public function __construct(Reader $annReader, $encryptorClass, $secretKey, EncryptorInterface $service = NULL) {
        $this->annReader = $annReader;
        if ($service instanceof EncryptorInterface) {
            $this->encryptor = $service;
        } else {
            $this->encryptor = $this->encryptorFactory($encryptorClass, $secretKey);
        }
    }

    /**
     * Listen a postUpdate lifecycle event.
     * Decrypt entity's property's values when post updated.
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
     * Encrypt entity's property's values on preUpdate, so they will be stored encrypted
     *
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args) {

        $entity = $args->getEntity();
        $this->processFields($entity);

    }

    /**
     * Listen a postLoad lifecycle event.
     * Decrypt entity's property's values when loaded into the entity manger
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args) {

        //Get entity and process fields
        $entity = $args->getEntity();
        $this->processFields($entity, false);

    }

    /**
     * Realization of EventSubscriber interface method.
     *
     * @return Array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents() {
        return array(
            Events::postUpdate,
            Events::preUpdate,
            Events::postLoad,
        );
    }


    /**
     * Process (encrypt/decrypt) entities fields
     *
     * @param Object $entity doctrine entity
     * @param Boolean $isEncryptOperation If true - encrypt, false - decrypt entity
     *
     * @throws \RuntimeException
     */
    private function processFields($entity, $isEncryptOperation = true) {

        //Check which operation to be used
        $encryptorMethod = $isEncryptOperation ? 'encrypt' : 'decrypt';

        //Get the real class, we don't want to use the proxy classes
        $realClass = \Doctrine\Common\Util\ClassUtils::getClass($entity);

        //Get ReflectionClass of our entity
        $reflectionClass = new ReflectionClass($realClass);
        $properties = $reflectionClass->getProperties();

        //Foreach property in the reflection class
        foreach ($properties as $refProperty) {

            /**
             * If followed standards, method name is getPropertyName, the propertyName is lowerCamelCase
             * So just uppercase first character of the property, later on get and set{$methodName} wil be used
             */
            $methodName = ucfirst($refProperty->getName());

            /**
             * Lazy loading, check if the property has an manyToOne relationship.
             * if it has look if the set/get exists and recursively call this function based on the entity inside. Only if not empty ofcourse
             */
            if($this->annReader->getPropertyAnnotation($refProperty, 'Doctrine\ORM\Mapping\ManyToOne')) {
                if ($reflectionClass->hasMethod($getter = 'get' . $methodName) && $reflectionClass->hasMethod($setter = 'set' . $methodName)) {
                    $entity = $entity->$getter();
                    if(!empty($entity)) {
                        $this->processFields($entity, $isEncryptOperation);
                    }
                }
            }

            /**
             * If property is an normal value and contains the Encrypt tag, lets encrypt/decrypt that property
             */
            if ($this->annReader->getPropertyAnnotation($refProperty, self::ENCRYPTED_ANN_NAME)) {

                /**
                 * If it is public lets not use the getter/setter
                 */
                if ($refProperty->isPublic()) {
                    $propName = $refProperty->getName();
                    $entity->$propName = $this->encryptor->$encryptorMethod($refProperty->getValue());
                } else {

                    //If private or protected check if there is an getter/setter for the property, based on the $methodName
                    if ($reflectionClass->hasMethod($getter = 'get' . $methodName) && $reflectionClass->hasMethod($setter = 'set' . $methodName)) {

                        //Get the information (value) of the property
                        $getInformation = $entity->$getter();

                        /**
                         * Then decrypt, encrypt the information if not empty, information is an string and the <ENC> tag is there (decrypt) or not (encrypt).
                         * The <ENC> will be added at the end of an encrypted string so it is marked as encrypted. Also protects against double encryption/decryption
                         */
                        if($encryptorMethod == "decrypt") {
                            if(!is_null($getInformation) and !empty($getInformation) and is_string($getInformation)) {
                                if(substr($entity->$getter(), -5) == "<ENC>") {
                                    $currentPropValue = $this->encryptor->$encryptorMethod(substr($entity->$getter(), 0, -5));
                                    $entity->$setter($currentPropValue);
                                }
                            }
                        } else {
                            if(!is_null($getInformation) and !empty($getInformation) and is_string($getInformation)) {
                                if(substr($entity->$getter(), -5) != "<ENC>") {
                                    $currentPropValue = $this->encryptor->$encryptorMethod($entity->$getter()) . "<ENC>";
                                    $entity->$setter($currentPropValue);
                                }
                            }
                        }

                    } else {
                        throw new \RuntimeException(sprintf("Property %s isn't public and doesn't has getter/setter"));
                    }
                }
            }
        }

    }

    /**
     * Encryptor factory. Checks and create needed encryptor
     * @param string $classFullName Encryptor namespace and name
     * @param string $secretKey Secret key for encryptor
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
