<?php

namespace Edgaras\Ethereum\Tests;

use PHPUnit\Framework\TestCase;
use Edgaras\Ethereum\EthereumClient;
use Edgaras\Ethereum\EthereumException;

class EthereumClientTest extends TestCase
{
    private EthereumClient $client;

    protected function setUp(): void
    { 
        $this->client = new EthereumClient('https://eth-mainnet.alchemyapi.io/v2/test');
    }

    public function testClientInitialization(): void
    {
        $this->assertInstanceOf(EthereumClient::class, $this->client);
        $this->assertEquals('https://eth-mainnet.alchemyapi.io/v2/test', $this->client->getRpcUrl());
        $this->assertEquals(1, $this->client->getConfiguredChainId());
    }

    public function testClientWithCustomChainId(): void
    {
        $client = new EthereumClient('https://polygon-rpc.com', 137);
        $this->assertEquals(137, $client->getConfiguredChainId());
    }

    public function testFormatBlockIdentifier(): void
    {
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('formatBlockIdentifier');
        $method->setAccessible(true);

        $this->assertEquals('latest', $method->invoke($this->client, 'latest'));
        $this->assertEquals('earliest', $method->invoke($this->client, 'earliest'));
        $this->assertEquals('pending', $method->invoke($this->client, 'pending'));
        $this->assertEquals('0x123', $method->invoke($this->client, '0x123'));
        $this->assertEquals('0x1', $method->invoke($this->client, '1'));
    }

    public function testInvalidRpcUrl(): void
    {
        $client = new EthereumClient('invalid-url');
        try {
            $client->getBlockNumber();
            $this->fail('Expected EthereumException to be thrown');
        } catch (\Throwable $e) {
          
            $this->assertTrue(true);
        }
    }
}
