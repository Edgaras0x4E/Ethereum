<?php

namespace Edgaras\Ethereum;


class RLP
{
    
    public static function encode($data): string
    {
        if (is_array($data)) {
            return self::encodeArray($data);
        }
        
        if (is_string($data)) {
            return self::encodeString($data);
        }
        
        if (is_numeric($data)) {
            
            $hex = dechex($data);
            
            
            $byteLength = strlen($hex) / 2;
            
            if (intval($data) < 0x80) {
                
                
                if (strlen($hex) === 1) {
                    $hex = '0' . $hex;
                }
                return '0x' . $hex;
            } elseif ($byteLength <= 55) {
                
                return '0x' . sprintf('%02x', 0x80 + $byteLength) . $hex;
            } else {
                
                $lengthHex = dechex($byteLength);
                if (strlen($lengthHex) % 2 !== 0) {
                    $lengthHex = '0' . $lengthHex;
                }
                return '0x' . sprintf('%02x', 0xb7 + strlen($lengthHex)/2) . $lengthHex . $hex;
            }
        }
        
        if ($data === null || $data === '') {
            return '0x80';
        }
        
        
        return self::encodeString((string)$data);
    }
    
    
    private static function encodeString(string $data): string
    {
        
        if (str_starts_with($data, '0x')) {
            $data = substr($data, 2);
            
            
            if (strlen($data) % 2 !== 0) {
                $data = '0' . $data;
            }
        }
        
        
        if ($data === '' || $data === null) {
            return '0x80';
        }
        
        
        $isHexData = (strlen($data) % 2 === 0) && ctype_xdigit($data);
        
        if ($isHexData) {
            
            $addressLen = (strlen($data) === 40);
            $sigComponentLen = (strlen($data) === 64); 

            
            if (!$addressLen && !$sigComponentLen) {
                $data = ltrim($data, '0');
                if ($data === '') {
                    return '0x80';
                }
                
                if (strlen($data) <= 2 && hexdec($data) < 0x80) {
                    return '0x' . str_pad($data, 2, '0', STR_PAD_LEFT);
                }
            }
            
            
            if ($sigComponentLen) {
                
                if (strlen($data) < 64) {
                    $data = str_pad($data, 64, '0', STR_PAD_LEFT);
                }
            }
            
            
            if (strlen($data) % 2 !== 0) {
                $data = '0' . $data;
            }
            
            $length = strlen($data) / 2;
            
            
            if ($length === 1 && hexdec($data) < 0x80) {
                return '0x' . $data;
            }
            
            
            if ($length <= 55) {
                return '0x' . sprintf('%02x', 0x80 + $length) . $data;
            }
            
            
            $lengthHex = dechex($length);
            if (strlen($lengthHex) % 2 !== 0) {
                $lengthHex = '0' . $lengthHex;
            }
            return '0x' . sprintf('%02x', 0xb7 + strlen($lengthHex)/2) . $lengthHex . $data;
        } else {
            
            $encodedChars = '';
            for ($i = 0; $i < strlen($data); $i++) {
                $encodedChars .= sprintf('%02x', ord($data[$i]));
            }
            return self::encodeString($encodedChars);
        }
    }
    
    
    private static function encodeArray(array $items): string
    {
        $encodedData = '';
        
        foreach ($items as $item) {
            if (is_array($item)) {
                
                $encoded = self::encodeArray($item);
            } else {
                
                $prefixed = str_starts_with((string)$item, '0x') ? (string)$item : ('0x' . (string)$item);
                $encoded = self::encodeString($prefixed);
            }
            $encodedData .= substr($encoded, 2);
        }
        
        
        if (strlen($encodedData) % 2 !== 0) {
            $encodedData .= '0';
        }
        
        $totalLength = strlen($encodedData) / 2;
        
        
        if ($totalLength === 0) {
            return '0xc0';
        }
        
        
        if ($totalLength <= 55) {
            return '0x' . sprintf('%02x', 0xc0 + $totalLength) . $encodedData;
        }
        
        
        $lengthHex = dechex($totalLength);
        if (strlen($lengthHex) % 2 !== 0) {
            $lengthHex = '0' . $lengthHex;
        }
        return '0x' . sprintf('%02x', 0xf7 + strlen($lengthHex)/2) . $lengthHex . $encodedData;
    }
}