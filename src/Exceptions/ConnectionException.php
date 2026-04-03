<?php

namespace Elvesora\Soryxa\Exceptions;

use Exception;

/**
 * Thrown when the HTTP request itself fails (network error, timeout, etc.)
 */
class ConnectionException extends SoryxaException {
    public static function fromException(Exception $e): self {
        return new self(
            message: 'Failed to connect to Soryxa API: ' . $e->getMessage(),
            errorCode: 'CONNECTION_ERROR',
            statusCode: 0,
            previous: $e,
        );
    }
}
