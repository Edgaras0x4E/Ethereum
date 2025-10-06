<?php

namespace Edgaras\Ethereum;

class Ethereum
{
    private EthereumClient $client;
    private ?Wallet $wallet;
    private ?TransactionManager $transactionManager;

    public function __construct(string $rpcUrl, int $chainId = 1, array $options = [])
    {
        $this->client = new EthereumClient($rpcUrl, $chainId, $options);
        
        $this->wallet = null;
        $this->transactionManager = null;
    }

    public function setWallet(Wallet $wallet): self
    {
        $this->wallet = $wallet;
        $this->transactionManager = new TransactionManager($this->client, $wallet);
        
        return $this;
    }

    public function createWallet(): Wallet
    {
        $wallet = new Wallet();
        $this->setWallet($wallet);
        return $wallet;
    }

    public function importWallet(string $privateKey): Wallet
    {
        $wallet = new Wallet($privateKey);
        $this->setWallet($wallet);
        return $wallet;
    }

    public function importWalletFromJson(string $jsonData): Wallet
    {
        $wallet = Wallet::importFromJson($jsonData);
        $this->setWallet($wallet);
        return $wallet;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function getTransactionManager(): ?TransactionManager
    {
        return $this->transactionManager;
    }

    public function getClient(): EthereumClient
    {
        return $this->client;
    }

    

    public function sendEther(string $to, string $amount, array $options = []): string
    {
        if (!$this->transactionManager) {
            throw new \InvalidArgumentException('Wallet must be set to send transactions');
        }

        return $this->transactionManager->sendEther($to, $amount, $options);
    }
    
    public function sendEtherEIP1559(string $to, string $amount, string $maxFeePerGas, string $maxPriorityFeePerGas, array $options = []): string
    {
        if (!$this->transactionManager) {
            throw new \InvalidArgumentException('Wallet must be set to send transactions');
        }

        return $this->transactionManager->sendEtherEIP1559($to, $amount, $maxFeePerGas, $maxPriorityFeePerGas, $options);
    }

    public function getBalance(?string $address = null): string
    {
        $address = $address ?: ($this->wallet ? $this->wallet->getAddress() : null);
        
        if (!$address) {
            throw new \InvalidArgumentException('Address is required');
        }

        return $this->client->getBalance($address);
    }

    public function getBalanceInEther(?string $address = null): string
    {
        $balanceWei = $this->getBalance($address);
        return Utils::weiToEther($balanceWei);
    }

    public function getBlockNumber(): int
    {
        return $this->client->getBlockNumber();
    }

    public function getBlock(string $blockIdentifier, bool $includeTransactions = false): array
    {
        return $this->client->getBlock($blockIdentifier, $includeTransactions);
    }

    public function getTransaction(string $txHash): array
    {
        return $this->client->getTransaction($txHash);
    }

    public function getTransactionReceipt(string $txHash): array
    {
        return $this->client->getTransactionReceipt($txHash);
    }

    public function getGasPrice(): string
    {
        return $this->client->getGasPrice();
    }

    public function estimateGas(array $transaction): string
    {
        return $this->client->estimateGas($transaction);
    }

    public function isSyncing(): array|false
    {
        return $this->client->isSyncing();
    }

    public function getNetworkInfo(): array
    {
        return [
            'chainId' => $this->client->getChainId(),
            'networkVersion' => $this->client->getNetworkVersion(),
            'peerCount' => $this->client->getPeerCount(),
            'syncing' => $this->client->isSyncing(),
        ];
    }

    public function waitForConfirmation(string $txHash, int $maxAttempts = 30, int $delaySeconds = 2): array
    {
        if (!$this->transactionManager) {
            throw new \InvalidArgumentException('TransactionManager is required');
        }

        return $this->transactionManager->waitForConfirmation($txHash, $maxAttempts, $delaySeconds);
    }

    public function getTransactionStatus(string $txHash): string
    {
        if (!$this->transactionManager) {
            throw new \InvalidArgumentException('TransactionManager is required');
        }

        return $this->transactionManager->getTransactionStatus($txHash);
    }

    public function getTransactionDetails(string $txHash): array
    {
        if (!$this->transactionManager) {
            throw new \InvalidArgumentException('TransactionManager is required');
        }

        return $this->transactionManager->getTransactionDetails($txHash);
    }

    

    public function createTransaction(array $transaction = []): Transaction
    {
        return new Transaction($transaction);
    }

    public function sendTransaction(Transaction $transaction): string
    {
        if (!$this->transactionManager) {
            throw new \InvalidArgumentException('TransactionManager is required');
        }

        return $this->transactionManager->sendTransaction($transaction);
    }

    public function estimateTransactionCost(Transaction $transaction): array
    {
        if (!$this->transactionManager) {
            throw new \InvalidArgumentException('TransactionManager is required');
        }

        return $this->transactionManager->estimateCost($transaction);
    }

	
	public function getTransactionsForAddress(string $address, int $maxBlocksToScan = 500, int $maxTxToShow = 20): array
	{
		$results = [];
		$latest = $this->getBlockNumber();
		$start = max(0, $latest - $maxBlocksToScan + 1);
		$needle = strtolower($address);

		for ($bn = $latest; $bn >= $start && count($results) < $maxTxToShow; $bn--) {
			$blockHex = '0x' . dechex($bn);
			$block = $this->getBlock($blockHex, true);
			if (!is_array($block) || empty($block['transactions'])) {
				continue;
			}

			foreach ($block['transactions'] as $tx) {
				$from = isset($tx['from']) ? strtolower($tx['from']) : '';
				$to = isset($tx['to']) ? strtolower((string)$tx['to']) : '';

				if ($from !== $needle && $to !== $needle) {
					continue;
				}

				$hash = $tx['hash'] ?? '';
				$valueHex = $tx['value'] ?? '0x0';
				$valueEth = Utils::weiToEther($valueHex);
				$direction = ($to === $needle) ? 'IN' : 'OUT';
				$peer = ($to === $needle) ? ($tx['from'] ?? '') : ($tx['to'] ?? '');
				$peerChecksum = $peer ? Utils::toChecksumAddress($peer) : '';

				$results[] = [
					'direction' => $direction,
					'hash' => $hash,
					'valueEth' => rtrim(rtrim($valueEth, '0'), '.'),
					'peer' => $peerChecksum,
					'blockNumber' => $bn,
				];

				if (count($results) >= $maxTxToShow) {
					break 2;
				}
			}
		}

		return $results;
	}

	public function getTransactionsByBlockNumber(int $blockNumber, int $limit = 50): array
	{
		$blockHex = '0x' . dechex($blockNumber);
		$block = $this->getBlock($blockHex, true);
		if (!is_array($block) || empty($block['transactions']) || !is_array($block['transactions'])) {
			return [];
		}

		if ($limit <= 0) {
			return [];
		}

		return array_slice($block['transactions'], 0, $limit);
	}
}
