<?php

namespace Crypt;

class Rsa
{
    private $privateKey = "file://privateKey.pem";

    private $publicKey = "file://publicKey.pem";

    public function __construct()
    {
    }

    public static function generateKeys(): void
    {
        $rsa = openssl_pkey_new([
            'digest_alg' => 'sha512',
            'private_key_bits' => '1024',
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ]);
        openssl_pkey_export($rsa, $privateKey);
        $publicKey = openssl_pkey_get_details($rsa);
        $publicKey = $publicKey['key'];
        file_put_contents('privateKey.pem', $privateKey);
        file_put_contents('publicKey.pem', $publicKey);
    }

    public function rsaPrivateEncrypt(string $origin): string
    {
        openssl_private_encrypt($origin, $secret, $this->privateKey);
        return $secret;
    }

    public function rsaPublicEncrypt(string $origin): string
    {
        openssl_public_encrypt($origin,$secret, $this->publicKey);
        return $secret;
    }

    public function rsaPrivateDecrypt(string $crypt): string
    {
        openssl_private_decrypt(base64_decode($crypt), $origin, $this->privateKey);
        return $origin;
    }

    public function rsaPublicDecrypt(string $crypt): string
    {
        openssl_public_encrypt(base64_decode($crypt), $origin, $this->publicKey);
        return $origin;
    }

    public function signByPrivateKey(string $origin): string
    {
        openssl_sign($origin, $signature, $this->privateKey);
        return base64_encode($signature);
    }

    public function verifySignByPublicKey(string $sign, string $data): bool
    {
        $verify = openssl_verify($data, base64_decode($sign), $this->publicKey);
        return (bool) $verify;
    }
}