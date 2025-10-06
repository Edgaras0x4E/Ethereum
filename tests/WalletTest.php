<?php

namespace Edgaras\Ethereum\Tests;

use PHPUnit\Framework\TestCase;
use Edgaras\Ethereum\Wallet;
use InvalidArgumentException;

class WalletTest extends TestCase
{
    public function testWalletCreation(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $wallet = new Wallet();
        
        $this->assertNotEmpty($wallet->getPrivateKey());
        $this->assertNotEmpty($wallet->getPublicKey());
        $this->assertNotEmpty($wallet->getAddress());
        $this->assertStringStartsWith('0x', $wallet->getAddress());
        $this->assertEquals(42, strlen($wallet->getAddress()));
    }

    public function testWalletFromPrivateKey(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $privateKey = '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';
        $wallet = new Wallet($privateKey);
        
        $this->assertEquals($privateKey, $wallet->getPrivateKey());
        $this->assertNotEmpty($wallet->getPublicKey());
        $this->assertNotEmpty($wallet->getAddress());
    }

    public function testWalletFromPrivateKeyWithPrefix(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $privateKey = '0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';
        $wallet = new Wallet($privateKey);
        
        $this->assertEquals('1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef', $wallet->getPrivateKey());
    }

    public function testInvalidPrivateKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Wallet('invalid-private-key');
    }

    public function testWalletExportImport(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $wallet1 = new Wallet();
        $jsonData = $wallet1->exportToJson();
        
        $wallet2 = Wallet::importFromJson($jsonData);
        
        $this->assertEquals($wallet1->getPrivateKey(), $wallet2->getPrivateKey());
        $this->assertEquals($wallet1->getAddress(), $wallet2->getAddress());
    }

    public function testInvalidJsonImport(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Wallet::importFromJson('invalid-json');
    }

    public function testAddressValidation(): void
    {
        $this->assertTrue(Wallet::isValidAddress('0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6'));
        $this->assertFalse(Wallet::isValidAddress('0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b'));
        $this->assertFalse(Wallet::isValidAddress('742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6'));
        $this->assertFalse(Wallet::isValidAddress('invalid-address'));
    }

    public function testChecksumAddress(): void
    {
        $address = '0x742d35cc6634c0532925a3b8d4c9db96c4b4d8b6';
        $checksum = Wallet::toChecksumAddress($address);
        
        $this->assertNotEquals($address, $checksum);
        $this->assertStringStartsWith('0x', $checksum);
        $this->assertEquals(42, strlen($checksum));
    }

    public function testSignMessage(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $wallet = new Wallet();
        $messageHash = '0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';
        $signature = $wallet->signMessage($messageHash);
        
        $this->assertStringStartsWith('0x', $signature);
        $this->assertNotEmpty($signature);
    }

    public function testSignTransaction(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $wallet = new Wallet();
        $transaction = ['to' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6', 'value' => '0x1'];
        $signature = $wallet->signTransaction($transaction);
        
        $this->assertStringStartsWith('0x', $signature);
        $this->assertNotEmpty($signature);
    }
}
