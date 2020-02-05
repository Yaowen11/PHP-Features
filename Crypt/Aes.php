<?php

namespace Crypt;

class Aes
{
    private $key;

    private $iv = "0000000000000000";

    private $cryptMethod = "AES-256-CBC";

    public function __construct(string $key)
    {
        $this->setKey($key);
    }

    /**
     * @param string $cryptMethod
     */
    public function setCryptMethod(string $cryptMethod)
    {
        $this->cryptMethod = $cryptMethod;
    }

    /**
     * @param string $iv
     */
    public function setIv(string $iv)
    {
        $this->iv = $iv;
    }

    /**
     * @param mixed $key
     */
    public function setKey($key)
    {
        $this->key = md5($key);
    }

    public function encrypt(string $origin) : string
    {
        return openssl_encrypt($origin, $this->cryptMethod, $this->key, 0, $this->iv);
    }

    public function decrypt(string $crypt): string
    {
        return openssl_decrypt($crypt, $this->cryptMethod, $this->key, 0, $this->iv);
    }

}


$aes = new Aes("123456");

echo $aes->encrypt("Hello World") . PHP_EOL; // E2HRGyN+wmaoW2SzDIt8Xg==

echo $aes->decrypt("E2HRGyN+wmaoW2SzDIt8Xg==") . PHP_EOL; // Hello World
