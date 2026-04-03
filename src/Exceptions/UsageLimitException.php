<?php

namespace Elvesora\Soryxa\Exceptions;

/**
 * 429 errors: USAGE_LIMIT_EXCEEDED, INSUFFICIENT_CREDITS
 */
class UsageLimitException extends SoryxaException {
    public function getLimit(): ?int {
        return $this->responseBody['data']['limit'] ?? null;
    }

    public function getUsed(): ?int {
        return $this->responseBody['data']['used'] ?? null;
    }

    public function getRemaining(): ?int {
        return $this->responseBody['data']['remaining'] ?? null;
    }

    public function getPeriodEndsAt(): ?string {
        return $this->responseBody['data']['period_ends_at'] ?? null;
    }
}
