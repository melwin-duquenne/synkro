<?php
// backend/src/Service/EncryptionService.php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EncryptionService
{
    private string $key;

    public function __construct(
        #[Autowire('%env(APP_SECRET)%')]
        string $encryptionKey
    ) {
        // Dériver une clé 32 bytes depuis APP_SECRET
        $this->key = substr(hash('sha256', $encryptionKey, true), 0, 32);
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($plaintext, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $ciphertext): string
    {
        $data = base64_decode($ciphertext);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $result = openssl_decrypt($encrypted, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $iv);
        if ($result === false) {
            throw new \RuntimeException('Impossible de déchiffrer la clé API');
        }
        return $result;
    }
}
