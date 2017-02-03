<?php
/**
 * Created by PhpStorm.
 * User: bdasilva
 * Date: 03/02/17
 * Time: 11:45
 */
namespace Combodo\DoctrineEncryptBundle\Services;

interface EncryptorInterface
{
    public function getEncryptor();

    public function decrypt($string);

    public function encrypt($string);
}