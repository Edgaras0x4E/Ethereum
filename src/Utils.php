<?php

namespace Edgaras\Ethereum;

class Utils
{
    public static function weiToEther(string $wei): string
    {
        $wei = self::removeHexPrefix($wei);
        if (empty($wei) || $wei === '0') {
            return '0.000000000000000000';
        }
        
        if (ctype_digit($wei)) {
            return self::bigIntDiv($wei, '1000000000000000000', 18);
        }
        
        $weiDecimal = hexdec($wei);
        return self::bigIntDiv((string)$weiDecimal, '1000000000000000000', 18);
    }

    public static function etherToWei(string $ether): string
    {
        return self::bigIntMul($ether, '1000000000000000000');
    }

    public static function gweiToWei(string $gwei): string
    {
        return self::bigIntMul($gwei, '1000000000');
    }

    public static function weiToGwei(string $wei): string
    {
        $wei = self::removeHexPrefix($wei);
        if (empty($wei) || $wei === '0') {
            return '0.000000000';
        }
        
        if (ctype_digit($wei)) {
            return self::bigIntDiv($wei, '1000000000', 9);
        }
        
        $weiDecimal = hexdec($wei);
        return self::bigIntDiv((string)$weiDecimal, '1000000000', 9);
    }

    
    private static function bigIntDiv(string $dividend, string $divisor, int $precision = 0): string
    {
        
        $dividendFloat = floatval($dividend);
        $divisorFloat = floatval($divisor);
        
        if ($divisorFloat == 0) {
            throw new \InvalidArgumentException('Division by zero');
        }
        
        $result = $dividendFloat / $divisorFloat;
        
        if ($precision > 0) {
            return number_format($result, $precision, '.', '');
        }
        
        return (string)$result;
    }

    
    private static function bigIntMul(string $multiplicand, string $multiplier): string
    {
        
        $multiplicandFloat = floatval($multiplicand);
        $multiplierFloat = floatval($multiplier);
        
        $result = $multiplicandFloat * $multiplierFloat;
        
        
        return number_format($result, 0, '.', '');
    }

    public static function toHex(string $value): string
    {
        if (str_starts_with($value, '0x')) {
            return $value;
        }
        return '0x' . dechex($value);
    }

    public static function fromHex(string $hex): string
    {
        $hex = self::removeHexPrefix($hex);
        return hexdec($hex);
    }

    public static function removeHexPrefix(string $hex): string
    {
        if (str_starts_with($hex, '0x')) {
            return substr($hex, 2);
        }
        return $hex;
    }

    public static function addHexPrefix(string $hex): string
    {
        if (str_starts_with($hex, '0x')) {
            return $hex;
        }
        return '0x' . $hex;
    }

    public static function isValidHex(string $value): bool
    {
        $value = self::removeHexPrefix($value);
        return ctype_xdigit($value);
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

    public static function randomHex(int $length): string
    {
        $bytes = random_bytes($length);
        return '0x' . bin2hex($bytes);
    }

    public static function randomPrivateKey(): string
    {
        return self::randomHex(32);
    }

    public static function keccak256(string $data): string
    {
        return '0x' . hash('sha3-256', $data);
    }

    public static function sha3(string $data): string
    {
        return '0x' . hash('sha3-256', $data);
    }

    public static function formatBlockIdentifier(string $blockIdentifier): string
    {
        if (in_array($blockIdentifier, ['latest', 'earliest', 'pending'])) {
            return $blockIdentifier;
        }

        if (str_starts_with($blockIdentifier, '0x')) {
            return $blockIdentifier;
        }

        return '0x' . dechex($blockIdentifier);
    }

    public static function parseBlockIdentifier(string $blockIdentifier): int|string
    {
        if (in_array($blockIdentifier, ['latest', 'earliest', 'pending'])) {
            return $blockIdentifier;
        }

        if (str_starts_with($blockIdentifier, '0x')) {
            return hexdec($blockIdentifier);
        }

        return (int) $blockIdentifier;
    }

    public static function bytesToHex(string $bytes): string
    {
        return '0x' . bin2hex($bytes);
    }

    public static function hexToBytes(string $hex): string
    {
        $hex = self::removeHexPrefix($hex);
        return hex2bin($hex);
    }

    public static function padHex(string $hex, int $length, string $padChar = '0'): string
    {
        $hex = self::removeHexPrefix($hex);
        return '0x' . str_pad($hex, $length, $padChar, STR_PAD_LEFT);
    }

    public static function stripLeadingZeros(string $hex): string
    {
        $hex = self::removeHexPrefix($hex);
        $hex = ltrim($hex, '0');
        return '0x' . ($hex ?: '0');
    }

    public static function compareHex(string $hex1, string $hex2): int
    {
        $val1 = self::fromHex($hex1);
        $val2 = self::fromHex($hex2);
        
        return $val1 <=> $val2;
    }

    public static function addHex(string $hex1, string $hex2): string
    {
        $val1 = self::fromHex($hex1);
        $val2 = self::fromHex($hex2);
        
        return '0x' . dechex($val1 + $val2);
    }

    public static function subHex(string $hex1, string $hex2): string
    {
        $val1 = self::fromHex($hex1);
        $val2 = self::fromHex($hex2);
        
        return '0x' . dechex($val1 - $val2);
    }

    public static function mulHex(string $hex1, string $hex2): string
    {
        $val1 = self::fromHex($hex1);
        $val2 = self::fromHex($hex2);
        
        return '0x' . dechex($val1 * $val2);
    }

    public static function divHex(string $hex1, string $hex2): string
    {
        $val1 = self::fromHex($hex1);
        $val2 = self::fromHex($hex2);
        
        if ($val2 == 0) {
            throw new \InvalidArgumentException('Division by zero');
        }
        
        return '0x' . dechex(intval($val1 / $val2));
    }

    public static function currentTimestampToHex(): string
    {
        return '0x' . dechex(time());
    }

    public static function timestampToHex(int $timestamp): string
    {
        return '0x' . dechex($timestamp);
    }

    public static function hexToTimestamp(string $hex): int
    {
        return hexdec(self::removeHexPrefix($hex));
    }

    public static function formatNumber(string $number, int $decimals = 18): string
    {
        return number_format($number, $decimals, '.', '');
    }

    public static function scientificToDecimal(string $number): string
    {
        if (strpos($number, 'e') !== false || strpos($number, 'E') !== false) {
            return sprintf('%.18f', floatval($number));
        }
        return $number;
    }
}
