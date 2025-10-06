<?php

namespace Edgaras\Ethereum;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;

class EthereumClient
{
    private Client $httpClient;
    private string $rpcUrl;
    private int $chainId;
    private array $defaultHeaders;

    public function __construct(string $rpcUrl, int $chainId = 1, array $options = [])
    {
        $this->rpcUrl = $rpcUrl;
        $this->chainId = $chainId;
        
        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $this->httpClient = new Client(array_merge([
            'timeout' => 30,
            'headers' => $this->defaultHeaders,
        ], $options));
    }

    public function call(string $method, array $params = []): mixed
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1,
        ];

        try {
            $response = $this->httpClient->post($this->rpcUrl, [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new EthereumException($data['error']['message'], $data['error']['code']);
            }

            return $data['result'] ?? [];
        } catch (RequestException $e) {
            throw new EthereumException('Network error: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getBlockNumber(): int
    {
        $result = $this->call('eth_blockNumber');
        return hexdec($result);
    }

    public function getBlock(string $blockIdentifier, bool $includeTransactions = false): array
    {
        $blockId = $this->formatBlockIdentifier($blockIdentifier);
        return $this->call('eth_getBlockByNumber', [$blockId, $includeTransactions]);
    }

    public function getTransaction(string $txHash): array
    {
        return $this->call('eth_getTransactionByHash', [$txHash]);
    }

    public function getTransactionReceipt(string $txHash): array
    {
        return $this->call('eth_getTransactionReceipt', [$txHash]);
    }

    public function getBalance(string $address, string $blockIdentifier = 'latest'): string
    {
        $blockId = $this->formatBlockIdentifier($blockIdentifier);
        $result = $this->call('eth_getBalance', [$address, $blockId]);
        
        
        $result = str_starts_with($result, '0x') ? substr($result, 2) : $result;
        return self::hexToDecimal($result);
    }

    public function getTransactionCount(string $address, string $blockIdentifier = 'latest'): int
    {
        $blockId = $this->formatBlockIdentifier($blockIdentifier);
        $result = $this->call('eth_getTransactionCount', [$address, $blockId]);
        return hexdec($result);
    }

    public function estimateGas(array $transaction): string
    {
        return $this->call('eth_estimateGas', [$transaction]);
    }

    public function getGasPrice(): string
    {
        return $this->call('eth_gasPrice');
    }

    public function sendRawTransaction(string $signedTransaction): string
    {
        return $this->call('eth_sendRawTransaction', [$signedTransaction]);
    }

    public function callContract(array $transaction, string $blockIdentifier = 'latest'): string
    {
        $blockId = $this->formatBlockIdentifier($blockIdentifier);
        return $this->call('eth_call', [$transaction, $blockId]);
    }

    public function getCode(string $address, string $blockIdentifier = 'latest'): string
    {
        $blockId = $this->formatBlockIdentifier($blockIdentifier);
        return $this->call('eth_getCode', [$address, $blockId]);
    }

    public function getStorageAt(string $address, string $position, string $blockIdentifier = 'latest'): string
    {
        $blockId = $this->formatBlockIdentifier($blockIdentifier);
        return $this->call('eth_getStorageAt', [$address, $position, $blockId]);
    }

    public function getChainId(): int
    {
        $result = $this->call('eth_chainId');
        return hexdec($result);
    }

    public function getNetworkVersion(): string
    {
        return $this->call('net_version');
    }

    public function isSyncing(): array|false
    {
        $result = $this->call('eth_syncing');
        return $result === false ? false : $result;
    }

    public function getPeerCount(): int
    {
        $result = $this->call('net_peerCount');
        return hexdec($result);
    }

    private function formatBlockIdentifier(string $blockIdentifier): string
    {
        if (in_array($blockIdentifier, ['latest', 'earliest', 'pending'])) {
            return $blockIdentifier;
        }

        if (str_starts_with($blockIdentifier, '0x')) {
            return $blockIdentifier;
        }

        return '0x' . dechex($blockIdentifier);
    }

    public function getRpcUrl(): string
    {
        return $this->rpcUrl;
    }

    public function getConfiguredChainId(): int
    {
        return $this->chainId;
    }

    
    private static function hexToDecimal(string $hex): string
    {
        $hex = strtolower($hex);
        $decimal = 0;
        
        for ($i = 0; $i < strlen($hex); $i++) {
            $char = $hex[$i];
            $value = is_numeric($char) ? intval($char) : (ord($char) - ord('a') + 10);
            $decimal = $decimal * 16 + $value;
        }
        
        return (string)$decimal;
    }
}
