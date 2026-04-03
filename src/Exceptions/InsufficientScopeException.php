<?php

namespace Elvesora\Soryxa\Exceptions;

/**
 * 403 error: INSUFFICIENT_SCOPE
 */
class InsufficientScopeException extends SoryxaException {
    public function getRequiredScopes(): array {
        return $this->responseBody['required_scopes'] ?? [];
    }
}
