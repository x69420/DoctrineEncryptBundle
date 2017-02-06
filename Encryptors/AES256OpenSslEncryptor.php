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
            throw new \Exception("Needs a 256-bit key, '".($keyLengthOctet * 8)."'bit given!");
        }

        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv     = openssl_random_pseudo_bytes($ivsize);

        $length = strlen($data);
        $dataAndMetadata = json_encode(['data'=>$data, 'length'=>$length]);

        $ciphertext = openssl_encrypt(
            $dataAndMetadata,
            self::METHOD,
            $this->secretKey->peek(),
            OPENSSL_RAW_DATA,
            $iv
        );

        $storableData = base64_encode($iv . $ciphertext).'<ENC>';


        //$decrypted = $this->decrypt($storableData);


        return $storableData;
    }

    /**
     * @param string $data Encrypted text
     * @return string Plain text
     */
    public function decrypt($dataBase64)
    {
        if (is_null($dataBase64)) { return $dataBase64; }
        $keyLengthOctet = mb_strlen($this->secretKey->peek(), '8bit');
        if ($keyLengthOctet !== 32) {
            throw new \Exception("Needs a 256-bit key, '".($keyLengthOctet * 8)."'bit given");
        }

        $ivsize         = openssl_cipher_iv_length(self::METHOD);

        //remove the <ENC>
        if ('<ENC>' == substr($dataBase64, -5)) {
            $dataBase64 = substr($dataBase64, 0, -5);
        }

        $data           = base64_decode($dataBase64);

        $iv             = mb_substr($data, 0        , $ivsize   , '8bit');
        $ciphertext     = mb_substr($data, $ivsize  , null      , '8bit');


        $dataAndMetadataStr = openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $this->secretKey->peek(),
            OPENSSL_RAW_DATA,
            $iv
        );

        if (null == $dataAndMetadataStr) {
            throw new \Exception('decrypt operation failed, $ciphertext : "'.$ciphertext.'", $iv: "'.$iv.'", $data : "'.$data.'", $dataBase64: "'.$dataBase64.'"');
        }

        $dataAndMetadata    = json_decode($dataAndMetadataStr);
        if (null == $dataAndMetadata) {
            throw new \Exception('decrypt operation failed, the decrypted structure of "'.$data.'" is not properly formed! (json expected, null obtained)');
        }
        $length             = strlen($dataAndMetadata->data);


        if ($dataAndMetadata->length != $length) {
            throw new \Exception('Integrity check failed on crypted data, should be "'.$dataAndMetadata->length.'" chars length, "'.$length.'" chars obtained!');
        }


        return $dataAndMetadata->data;
    }
}