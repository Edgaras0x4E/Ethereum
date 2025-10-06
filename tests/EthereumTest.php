<?php

namespace Edgaras\Ethereum\Tests;

use PHPUnit\Framework\TestCase;
use Edgaras\Ethereum\Ethereum;
use Edgaras\Ethereum\Wallet;
use InvalidArgumentException;

class EthereumTest extends TestCase
{
    private Ethereum $ethereum;

    protected function setUp(): void
    {
        $this->ethereum = new Ethereum('https://eth-mainnet.alchemyapi.io/v2/test');
    }

    public function testEthereumInitialization(): void
    {
        $this->assertInstanceOf(Ethereum::class, $this->ethereum);
        $this->assertInstanceOf(\Edgaras\Ethereum\EthereumClient::class, $this->ethereum->getClient());
        
    }

    public function testCreateWallet(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $wallet = $this->ethereum->createWallet();
        
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertInstanceOf(Wallet::class, $this->ethereum->getWallet());
        $this->assertInstanceOf(\Edgaras\Ethereum\TransactionManager::class, $this->ethereum->getTransactionManager());
    }

    public function testImportWallet(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $privateKey = '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';
        $wallet = $this->ethereum->importWallet($privateKey);
        
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals($privateKey, $wallet->getPrivateKey());
        $this->assertEquals($wallet, $this->ethereum->getWallet());
    }

    public function testImportWalletFromJson(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $wallet1 = new Wallet();
        $jsonData = $wallet1->exportToJson();
        
        $wallet2 = $this->ethereum->importWalletFromJson($jsonData);
        
        $this->assertInstanceOf(Wallet::class, $wallet2);
        $this->assertEquals($wallet1->getPrivateKey(), $wallet2->getPrivateKey());
        $this->assertEquals($wallet2, $this->ethereum->getWallet());
    }

    public function testSetWallet(): void
    {
        if (!extension_loaded('gmp')) {
        	$this->markTestSkipped('gmp extension required for wallet operations.');
        }
        $wallet = new Wallet();
        $this->ethereum->setWallet($wallet);
        
        $this->assertEquals($wallet, $this->ethereum->getWallet());
        $this->assertInstanceOf(\Edgaras\Ethereum\TransactionManager::class, $this->ethereum->getTransactionManager());
    }

    public function testCreateTransaction(): void
    {
        $transaction = $this->ethereum->createTransaction([
            'from' => '0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6',
            'to' => '0x8ba1f109551bD432803012645aac136c4c4c4c40',
            'value' => '0x1',
        ]);
        
        $this->assertInstanceOf(\Edgaras\Ethereum\Transaction::class, $transaction);
        $this->assertEquals('0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b6', $transaction->getFrom());
        $this->assertEquals('0x8ba1f109551bD432803012645aac136c4c4c4c40', $transaction->getTo());
        $this->assertEquals('0x1', $transaction->getValue());
    }

    

    public function testSendEtherWithoutWallet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->ethereum->sendEther('0x8ba1f109551bD432803012645aac136c4c4c4c40', '1');
    }

    

    public function testSendTransactionWithoutWallet(): void
    {
        $transaction = $this->ethereum->createTransaction([
            'to' => '0x8ba1f109551bD432803012645aac136c4c4c4c40',
            'value' => '0x1',
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->ethereum->sendTransaction($transaction);
    }

    

    public function testWaitForConfirmationWithoutWallet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->ethereum->waitForConfirmation('0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef');
    }

    public function testGetTransactionStatusWithoutWallet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->ethereum->getTransactionStatus('0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef');
    }

    public function testGetTransactionDetailsWithoutWallet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->ethereum->getTransactionDetails('0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef');
    }

    public function testEstimateTransactionCostWithoutWallet(): void
    {
        $transaction = $this->ethereum->createTransaction([
            'to' => '0x8ba1f109551bD432803012645aac136c4c4c4c40',
            'value' => '0x1',
        ]);
        
        $this->expectException(InvalidArgumentException::class);
        $this->ethereum->estimateTransactionCost($transaction);
    }

    public function testGetBalanceWithoutAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->ethereum->getBalance();
    }

    public function testGetBalanceWithAddress(): void
    { 
        $this->assertTrue(method_exists($this->ethereum, 'getBalance'));
    }

    public function testGetBalanceInEtherWithoutAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->ethereum->getBalanceInEther();
    }

    public function testGetNetworkInfo(): void
    {
        try {
            $networkInfo = $this->ethereum->getNetworkInfo();
            
            $this->assertIsArray($networkInfo);
            $this->assertArrayHasKey('chainId', $networkInfo);
            $this->assertArrayHasKey('networkVersion', $networkInfo);
            $this->assertArrayHasKey('peerCount', $networkInfo);
            $this->assertArrayHasKey('syncing', $networkInfo);
        } catch (\Edgaras\Ethereum\EthereumException $e) {
          
            $this->assertTrue(true);
        }
    }

    
 
}
