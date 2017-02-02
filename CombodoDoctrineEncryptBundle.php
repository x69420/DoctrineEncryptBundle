<?php

namespace Combodo\DoctrineEncryptBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Combodo\DoctrineEncryptBundle\DependencyInjection\DoctrineEncryptExtension;
use Combodo\DoctrineEncryptBundle\DependencyInjection\Compiler\RegisterServiceCompilerPass;

class CombodoDoctrineEncryptBundle extends Bundle {
    
    public function build(ContainerBuilder $container) {
        parent::build($container);
        $container->addCompilerPass(new RegisterServiceCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
    
    public function getContainerExtension()
    {
        return new DoctrineEncryptExtension();
    }
}
