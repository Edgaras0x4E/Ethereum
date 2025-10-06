<?php

namespace Edgaras\Ethereum;

use Exception;

class EthereumException extends Exception
{
    private int $errorCode;
    private array $errorData;

    public function __construct(string $message, int $errorCode = 0, ?Exception $previous = null, array $errorData = [])
    {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorData(): array
    {
        return $this->errorData;
    }
}
