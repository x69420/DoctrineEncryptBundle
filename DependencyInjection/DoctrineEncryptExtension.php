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

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $services = array('orm' => 'orm-services');
        $supportedEncryptorClasses = array('aes256' => 'Ambta\DoctrineEncryptBundle\Encryptors\AES256Encryptor');

        if (empty($config['secret_key'])) {
            if ($container->hasParameter('secret')) {
                $config['secret_key'] = $container->getParameter('secret');
            } else {
                throw new \RuntimeException('You must provide "secret_key" for DoctrineEncryptBundle or "secret" for framework');
            }
        }

        if (!empty($config['encryptor_class'])) {
            $encryptorFullName = $config['encryptor_class'];
        } else {
            $encryptorFullName = $supportedEncryptorClasses[$config['encryptor']];
        }

        $container->setParameter('ambta_doctrine_encrypt.encryptor_class_name', $encryptorFullName);
        $container->setParameter('ambta_doctrine_encrypt.secret_key', $config['secret_key']);

        if (!empty($config['encryptor_service'])) {
            $container->setParameter('ambta_doctrine_encrypt.encryptor_service', $config['encryptor_service']);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load(sprintf('%s.yml', $services[$config['db_driver']]));
    }


    public function getAlias() {
        return 'ambta_doctrine_encrypt';
    }

}
