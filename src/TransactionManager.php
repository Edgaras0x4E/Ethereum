<?php

namespace Edgaras\Ethereum;

use InvalidArgumentException;
use Edgaras\Ethereum\Crypto;
use Edgaras\Ethereum\RLP;
use kornrunner\Keccak;

class TransactionManager
{
    private EthereumClient $client;
    private Wallet $wallet;

    public function __construct(EthereumClient $client, Wallet $wallet)
    {
        $this->client = $client;
        $this->wallet = $wallet;
    }

    public function sendTransaction(Transaction $transaction): string
    {
        $transaction->validate();

        if (!$transaction->getFrom()) {
            $transaction->setFrom($this->wallet->getAddress());
        }

        if (!$transaction->getNonce()) {
            $nonce = $this->client->getTransactionCount($this->wallet->getAddress());
            $transaction->setNonce($nonce);
        }

        
        if (!$transaction->getFrom() || $transaction->getFrom() !== $this->wallet->getAddress()) {
            $transaction->setFrom($this->wallet->getAddress());
        }

        $currentGasPrice = $transaction->getGasPrice();
        if (!$currentGasPrice || $currentGasPrice === '0x0' || $currentGasPrice === '0x00') {
            if (!$transaction->getMaxFeePerGas()) {
                
                $gasPrice = '0x5d21dba00'; 
                $transaction->setGasPrice($gasPrice);
            }
        }

        if ($transaction->getGas() === '0x5208') { 
            try {
                $estimatedGas = $this->client->estimateGas($transaction->toArray());
                $transaction->setGas($estimatedGas);
            } catch (EthereumException $e) {
            }
        }

        $signedTx = $this->signTransaction($transaction);

        return $this->client->sendRawTransaction($signedTx);
    }

    public function sendEther(string $to, string $amount, array $options = []): string
    {
        $transaction = Transaction::createTransfer(
            $this->wallet->getAddress(),
            $to,
            Transaction::etherToWei($amount),
            $options
        );

        return $this->sendTransaction($transaction);
    }
    
    public function sendEtherEIP1559(string $to, string $amount, string $maxFeePerGas, string $maxPriorityFeePerGas, array $options = []): string
    {
        $transaction = Transaction::createTransfer(
            $this->wallet->getAddress(),
            $to,
            Transaction::etherToWei($amount),
            $options
        );
        
        $transaction->setEIP1559Gas($maxFeePerGas, $maxPriorityFeePerGas);

        return $this->sendTransaction($transaction);
    }

    

    public function signTransaction(Transaction $transaction): string
    {
        $chainId = $this->client->getChainId();
        
        
        if ($transaction->getMaxFeePerGas() && $transaction->getMaxPriorityFeePerGas()) {
            return $this->signEIP1559Transaction($transaction, $chainId);
        }
        
        
        return $this->signLegacyTransaction($transaction, $chainId);
    }
    
    private function signLegacyTransaction(Transaction $transaction, int $chainId): string
    {
        $txForSigning = [
            'nonce' => substr($transaction->getNonce(), 2),
            'gasPrice' => substr($transaction->getGasPrice(), 2),
            'gasLimit' => substr($transaction->getGas(), 2),
            'to' => substr($transaction->getTo(), 2),
            'value' => substr($transaction->getValue(), 2),
            'data' => $transaction->getData() ? substr($transaction->getData(), 2) : ''
        ];
        
        if (!$txForSigning['data']) {
            $txForSigning['data'] = '';
        }
        
        $txHash = self::hashLegacyTransactionForSigning($txForSigning, $chainId);
        $signature = Crypto::signTransaction($txHash, $this->wallet->getPrivateKey());
        
        $eip155V = (int)$signature['v'] + ($chainId * 2) + 35;
        
        $finalTx = array_merge($txForSigning, [
            'v' => dechex($eip155V),
            'r' => substr($signature['r'], 2),
            's' => substr($signature['s'], 2)
        ]);
        
        return RLP::encode($finalTx);
    }
    
