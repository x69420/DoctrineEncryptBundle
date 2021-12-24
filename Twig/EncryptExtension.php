<?php

namespace Combodo\DoctrineEncryptBundle\Twig;

use Combodo\DoctrineEncryptBundle\Services\EncryptorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class EncryptExtension extends AbstractExtension
{

    private $encryptor;

    public function __construct(EncryptorInterface $encryptor)
    {
        $this->encryptor = $encryptor;
    }


    public function getFilters()
    {
        return [
            new TwigFilter('decrypt', [$this,'decrypt'], [
                //'is_safe' => ['html'],//to avoid autoescaping of HTML
            ]),
        ];
    }


    public function decrypt(string $encryptedData)
    {
        if(substr($encryptedData, -5) != "<ENC>") {
            return $encryptedData;
        }

        return $this->encryptor->decrypt($encryptedData);
    }

    public function getName()
    {
        return 'combodo_encrypt_twig_extensions';
    }
}