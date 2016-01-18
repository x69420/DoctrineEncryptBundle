<?php

namespace Ambta\DoctrineEncryptBundle\Command;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Hello World command for demo purposes.
 *
 * You could also extend from Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand
 * to get access to the container via $this->getContainer().
 *
 * @author Marcel van Nuil <marcel@ambta.com>
 */
class DoctrineEncryptStatusCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctrine:encrypt:status')
            ->setDescription('Get status of doctrine encrypt bundle and the database');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $annotationReader = new AnnotationReader();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $metaDataArray = $em->getMetadataFactory()->getAllMetadata();

        $totalCount = 0;
        foreach($metaDataArray as $metaData) {
            if ($metaData->isMappedSuperclass) {
                continue;
            }

            $reflectionClass = New \ReflectionClass($metaData->name);
            $propertyArray = $reflectionClass->getProperties();
            $count = 0;

            foreach($propertyArray as $property) {
                if($annotationReader->getPropertyAnnotation($property, "Ambta\DoctrineEncryptBundle\Configuration\Encrypted")) {
                    $count++;
                    $totalCount++;
                }
            }

            $output->writeln($metaData->name . " has " . $count . " properties which are encrypted.");
        }

        $output->writeln("");
        $output->writeln(count($metaDataArray) . " entities found which are containing " . $totalCount . " encrypted properties.");
    }
}
