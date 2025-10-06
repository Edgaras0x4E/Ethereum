<?php

namespace Edgaras\Ethereum;

use InvalidArgumentException;

class Transaction
{
    private string $from;
    private ?string $to;
    private string $value;
    private string $gas;
    private ?string $gasPrice;
    private ?string $data;
    private ?string $nonce;
    private ?string $chainId;
    private ?string $maxFeePerGas;
    private ?string $maxPriorityFeePerGas;

    public function __construct(array $transaction = [])
    {
        $this->from = $transaction['from'] ?? '';
        $this->to = $transaction['to'] ?? null;
        $this->value = $transaction['value'] ?? '0x0';
        $this->gas = $transaction['gas'] ?? '0x5208'; 
        $this->gasPrice = $transaction['gasPrice'] ?? '0x0';
        $this->data = $transaction['data'] ?? null;
        $this->nonce = $transaction['nonce'] ?? null;
        $this->chainId = $transaction['chainId'] ?? null;
        $this->maxFeePerGas = $transaction['maxFeePerGas'] ?? null;
        $this->maxPriorityFeePerGas = $transaction['maxPriorityFeePerGas'] ?? null;
    }

    public static function createTransfer(string $from, string $to, string $value, array $options = []): self
    {
        $transaction = array_merge([
            'from' => $from,
            'to' => $to,
            'value' => self::toHex($value),
        ], $options);

        return new self($transaction);
    }

    

    public function setFrom(string $from): self
    {
        $this->from = $from;
        return $this;
    }

    public function setTo(string $to): self
    {
        $this->to = $to;
        return $this;
    }

    public function setValue(string $value): self
    {
        $this->value = self::toHex($value);
        return $this;
    }

    public function setGas(string $gas): self
    {
        $this->gas = self::toHex($gas);
        return $this;
    }

    public function setGasPrice(string $gasPrice): self
    {
        $this->gasPrice = self::toHex($gasPrice);
        return $this;
    }

    public function setData(string $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setNonce(string $nonce): self
    {
        $this->nonce = self::toHex($nonce);
        return $this;
    }

    public function setChainId(string $chainId): self
    {
        $this->chainId = self::toHex($chainId);
        return $this;
    }

    public function setEIP1559Gas(string $maxFeePerGas, string $maxPriorityFeePerGas): self
    {
        $this->maxFeePerGas = self::toHex($maxFeePerGas);
        $this->maxPriorityFeePerGas = self::toHex($maxPriorityFeePerGas);
        $this->gasPrice = null; 
        return $this;
    }

    public function toArray(): array
    {
        $transaction = [];

        if ($this->from) {
            $transaction['from'] = $this->from;
        }

        if ($this->to) {
            $transaction['to'] = $this->to;
        }

        if ($this->value) {
            $transaction['value'] = $this->value;
        }

        if ($this->gas) {
            $transaction['gas'] = $this->gas;
        }

        if ($this->gasPrice) {
            $transaction['gasPrice'] = $this->gasPrice;
        }

        if ($this->data) {
            $transaction['data'] = $this->data;
        }

        if ($this->nonce) {
            $transaction['nonce'] = $this->nonce;
        }

        if ($this->chainId) {
            $transaction['chainId'] = $this->chainId;
        }

        if ($this->maxFeePerGas) {
            $transaction['maxFeePerGas'] = $this->maxFeePerGas;
        }

        if ($this->maxPriorityFeePerGas) {
            $transaction['maxPriorityFeePerGas'] = $this->maxPriorityFeePerGas;
        }

        return $transaction;
    }

    public function getHash(): string
    {
        $txData = $this->toArray();
        return hash('sha3-256', json_encode($txData));
    }

    public function validate(): bool
    {
        if (empty($this->from)) {
            throw new InvalidArgumentException('From address is required');
        }

        if (!Wallet::isValidAddress($this->from)) {
            throw new InvalidArgumentException('Invalid from address');
        }

        if ($this->to && !Wallet::isValidAddress($this->to)) {
            throw new InvalidArgumentException('Invalid to address');
        }

        if (!self::isValidHex($this->value)) {
            throw new InvalidArgumentException('Invalid value format');
        }

        if (!self::isValidHex($this->gas)) {
            throw new InvalidArgumentException('Invalid gas format');
        }

        if ($this->gasPrice && !self::isValidHex($this->gasPrice)) {
            throw new InvalidArgumentException('Invalid gas price format');
        }

        if ($this->nonce && !self::isValidHex($this->nonce)) {
            throw new InvalidArgumentException('Invalid nonce format');
        }

        if ($this->chainId && !self::isValidHex($this->chainId)) {
            throw new InvalidArgumentException('Invalid chain ID format');
        }

        if ($this->maxFeePerGas && !self::isValidHex($this->maxFeePerGas)) {
            throw new InvalidArgumentException('Invalid max fee per gas format');
        }

        if ($this->maxPriorityFeePerGas && !self::isValidHex($this->maxPriorityFeePerGas)) {
            throw new InvalidArgumentException('Invalid max priority fee per gas format');
        }

        return true;
    }

    public static function toHex(string $value): string
    {
        if (str_starts_with($value, '0x')) {
            return $value;
        }

        return '0x' . dechex($value);
    }

    public static function fromHex(string $hex): string
    {
        if (str_starts_with($hex, '0x')) {
            $hex = substr($hex, 2);
        }

        return hexdec($hex);
    }

    public static function isValidHex(string $value): bool
    {
        if (str_starts_with($value, '0x')) {
            $value = substr($value, 2);
        }

        return ctype_xdigit($value);
    }

    public static function weiToEther(string $wei): string
    {
        $wei = self::fromHex($wei);
        return bcdiv($wei, '1000000000000000000', 18);
    }

    public static function etherToWei(string $ether): string
    {
        return bcmul($ether, '1000000000000000000');
    }

    public static function gweiToWei(string $gwei): string
    {
        return bcmul($gwei, '1000000000');
    }

    public static function weiToGwei(string $wei): string
    {
        $wei = self::fromHex($wei);
        return bcdiv($wei, '1000000000', 9);
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): ?string
    {
        return $this->to;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getGas(): string
    {
        return $this->gas;
    }

    public function getGasPrice(): ?string
    {
        return $this->gasPrice;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function getChainId(): ?string
    {
        return $this->chainId;
    }

    public function getMaxFeePerGas(): ?string
    {
        return $this->maxFeePerGas;
    }

    public function getMaxPriorityFeePerGas(): ?string
    {
        return $this->maxPriorityFeePerGas;
    }
}
