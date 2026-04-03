<?php

namespace Elvesora\Soryxa\Exceptions;

/**
 * 422 error: VALIDATION_ERROR
 */
class ValidationException extends SoryxaException {
    public function getValidationErrors(): array {
        return $this->responseBody['errors'] ?? [];
    }
}
