# Ethereum PHP Library

PHP library for interacting with the Ethereum blockchain. Provides wallet management, ETH transfers, balance and transaction queries, and helpful utilities.

## Features

- **Wallet management**: Create/import wallets, export JSON, derive address and public key
- **ETH transfers**: Build, sign and send legacy (Type-0) and EIP-1559 (Type-2) transactions; wait for confirmations
- **Balances and network**: `getBalance`, `getBalanceInEther`, `getBlockNumber`, `getNetworkInfo`
- **Transaction details**: `getTransaction`, `getTransactionReceipt`, `getTransactionStatus`, `getTransactionDetails`
- **Address activity**: Scan recent blocks with `getTransactionsForAddress`
- **Block transactions**: Fetch with `getTransactionsByBlockNumber`
- **Utilities**: Unit conversion, checksum addresses, hex helpers, hashing
- **JSON‑RPC client wrapper**: Under-the-hood client for core RPC calls

## Installation

```bash
composer require edgaras/ethereum
```

Requirements:

- PHP 8.3+
- Extensions: `gmp`, `bcmath`

## Quick Start

```php
<?php

use Edgaras\Ethereum\Ethereum;
use Edgaras\Ethereum\Wallet;

$rpcUrl = 'https://sepolia.infura.io/v3/API_KEY';
$chainId = 11155111; // Sepolia

$eth = new Ethereum($rpcUrl, $chainId);

// Create a new wallet (or import with new Wallet('0x...'))
$wallet = $eth->createWallet();
$eth->setWallet($wallet);

echo "Address: {$wallet->getAddress()}<br>";
echo "Private Key: {$wallet->getPrivateKeyHex()}<br>";

// Get balance (ETH)
echo "Balance: {$eth->getBalanceInEther()} ETH<br>";
```

### Send ETH

```php
use Edgaras\Ethereum\Ethereum;
use Edgaras\Ethereum\Wallet;

$eth = new Ethereum($rpcUrl, $chainId);
$eth->setWallet(new Wallet('0xYOUR_PRIVATE_KEY_HEX'));

$txHash = $eth->sendEther('0xRecipientAddress...', '0.01');
echo "tx: $txHash<br>";

// (optional) wait for confirmation
$receipt = $eth->waitForConfirmation($txHash, maxAttempts: 60, delaySeconds: 2);
echo "included in block: {$receipt['blockNumber']}<br>";
```

### Send ETH with EIP-1559 (Type-2 Transactions)

```php
use Edgaras\Ethereum\Ethereum;
use Edgaras\Ethereum\Wallet;
use Edgaras\Ethereum\Utils;

$eth = new Ethereum($rpcUrl, $chainId);
$eth->setWallet(new Wallet('0xYOUR_PRIVATE_KEY_HEX'));

// Set EIP-1559 gas parameters (in gwei)
$maxPriorityFeePerGasGwei = '2';  // 2 gwei tip
$maxFeePerGasGwei = '20';         // 20 gwei max fee

// Convert to wei hex
$maxPriorityFeePerGas = Utils::toHex(Utils::gweiToWei($maxPriorityFeePerGasGwei));
$maxFeePerGas = Utils::toHex(Utils::gweiToWei($maxFeePerGasGwei));

$txHash = $eth->sendEtherEIP1559(
    '0xRecipientAddress...', 
    '0.01', 
    $maxFeePerGas, 
    $maxPriorityFeePerGas
);
echo "EIP-1559 tx: $txHash<br>";
```

### Method reference

```php
// Legacy (Type-0): uses gasPrice
public function sendEther(string $to, string $amount, array $options = []): string

// EIP-1559 (Type-2): uses dynamic fees
public function sendEtherEIP1559(
    string $to,
    string $amount,
    string $maxFeePerGas,          // hex wei, e.g. '0x4a817c800' (20 gwei)
    string $maxPriorityFeePerGas,  // hex wei, e.g. '0x77359400' (2 gwei)
    array $options = []
): string
```

