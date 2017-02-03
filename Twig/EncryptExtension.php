<?php
namespace Combodo\DoctrineEncryptBundle\Twig;


use Combodo\DoctrineEncryptBundle\Encryptors\EncryptorInterface;


class EncryptExtension extends \Twig_Extension
{

    private $encryptor;

    public function __construct(string $encryptorClassName, string $secretKey)
    {
        /** @var EncryptorInterface encryptor */
        $this->encryptor = new $encryptorClassName($secretKey);
    }


    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('decrypt', [$this,'decrypt'], [
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