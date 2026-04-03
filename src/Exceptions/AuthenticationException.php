<?php

namespace Elvesora\Soryxa\Exceptions;

/**
 * 401 errors: INVALID_AUTH_HEADER, MISSING_TOKEN, INVALID_TOKEN, TOKEN_EXPIRED, TEAM_NOT_FOUND, TOKEN_NOT_FOUND
 */
class AuthenticationException extends SoryxaException {
    public function isTokenExpired(): bool {
        return $this->errorCode === 'TOKEN_EXPIRED';
    }

    public function isInvalidToken(): bool {
        return $this->errorCode === 'INVALID_TOKEN';
    }

    public function isMissingToken(): bool {
        return $this->errorCode === 'MISSING_TOKEN';
    }
}