Notes:
- `amount` is in ETH as a decimal string (e.g. '0.01').
- `maxFeePerGas` and `maxPriorityFeePerGas` must be hex-encoded wei values (use `Utils::gweiToWei()` + `Utils::toHex()`).

## Practical Examples

Update `RPC_URL` and `CHAIN_ID` to match your node/network.

### 1) Minimal wallet overview (balance + recent txs)

```php
use Edgaras\Ethereum\Ethereum;
use Edgaras\Ethereum\Utils;

$eth = new Ethereum($rpcUrl, $chainId);
$address = '0x11d6223151490ef707a9eab3dbf3d166d7b453d3';

$balanceEth = $eth->getBalanceInEther($address);
echo "Address: " . Utils::toChecksumAddress($address) . "<br>";
echo "Balance: $balanceEth ETH<br><br>";

$txs = $eth->getTransactionsForAddress($address, maxBlocksToScan: 1500, maxTxToShow: 20);
foreach ($txs as $t) {
    echo sprintf("[%s] %s | %s ETH | peer: %s | block: %d<br>",
        $t['direction'], $t['hash'], $t['valueEth'], $t['peer'], $t['blockNumber']
    );
}
```

### 2) List transactions in a block

```php
use Edgaras\Ethereum\Ethereum;

$eth = new Ethereum($rpcUrl, $chainId);
$blockNumber = 9354804; // example
$txs = $eth->getTransactionsByBlockNumber($blockNumber, limit: 10);

foreach ($txs as $i => $tx) {
    $idx = $i + 1;
    echo "#$idx<br>";
    echo "  hash:  " . ($tx['hash'] ?? '') . "<br>";
    echo "  from:  " . ($tx['from'] ?? '') . "<br>";
    echo "  to:    " . ($tx['to'] ?? '') . "<br>";
    echo "  value: " . ($tx['value'] ?? '0x0') . " (wei hex)<br><br>";
}
```

### 3) Simple transfer demo

```php
use Edgaras\Ethereum\Ethereum;
use Edgaras\Ethereum\Wallet;

$eth = new Ethereum($rpcUrl, $chainId);
$sender = new Wallet('0xSENDER_PRIVATE_KEY');
$eth->setWallet($sender);

$recipient = '0xRecipientAddress...';
$amountEth = '0.001';

$senderBal = $eth->getBalanceInEther($sender->getAddress());
echo "Sender balance: $senderBal ETH<br>";

if ((float)$senderBal < (float)$amountEth) {
    echo "Insufficient balance.<br>";
    exit(0);
}

$txHash = $eth->sendEther($recipient, $amountEth);
echo "tx: $txHash<br>";
```

## Utilities

```php
use Edgaras\Ethereum\Utils;

// Unit conversion
$wei = Utils::etherToWei('1.5');
$eth = Utils::weiToEther('0x204fce5e3e250261100000000'); // works with hex or decimals
$gwei = Utils::weiToGwei('0x4a817c800');

// Address helpers
$isValid = Utils::isValidAddress('0x242d35Cc6634C0532925a3b8D4C9db96C4b4d8b6');
$checksum = Utils::toChecksumAddress('0x242d35cc6634c0532925a3b8d4c9db96c4b4d8b6');

// Hex helpers
$hex = Utils::toHex('255'); // 0xff
$dec = Utils::fromHex('0xff'); // 255
```

## Notes

- **Transaction Types**: Both legacy (Type-0) and EIP-1559 (Type-2) transactions are supported. Use `sendEther()` for legacy or `sendEtherEIP1559()` for modern fee market transactions.
- **EIP-1559 Benefits**: Better fee predictability, reduced overpayment during low congestion, and dynamic fee market adaptation.
- **Gas Parameters**: For EIP-1559, set `maxFeePerGas` (maximum total fee) and `maxPriorityFeePerGas` (tip to miners). Monitor network base fee for optimal values.
- EIP-1559 signing follows EIP-2718 typed transactions (hashing `0x02 || RLP(list)`), ensuring correct sender recovery and node compatibility.


 