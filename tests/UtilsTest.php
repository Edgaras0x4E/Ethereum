<?php

namespace Edgaras\Ethereum\Tests;

use PHPUnit\Framework\TestCase;
use Edgaras\Ethereum\Utils;

class UtilsTest extends TestCase
{
    public function testWeiToEther(): void
    {
        $this->assertEquals('1.000000000000000000', Utils::weiToEther('0xde0b6b3a7640000'));
        $this->assertEquals('0.000000000000000001', Utils::weiToEther('0x1'));
        $this->assertEquals('0.500000000000000000', Utils::weiToEther('0x6f05b59d3b20000'));
    }

    public function testEtherToWei(): void
    {
        $this->assertEquals('1000000000000000000', Utils::etherToWei('1'));
        $this->assertEquals('100000000000000000', Utils::etherToWei('0.1'));
        $this->assertEquals('500000000000000000', Utils::etherToWei('0.5'));
    }

    public function testGweiToWei(): void
    {
        $this->assertEquals('1000000000', Utils::gweiToWei('1'));
        $this->assertEquals('20000000000', Utils::gweiToWei('20'));
        $this->assertEquals('5000000000', Utils::gweiToWei('5'));
    }

    public function testWeiToGwei(): void
    {
        $this->assertEquals('1.000000000', Utils::weiToGwei('0x3b9aca00'));
        $this->assertEquals('20.000000000', Utils::weiToGwei('0x4a817c800'));
        $this->assertEquals('5.000000000', Utils::weiToGwei('0x12a05f200'));
    }

    public function testToHex(): void
    {
        $this->assertEquals('0x1', Utils::toHex('1'));
        $this->assertEquals('0x1', Utils::toHex('0x1'));
        $this->assertEquals('0xa', Utils::toHex('10'));
        $this->assertEquals('0xff', Utils::toHex('255'));
    }

    public function testFromHex(): void
    {
        $this->assertEquals('1', Utils::fromHex('0x1'));
        $this->assertEquals('10', Utils::fromHex('0xa'));
        $this->assertEquals('255', Utils::fromHex('0xff'));
        $this->assertEquals('1', Utils::fromHex('1'));
    }

    public function testRemoveHexPrefix(): void
    {
        $this->assertEquals('1', Utils::removeHexPrefix('0x1'));
        $this->assertEquals('abcdef', Utils::removeHexPrefix('0xabcdef'));
        $this->assertEquals('1', Utils::removeHexPrefix('1'));
    }

    public function testAddHexPrefix(): void
    {
        $this->assertEquals('0x1', Utils::addHexPrefix('1'));
        $this->assertEquals('0xabcdef', Utils::addHexPrefix('abcdef'));
        $this->assertEquals('0x1', Utils::addHexPrefix('0x1'));
    }

    public function testIsValidHex(): void
    {
        $this->assertTrue(Utils::isValidHex('0x1'));
        $this->assertTrue(Utils::isValidHex('0xabcdef'));
        $this->assertTrue(Utils::isValidHex('1'));
        $this->assertTrue(Utils::isValidHex('abcdef'));
        $this->assertFalse(Utils::isValidHex('invalid'));
        $this->assertFalse(Utils::isValidHex('0xgg'));
        $this->assertFalse(Utils::isValidHex('gg'));
    }

    public function testIsValidAddress(): void
    {
        $this->assertTrue(Utils::isValidAddress('0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6'));
        $this->assertTrue(Utils::isValidAddress('0x0000000000000000000000000000000000000000'));
        $this->assertFalse(Utils::isValidAddress('0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b'));
        $this->assertFalse(Utils::isValidAddress('742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6'));
        $this->assertFalse(Utils::isValidAddress('invalid-address'));
    }

    public function testToChecksumAddress(): void
    {
        $address = '0x742d35cc6634c0532925a3b8d4c9db96c4b4d8b6';
        $checksum = Utils::toChecksumAddress($address);
        
        $this->assertNotEquals($address, $checksum);
        $this->assertStringStartsWith('0x', $checksum);
        $this->assertEquals(42, strlen($checksum));
    }

    public function testIsValidChecksumAddress(): void
    {
        $address = '0x742d35cc6634c0532925a3b8d4c9db96c4b4d8b6';
        $checksum = Utils::toChecksumAddress($address);
        
        $this->assertTrue(Utils::isValidChecksumAddress($checksum));
        $this->assertFalse(Utils::isValidChecksumAddress($address));
    }

    public function testRandomHex(): void
    {
        $hex1 = Utils::randomHex(32);
        $hex2 = Utils::randomHex(32);
        
        $this->assertStringStartsWith('0x', $hex1);
        $this->assertStringStartsWith('0x', $hex2);
        $this->assertEquals(66, strlen($hex1)); 
        $this->assertEquals(66, strlen($hex2));
        $this->assertNotEquals($hex1, $hex2);
    }

    public function testRandomPrivateKey(): void
    {
        $key1 = Utils::randomPrivateKey();
        $key2 = Utils::randomPrivateKey();
        
        $this->assertStringStartsWith('0x', $key1);
        $this->assertStringStartsWith('0x', $key2);
        $this->assertEquals(66, strlen($key1)); 
        $this->assertEquals(66, strlen($key2));
        $this->assertNotEquals($key1, $key2);
    }

