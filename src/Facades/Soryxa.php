<?php

namespace Elvesora\Soryxa\Facades;

use Illuminate\Support\Facades\Facade;
use Elvesora\Soryxa\Responses\ValidationResult;
use Elvesora\Soryxa\SoryxaClient;

/**
 * @method static ValidationResult validate(string $email)
 *
 * @see SoryxaClient
 */
class Soryxa extends Facade {
    protected static function getFacadeAccessor(): string {
        return SoryxaClient::class;
    }
}
