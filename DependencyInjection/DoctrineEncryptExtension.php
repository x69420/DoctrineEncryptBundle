<?php

namespace Ambta\DoctrineEncryptBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Initialization of bundle.
 *
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class DoctrineEncryptExtension extends Extension {

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container) {

        //Create configuration object
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        //Set orm-service in array of services
        $services = array('orm' => 'orm-services');

        //set supported encryptor classes
        $supportedEncryptorClasses = array('aes256' => 'Ambta\DoctrineEncryptBundle\Encryptors\AES256Encryptor');

        //If no secret key is set, check for framework secret, otherwise throw exception
        if (empty($config['secret_key'])) {
            if ($container->hasParameter('secret')) {
                $config['secret_key'] = $container->getParameter('secret');
            } else {
                throw new \RuntimeException('You must provide "secret_key" for DoctrineEncryptBundle or "secret" for framework');
            }
        }

        //If empty encryptor class, use AES256 encryptor
        if (empty($config['encryptor_class'])) {
            $config['encryptor_class'] = $supportedEncryptorClasses['aes256'];
        }

        //Set parameters
        $container->setParameter('ambta_doctrine_encrypt.encryptor_class_name', $config['encryptor_class']);
        $container->setParameter('ambta_doctrine_encrypt.secret_key', $config['secret_key']);

        //Load service file
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load(sprintf('%s.yml', $services['orm']));
    }

    /**
     * Get alias for configuration
     *
     * @return string
     */
    public function getAlias() {
        return 'ambta_doctrine_encrypt';
    }
}
