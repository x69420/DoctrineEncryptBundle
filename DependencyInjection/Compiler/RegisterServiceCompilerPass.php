<?php

namespace Ambta\DoctrineEncryptBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * The RegisterServiceCompilerPass class
 *
 * @author wpigott
 */
class RegisterServiceCompilerPass implements CompilerPassInterface {

    /**
     * can modify the container here before dumped to PHP code
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container) {
        //Nothing here
    }
}

?>
