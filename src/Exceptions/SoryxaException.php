<?php

namespace Elvesora\Soryxa\Exceptions;

use Exception;

class SoryxaException extends Exception {
    protected string $errorCode;
    protected array $responseBody;
    protected int $statusCode;

    public function __construct(
        string $message,
        string $errorCode,
        int $statusCode,
        array $responseBody = [],
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);

        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getErrorCode(): string {
        return $this->errorCode;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function getResponseBody(): array {
        return $this->responseBody;
    }

    /**
     * Build the appropriate exception from an HTTP response.
     */
    public static function fromResponse(int $statusCode, array $body): self {
        $message = $body['message'] ?? 'Unknown error';
        $errorCode = $body['error_code'] ?? 'UNKNOWN_ERROR';

        return match (true) {
            $statusCode === 401 => new AuthenticationException($message, $errorCode, $statusCode, $body),
            $statusCode === 402 => new SubscriptionException($message, $errorCode, $statusCode, $body),
            $statusCode === 403 => new InsufficientScopeException($message, $errorCode, $statusCode, $body),
            $statusCode === 422 => new ValidationException($message, $errorCode, $statusCode, $body),
            $statusCode === 429 => new UsageLimitException($message, $errorCode, $statusCode, $body),
            $statusCode >= 500 => new ServerException($message, $errorCode, $statusCode, $body),
            default => new self($message, $errorCode, $statusCode, $body),
        };
    }
}
