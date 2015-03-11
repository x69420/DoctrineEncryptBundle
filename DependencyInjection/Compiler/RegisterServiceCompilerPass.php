<?php

namespace Ambta\DoctrineEncryptBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Description of RegisterServiceCompilerPass
 *
 * @author wpigott
 */
class RegisterServiceCompilerPass implements CompilerPassInterface {

    public function process(ContainerBuilder $container) {
        //Nothing here
    }


}

?>
