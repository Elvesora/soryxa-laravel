<?php

namespace Elvesora\Soryxa\Exceptions;

/**
 * 402 errors: NO_SUBSCRIPTION, SUBSCRIPTION_INACTIVE
 */
class SubscriptionException extends SoryxaException {
    public function hasNoSubscription(): bool {
        return $this->errorCode === 'NO_SUBSCRIPTION';
    }

    public function isInactive(): bool {
        return $this->errorCode === 'SUBSCRIPTION_INACTIVE';
    }
}
