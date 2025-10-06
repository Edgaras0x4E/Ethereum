<?php

namespace Edgaras\Ethereum;

use kornrunner\Keccak;
use kornrunner\Secp256k1;
use kornrunner\Serializer\HexPrivateKeySerializer;
use Mdanter\Ecc\Curves\CurveFactory;
use Mdanter\Ecc\Curves\SecgCurve;
use Mdanter\Ecc\Math\ConstantTimeMath;


class Crypto
{
    
    public static function generatePrivateKey(): string
    {
        $bytes = random_bytes(32);
        return bin2hex($bytes);
    }
    
    
    public static function derivePublicKey(string $privateKey): string
    {
        try {
            
            if (str_starts_with($privateKey, '0x')) {
                $privateKey = substr($privateKey, 2);
            }
            
            
            $adapter = new ConstantTimeMath();
            $generator = CurveFactory::getGeneratorByName(SecgCurve::NAME_SECP_256K1);
            $deserializer = new HexPrivateKeySerializer($generator);
            
            
            $keyObject = $deserializer->parse($privateKey);
            $publicKey = $keyObject->getPublicKey();
            $point = $publicKey->getPoint();
            
            
            $x = gmp_strval($point->getX(), 16);
            $y = gmp_strval($point->getY(), 16);
            
            
            $x = str_pad($x, 64, '0', STR_PAD_LEFT);
            $y = str_pad($y, 64, '0', STR_PAD_LEFT);
            
            
            return '04' . $x . $y;
            
        } catch (\Exception $e) {
            throw new \Exception('Public key derivation failed: ' . $e->getMessage());
        }
    }
    
    
    public static function deriveAddress(string $publicKey): string
    {
        try {
            
            if (str_starts_with($publicKey, '0x')) {
                $publicKey = substr($publicKey, 2);
            }
            
            
            if (str_starts_with($publicKey, '04')) {
                $publicKey = substr($publicKey, 2); 
            }
            
            
            if (strlen($publicKey) % 2 !== 0) {
                $publicKey = '0' . $publicKey;
            }
            
            
            $publicKeyBytes = hex2bin($publicKey);
            $hash = Keccak::hash($publicKeyBytes, 256);
            
            
            $address = substr($hash, 24); 
            
            return '0x' . $address;
            
        } catch (\Exception $e) {
            throw new \Exception('Address derivation failed: ' . $e->getMessage());
        }
    }
    
    
    public static function signTransaction(string $txHash, string $privateKey): array
    {
        try {
            
            $privateKey = str_starts_with($privateKey, '0x') ? substr($privateKey, 2) : $privateKey;
            $txHash = str_starts_with($txHash, '0x') ? substr($txHash, 2) : $txHash;

            
            $secp = new Secp256k1('sha256');
            $signature = $secp->sign($txHash, $privateKey);

            
            $rHex = str_pad(gmp_strval($signature->getR(), 16), 64, '0', STR_PAD_LEFT);
            $sHex = str_pad(gmp_strval($signature->getS(), 16), 64, '0', STR_PAD_LEFT);
            $vRecId = $signature->getRecoveryParam(); 

            return [
                'r' => '0x' . $rHex,
                's' => '0x' . $sHex,
                'v' => $vRecId, 
            ];

        } catch (\Exception $e) {
            throw new \Exception('Transaction signing failed: ' . $e->getMessage());
        }
    }
    
    
    public static function signMessage(string $messageHash, string $privateKey): string
    {
        $sig = self::signTransaction($messageHash, $privateKey);
        return $sig['r'] . substr($sig['s'], 2) . substr($sig['v'], 2);
    }
    
    
    public static function keccak256(string $data): string
    {
        if (str_starts_with($data, '0x')) {
            $data = substr($data, 2);
        }
        
        
        if (strlen($data) % 2 !== 0) {
            $data = '0' . $data;
        }
        
        $bytes = hex2bin($data);
        return '0x' . Keccak::hash($bytes, 256);
    }
}