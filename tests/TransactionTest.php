<?php

namespace Edgaras\Ethereum\Tests;

use PHPUnit\Framework\TestCase;
use Edgaras\Ethereum\Transaction;
use InvalidArgumentException;

class TransactionTest extends TestCase
{
    public function testTransactionCreation(): void
    {
        $transaction = new Transaction();
        
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('0x0', $transaction->getValue());
        $this->assertEquals('0x5208', $transaction->getGas());
    }

    public function testTransactionFromArray(): void
    {
        $data = [
            'from' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
            'to' => '0x8ba1f109551bD432803012645aac136c4c4c4c40',
            'value' => '0x1',
            'gas' => '0x5208',
        ];
        
        $transaction = new Transaction($data);
        
        $this->assertEquals($data['from'], $transaction->getFrom());
        $this->assertEquals($data['to'], $transaction->getTo());
        $this->assertEquals($data['value'], $transaction->getValue());
        $this->assertEquals($data['gas'], $transaction->getGas());
    }

    public function testCreateTransfer(): void
    {
        $transaction = Transaction::createTransfer(
            '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
            '0x8ba1f109551bD432803012645aac136c4c4c4c40',
            '1000000000000000000' 
        );
        
        $this->assertEquals('0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6', $transaction->getFrom());
        $this->assertEquals('0x8ba1f109551bD432803012645aac136c4c4c4c40', $transaction->getTo());
        $this->assertEquals('0xde0b6b3a7640000', $transaction->getValue());
    }

    

    

    public function testTransactionValidation(): void
    {
        $transaction = new Transaction([
            'from' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
            'to' => '0x8ba1f109551bD432803012645aac136c4c4c4c40',
            'value' => '0x1',
        ]);
        
        $this->assertTrue($transaction->validate());
    }

    public function testTransactionValidationInvalidFrom(): void
    {
        $transaction = new Transaction([
            'from' => 'invalid-address',
            'to' => '0x8ba1f109551bD432803012645aac136c4c4c4c40',
            'value' => '0x1',
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $transaction->validate();
    }

    public function testTransactionValidationInvalidTo(): void
    {
        $transaction = new Transaction([
            'from' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
            'to' => 'invalid-address',
            'value' => '0x1',
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $transaction->validate();
    }

    public function testToHex(): void
    {
        $this->assertEquals('0x1', Transaction::toHex('1'));
        $this->assertEquals('0x1', Transaction::toHex('0x1'));
        $this->assertEquals('0xa', Transaction::toHex('10'));
    }

    public function testFromHex(): void
    {
        $this->assertEquals('1', Transaction::fromHex('0x1'));
        $this->assertEquals('10', Transaction::fromHex('0xa'));
        $this->assertEquals('255', Transaction::fromHex('0xff'));
    }

    public function testIsValidHex(): void
    {
        $this->assertTrue(Transaction::isValidHex('0x1'));
        $this->assertTrue(Transaction::isValidHex('0xabcdef'));
        $this->assertTrue(Transaction::isValidHex('1'));
        $this->assertFalse(Transaction::isValidHex('invalid'));
        $this->assertFalse(Transaction::isValidHex('0xgg'));
    }

    public function testWeiToEther(): void
    {
        if (!extension_loaded('bcmath')) {
        	$this->markTestSkipped('bcmath extension required for big number conversions.');
        }
        $this->assertEquals('1.000000000000000000', Transaction::weiToEther('0xde0b6b3a7640000'));
        $this->assertEquals('0.000000000000000001', Transaction::weiToEther('0x1'));
    }

    public function testEtherToWei(): void
    {
        if (!extension_loaded('bcmath')) {
        	$this->markTestSkipped('bcmath extension required for big number conversions.');
        }
        $this->assertEquals('1000000000000000000', Transaction::etherToWei('1'));
        $this->assertEquals('100000000000000000', Transaction::etherToWei('0.1'));
    }

    public function testGweiToWei(): void
    {
        if (!extension_loaded('bcmath')) {
        	$this->markTestSkipped('bcmath extension required for big number conversions.');
        }
        $this->assertEquals('1000000000', Transaction::gweiToWei('1'));
        $this->assertEquals('20000000000', Transaction::gweiToWei('20'));
    }

    public function testWeiToGwei(): void
    {
        if (!extension_loaded('bcmath')) {
        	$this->markTestSkipped('bcmath extension required for big number conversions.');
        }
        $this->assertEquals('1.000000000', Transaction::weiToGwei('0x3b9aca00'));
        $this->assertEquals('20.000000000', Transaction::weiToGwei('0x4a817c800'));
    }

    public function testToArray(): void
    {
        $transaction = new Transaction([
            'from' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
            'to' => '0x8ba1f109551bD432803012645aac136c4c4c4c40',
            'value' => '0x1',
        ]);
        
        $array = $transaction->toArray();
        
        $this->assertArrayHasKey('from', $array);
        $this->assertArrayHasKey('to', $array);
        $this->assertArrayHasKey('value', $array);
        $this->assertEquals('0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6', $array['from']);
        $this->assertEquals('0x8ba1f109551bD432803012645aac136c4c4c4c40', $array['to']);
        $this->assertEquals('0x1', $array['value']);
    }

    public function testGetHash(): void
    {
        $transaction = new Transaction([
            'from' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
            'to' => '0x8ba1f109551bD432803012645aac136c4c4c4c40',
            'value' => '0x1',
        ]);
        
        $hash = $transaction->getHash();
        $this->assertNotEmpty($hash);
        $this->assertIsString($hash);
    }
}
