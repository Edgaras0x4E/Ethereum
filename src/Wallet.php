<?php

namespace Edgaras\Ethereum;

use InvalidArgumentException;
use Exception;

class Wallet
{
    private string $privateKey;
    private string $publicKey;
    private string $address;

    public function __construct(?string $privateKey = null)
    {
        if ($privateKey === null) {
            $this->generateNewWallet();
        } else {
            $this->importPrivateKey($privateKey);
        }
    }

    private function generateNewWallet(): void
    {
        $this->privateKey = $this->generateRandomPrivateKey();
        $this->deriveKeysFromPrivateKey();
    }

    private function importPrivateKey(string $privateKey): void
    {
        $privateKey = $this->normalizePrivateKey($privateKey);
        
        if (!$this->isValidPrivateKey($privateKey)) {
            throw new InvalidArgumentException('Invalid private key format');
        }

        $this->privateKey = $privateKey;
        $this->deriveKeysFromPrivateKey();
    }

    private function generateRandomPrivateKey(): string
    {
        $bytes = random_bytes(32);
        return bin2hex($bytes);
    }

    private function normalizePrivateKey(string $privateKey): string
    {
        if (str_starts_with($privateKey, '0x')) {
            return substr($privateKey, 2);
        }
        return $privateKey;
    }

    private function isValidPrivateKey(string $privateKey): bool
    {
        return strlen($privateKey) === 64 && ctype_xdigit($privateKey);
    }

    private function deriveKeysFromPrivateKey(): void
    {
        
        $this->publicKey = $this->derivePublicKey($this->privateKey);
        $this->address = $this->deriveAddress($this->publicKey);
    }

    private function derivePublicKey(string $privateKey): string
    {
        
        return Crypto::derivePublicKey($privateKey);
    }

    private function deriveAddress(string $publicKey): string
    {
        
        return Crypto::deriveAddress($publicKey);
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getPrivateKeyHex(): string
    {
        return '0x' . $this->privateKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function exportToJson(): string
    {
        $data = [
            'privateKey' => $this->getPrivateKeyHex(),
            'publicKey' => $this->publicKey,
            'address' => $this->address,
            'created' => date('c'),
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public static function importFromJson(string $jsonData): self
    {
        $data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON format');
        }

        if (!isset($data['privateKey'])) {
            throw new InvalidArgumentException('Private key not found in JSON data');
        }

        return new self($data['privateKey']);
    }

    public function signMessage(string $messageHash): string
    {
        
        return Crypto::signMessage($messageHash, $this->privateKey);
    }

    public function signTransaction(array $transaction): string
    {
        $txData = json_encode($transaction);
        
        return Crypto::signMessage(hash('sha256', $txData), $this->privateKey);
    }

    public static function fromMnemonic(string $mnemonic, string $passphrase = ''): self
    {
        
        throw new Exception('Mnemonic wallet creation not yet implemented. Use private key instead.');
    }

    public static function generateMnemonic(int $wordCount = 12): string
    {
        
        throw new Exception('Mnemonic generation not yet implemented.');
    }

    public static function isValidAddress(string $address): bool
    {
        if (!str_starts_with($address, '0x')) {
            return false;
        }

        $address = substr($address, 2);
        
        if (strlen($address) !== 40) {
            return false;
        }

        return ctype_xdigit($address);
    }

    public static function toChecksumAddress(string $address): string
    {
        $address = strtolower($address);
        
        if (!str_starts_with($address, '0x')) {
            $address = '0x' . $address;
        }

        $addressOnly = substr($address, 2);
        
        
        $hash = Crypto::keccak256($addressOnly);
        $hashWithoutPrefix = substr($hash, 2); 
        
        $checksumAddress = '0x';
        
        for ($i = 0; $i < strlen($addressOnly); $i++) {
            if (ctype_digit($addressOnly[$i])) {
                $checksumAddress .= $addressOnly[$i];
            } else {
                $hashValue = hexdec($hashWithoutPrefix[$i]);
                $checksumAddress .= ($hashValue >= 8) ? strtoupper($addressOnly[$i]) : strtolower($addressOnly[$i]);
            }
        }

        return $checksumAddress;
    }
    
    public static function isValidChecksumAddress(string $address): bool
    {
        return self::toChecksumAddress($address) === $address;
    }
}
