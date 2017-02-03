<?php
/**
 * Created by PhpStorm.
 * User: bdasilva
 * Date: 02/02/17
 * Time: 14:03
 */

namespace Combodo\DoctrineEncryptBundle\Encryptors;

use Combodo\DoctrineEncryptBundle\Security\SensitiveValue;

class AES256OpenSslEncryptor implements EncryptorInterface
{
    const METHOD = 'aes-256-cbc';

    /** @var SensitiveValue $secretKey Secret key for aes algorythm */
    private $secretKey;

    /**
     * Must accept secret key for encryption
     * @param string|SensitiveValue $secretKey the encryption key
     */
    public function __construct($secretKey)
    {
        if ($secretKey instanceof SensitiveValue) {
            $this->secretKey = $secretKey;
        } else {
            $this->secretKey = new SensitiveValue($secretKey);
        }

    }

    /**
     * @param string $data Plain text to encrypt
     * @return string Encrypted text
     */
    public function encrypt($data)
    {
        if (is_null($data)) { return $data; }
        $keyLengthOctet = mb_strlen($this->secretKey->peek(), '8bit');
        if ($keyLengthOctet !== 32) {
            throw new \Exception("Needs a 256-bit key, '".($keyLengthOctet * 8)."'bit given");
        }

        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv     = openssl_random_pseudo_bytes($ivsize);

        $ciphertext = openssl_encrypt(
            $data,
            self::METHOD,
            $this->secretKey->peek(),
            OPENSSL_RAW_DATA,
            $iv
        );

        return $iv . $ciphertext;
    }

    /**
     * @param string $data Encrypted text
     * @return string Plain text
     */
    public function decrypt($data)
    {
        if (is_null($data)) { return $data; }
        $keyLengthOctet = mb_strlen($this->secretKey->peek(), '8bit');
        if ($keyLengthOctet !== 32) {
            throw new \Exception("Needs a 256-bit key, '".($keyLengthOctet * 8)."'bit given");
        }

        $ivsize         = openssl_cipher_iv_length(self::METHOD);

        $iv             = mb_substr($data, 0        , $ivsize   , '8bit');
        $ciphertext     = mb_substr($data, $ivsize  , null      , '8bit');

        return openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $this->secretKey->peek(),
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}