    private function signEIP1559Transaction(Transaction $transaction, int $chainId): string
    {
        $txForSigning = [
            'chainId' => dechex($chainId),
            'nonce' => substr($transaction->getNonce(), 2),
            'maxPriorityFeePerGas' => substr($transaction->getMaxPriorityFeePerGas(), 2),
            'maxFeePerGas' => substr($transaction->getMaxFeePerGas(), 2),
            'gasLimit' => substr($transaction->getGas(), 2),
            'to' => $transaction->getTo() ? substr($transaction->getTo(), 2) : '',
            'value' => substr($transaction->getValue(), 2),
            'data' => $transaction->getData() ? substr($transaction->getData(), 2) : '',
            'accessList' => [] 
        ];
        
        if (!$txForSigning['data']) {
            $txForSigning['data'] = '';
        }
        
        $txHash = self::hashEIP1559TransactionForSigning($txForSigning);
        $signature = Crypto::signTransaction($txHash, $this->wallet->getPrivateKey());
        
        
        $eip1559V = (int)$signature['v'];
        
        $finalTx = array_merge($txForSigning, [
            'v' => dechex($eip1559V),
            'r' => substr($signature['r'], 2),
            's' => substr($signature['s'], 2)
        ]);
        
        
        $encodedTx = RLP::encode($finalTx);
        return '0x02' . substr($encodedTx, 2);
    }
    
    
    private static function hashLegacyTransactionForSigning(array $txForSigning, int $chainId): string
    {
        $chainIdHex = dechex($chainId);
        if (strlen($chainIdHex) % 2 !== 0) {
            $chainIdHex = '0' . $chainIdHex;
        }

        $signingItems = [
            $txForSigning['nonce'],
            $txForSigning['gasPrice'],
            $txForSigning['gasLimit'],
            $txForSigning['to'],
            $txForSigning['value'],
            $txForSigning['data'],
            $chainIdHex,
            '', 
            '', 
        ];

        $rlp = RLP::encode($signingItems);
        return Crypto::keccak256($rlp);
    }
    
    private static function hashEIP1559TransactionForSigning(array $txForSigning): string
    {
        $signingItems = [
            $txForSigning['chainId'],
            $txForSigning['nonce'],
            $txForSigning['maxPriorityFeePerGas'],
            $txForSigning['maxFeePerGas'],
            $txForSigning['gasLimit'],
            $txForSigning['to'],
            $txForSigning['value'],
            $txForSigning['data'],
            $txForSigning['accessList']
        ];

        
        $rlp = RLP::encode($signingItems);
        $typedPayload = '0x02' . substr($rlp, 2);
        return Crypto::keccak256($typedPayload);
    }

    public function waitForConfirmation(string $txHash, int $maxAttempts = 30, int $delaySeconds = 2): array
    {
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            try {
                $receipt = $this->client->getTransactionReceipt($txHash);
                
                if (!empty($receipt)) {
                    return $receipt;
                }
            } catch (EthereumException $e) {
            }

            sleep($delaySeconds);
            $attempts++;
        }

        throw new EthereumException('Transaction confirmation timeout');
    }

    public function getTransactionStatus(string $txHash): string
    {
        try {
            $receipt = $this->client->getTransactionReceipt($txHash);
            
            if (empty($receipt)) {
                return 'pending';
            }

            if (isset($receipt['status']) && $receipt['status'] === '0x0') {
                return 'failed';
            }

            return 'confirmed';
        } catch (EthereumException $e) {
            return 'pending';
        }
    }

    public function getTransactionDetails(string $txHash): array
    {
        $transaction = $this->client->getTransaction($txHash);
        $receipt = $this->client->getTransactionReceipt($txHash);

        return [
            'transaction' => $transaction,
            'receipt' => $receipt,
            'status' => $this->getTransactionStatus($txHash),
        ];
    }

    public function estimateCost(Transaction $transaction): array
    {
        $gasPrice = $transaction->getGasPrice() ?: $this->client->getGasPrice();
        $gasLimit = $transaction->getGas() ?: $this->client->estimateGas($transaction->toArray());
        
        $gasPriceDecimal = hexdec($gasPrice);
        $gasLimitDecimal = hexdec($gasLimit);
        
        $totalCost = $gasPriceDecimal * $gasLimitDecimal;
        
        return [
            'gasPrice' => $gasPrice,
            'gasLimit' => $gasLimit,
            'totalCost' => '0x' . dechex($totalCost),
            'totalCostWei' => $totalCost,
            'totalCostEther' => Transaction::weiToEther('0x' . dechex($totalCost)),
        ];
    }

    public function getBalance(?string $address = null): string
    {
        $address = $address ?: $this->wallet->getAddress();
        return $this->client->getBalance($address);
    }

    public function getBalanceInEther(?string $address = null): string
    {
        $balanceWei = $this->getBalance($address);
        return Transaction::weiToEther($balanceWei);
    }

    public function getNextNonce(): int
    {
        return $this->client->getTransactionCount($this->wallet->getAddress());
    }

    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    public function getClient(): EthereumClient
    {
        return $this->client;
    }
}