    public function testKeccak256(): void
    {
        $hash = Utils::keccak256('hello world');
        
        $this->assertStringStartsWith('0x', $hash);
        $this->assertEquals(66, strlen($hash)); 
    }

    public function testSha3(): void
    {
        $hash = Utils::sha3('hello world');
        
        $this->assertStringStartsWith('0x', $hash);
        $this->assertEquals(66, strlen($hash)); 
    }

    public function testFormatBlockIdentifier(): void
    {
        $this->assertEquals('latest', Utils::formatBlockIdentifier('latest'));
        $this->assertEquals('earliest', Utils::formatBlockIdentifier('earliest'));
        $this->assertEquals('pending', Utils::formatBlockIdentifier('pending'));
        $this->assertEquals('0x123', Utils::formatBlockIdentifier('0x123'));
        $this->assertEquals('0x1', Utils::formatBlockIdentifier('1'));
    }

    public function testParseBlockIdentifier(): void
    {
        $this->assertEquals('latest', Utils::parseBlockIdentifier('latest'));
        $this->assertEquals('earliest', Utils::parseBlockIdentifier('earliest'));
        $this->assertEquals('pending', Utils::parseBlockIdentifier('pending'));
        $this->assertEquals(291, Utils::parseBlockIdentifier('0x123'));
        $this->assertEquals(1, Utils::parseBlockIdentifier('1'));
    }

    public function testBytesToHex(): void
    {
        $this->assertEquals('0x68656c6c6f', Utils::bytesToHex('hello'));
        $this->assertEquals('0x', Utils::bytesToHex(''));
    }

    public function testHexToBytes(): void
    {
        $this->assertEquals('hello', Utils::hexToBytes('0x68656c6c6f'));
        $this->assertEquals('', Utils::hexToBytes('0x'));
    }

    public function testPadHex(): void
    {
        $this->assertEquals('0x0000000000000000000000000000000000000000000000000000000000000001', Utils::padHex('0x1', 64));
        $this->assertEquals('0x0000000000000000000000000000000000000000000000000000000000000001', Utils::padHex('1', 64));
    }

    public function testStripLeadingZeros(): void
    {
        $this->assertEquals('0x1', Utils::stripLeadingZeros('0x0000000000000000000000000000000000000000000000000000000000000001'));
        $this->assertEquals('0x0', Utils::stripLeadingZeros('0x0000000000000000000000000000000000000000000000000000000000000000'));
    }

    public function testCompareHex(): void
    {
        $this->assertEquals(0, Utils::compareHex('0x1', '0x1'));
        $this->assertEquals(1, Utils::compareHex('0x2', '0x1'));
        $this->assertEquals(-1, Utils::compareHex('0x1', '0x2'));
    }

    public function testAddHex(): void
    {
        $this->assertEquals('0x2', Utils::addHex('0x1', '0x1'));
        $this->assertEquals('0xa', Utils::addHex('0x5', '0x5'));
    }

    public function testSubHex(): void
    {
        $this->assertEquals('0x0', Utils::subHex('0x1', '0x1'));
        $this->assertEquals('0x1', Utils::subHex('0x2', '0x1'));
    }

    public function testMulHex(): void
    {
        $this->assertEquals('0x1', Utils::mulHex('0x1', '0x1'));
        $this->assertEquals('0x19', Utils::mulHex('0x5', '0x5'));
    }

    public function testDivHex(): void
    {
        $this->assertEquals('0x1', Utils::divHex('0x2', '0x2'));
        $this->assertEquals('0x2', Utils::divHex('0xa', '0x5'));
    }

    public function testDivHexByZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Utils::divHex('0x1', '0x0');
    }

    public function testCurrentTimestampToHex(): void
    {
        $hex = Utils::currentTimestampToHex();
        $this->assertStringStartsWith('0x', $hex);
        $this->assertIsString($hex);
    }

    public function testTimestampToHexWithValue(): void
    {
        $timestamp = 1640995200; 
        $hex = Utils::timestampToHex($timestamp);
        $this->assertEquals('0x' . dechex($timestamp), $hex);
    }

    public function testHexToTimestamp(): void
    {
        $timestamp = 1640995200; 
        $hex = '0x' . dechex($timestamp);
        $this->assertEquals($timestamp, Utils::hexToTimestamp($hex));
    }

    public function testFormatNumber(): void
    {
        $this->assertEquals('1.000000000000000000', Utils::formatNumber('1', 18));
        $this->assertEquals('1.50', Utils::formatNumber('1.5', 2));
        $this->assertEquals('123.456789', Utils::formatNumber('123.456789', 6));
    }

    public function testScientificToDecimal(): void
    {
        $this->assertEquals('1.000000000000000000', Utils::scientificToDecimal('1e0'));
        $this->assertEquals('1000000000000000000.000000000000000000', Utils::scientificToDecimal('1e18'));
        $this->assertEquals('1', Utils::scientificToDecimal('1'));
    }
}
