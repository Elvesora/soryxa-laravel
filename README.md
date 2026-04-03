# Elvesora Soryxa Laravel SDK

Official Laravel SDK for the [Soryxa](https://www.elvesora.com/soryxa) email validation API. Validate email addresses in your Laravel application with a single method call and get detailed results through a clean, fluent API.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13

## Installation

```bash
composer require elvesora/soryxa-laravel
```

The service provider and facade are auto-discovered. No manual registration needed.

### Publish the Config (optional)

```bash
php artisan vendor:publish --tag=soryxa-config
```

## Configuration

Add your API token to `.env`:

```env
SORYXA_API_TOKEN=your-api-token
```

All available environment variables:

| Variable | Default | Description |
|---|---|---|
| `SORYXA_API_TOKEN` | *(required)* | Bearer token from your Soryxa dashboard |
| `SORYXA_TIMEOUT` | `30` | Request timeout in seconds |
| `SORYXA_RETRIES` | `0` | Number of retries on 5xx errors |
| `SORYXA_RETRY_DELAY` | `100` | Delay between retries in milliseconds |
| `SORYXA_SILENT_ON_LIMIT` | `false` | Suppress usage limit exceptions (see [Silent Mode](#silent-mode)) |

## Quick Start

```php
use Elvesora\Soryxa\Facades\Soryxa;

$result = Soryxa::validate('user@example.com');

if ($result->isAllowed()) {
    // Email is valid — proceed
}

if ($result->isBlocked()) {
    // Email failed validation — reject
}

if ($result->needsReview()) {
    // Email is risky — flag for manual review
}
```

## Validation Result

The `validate()` method returns a `ValidationResult` object with access to all validation data through public properties and helper methods.

### Decision

```php
$result = Soryxa::validate('user@example.com');

// Access as properties
$result->decision;        // 'allow', 'block', or 'review'
$result->reasonCode;      // e.g. 'CLASSIFICATION_VALID'
$result->decisionMessage; // 'Email passed all validation checks'
$result->decisionReasons; // ['Valid email with high quality score']
$result->score;           // 0–100

// Or as methods
$result->decision();
$result->reasonCode();
$result->decisionMessage();
$result->decisionReasons();
$result->score();
```

#### Decision Helpers

```php
$result->isAllowed();       // true when decision is 'allow'
$result->isBlocked();       // true when decision is 'block'
$result->needsReview();     // true when decision is 'review'
$result->isLimitExceeded(); // true when usage limit was silently exceeded
```

### Email & Identity

```php
$result->email();          // 'user@example.com'
$result->username();       // 'user'
$result->domain();         // 'example.com'
$result->firstName();      // 'John' or null
$result->lastName();       // 'Doe' or null
$result->classification(); // 'valid', 'risky', or 'invalid'
```

### Boolean Check Helpers

Quick boolean shortcuts to inspect specific validation checks:

```php
// Syntax & DNS
$result->isSyntaxValid();          // Email format is valid
$result->hasMxRecords();           // Domain has MX records
$result->isSmtpValid();            // SMTP verification passed
$result->isDomainRegistered();     // Domain is registered
$result->isNewlyRegisteredDomain(); // Domain was recently registered

// Email classification
$result->isDisposable();           // Disposable/temporary email
$result->isFreeProvider();         // Free email provider (Gmail, Yahoo, etc.)
$result->isFree();                 // Alias for isFreeProvider()
$result->isRoleAccount();          // Role-based address (info@, admin@, etc.)
$result->isRole();                 // Alias for isRoleAccount()
$result->isBogus();                // Username matches known bogus patterns
$result->isCatchAll();             // Domain accepts any address
```

### Individual Checks

Each validation response includes an array of `Check` objects with detailed results for every check performed:

```php
foreach ($result->checks as $check) {
    $check->name;    // e.g. 'Syntax Check', 'Domain MX', 'SMTP Verification'
    $check->status;  // 'passed', 'failed', 'warning', or 'error'
    $check->message; // 'Valid email format'

    $check->passed();    // true if status is 'passed'
    $check->failed();    // true if status is 'failed'
    $check->isWarning(); // true if status is 'warning'
    $check->isError();   // true if status is 'error'
}
```

#### Filtering Checks

```php
// Get a specific check by name (case-insensitive)
$mx = $result->getCheck('Domain MX');
if ($mx && $mx->passed()) {
    // MX records are valid
}

// Get all checks that passed
$passed = $result->passedChecks();

// Get all checks that failed
$failed = $result->failedChecks();

// Get all checks with warnings
$warnings = $result->warningChecks();
```

### Usage Tracking

Every response includes your current API credit usage:

```php
$result->usage->remaining;      // Credits remaining
$result->usage->limit;          // Total credit limit
$result->usage->usagePercent(); // Percentage consumed (0–100)
```

### Serialization

All response objects (`ValidationResult`, `Check`, `Usage`) can be converted to arrays:

```php
$array = $result->toArray();
```

## Silent Mode

By default, exceeding your API usage limit throws a `UsageLimitException`. If you prefer your application to continue without interruption, enable silent mode:

```env
SORYXA_SILENT_ON_LIMIT=true
```

When silent mode is enabled and the usage limit is exceeded, `validate()` will **not** throw an exception. Instead, it returns a `ValidationResult` with:

- `decision` set to `"allow"` — so your application flow continues
- `reasonCode` set to `"LIMIT_EXCEEDED"`
- `decisionMessage` set to `"You have exceeded your monthly usage limit."`
- `score` set to `0`
- All boolean check helpers (`isDisposable()`, `isFree()`, etc.) return `false`
- `email()`, `username()`, and `domain()` still return the input values
- `checks` is an empty array
- `classification()`, `firstName()`, `lastName()` return `null`

Use `isLimitExceeded()` to detect this state and handle it in your application:

```php
$result = Soryxa::validate('user@example.com');

if ($result->isLimitExceeded()) {
    // Usage limit hit — result is a passthrough "allow"
    // Log it, notify admin, etc.
}

// Your normal flow continues either way
if ($result->isAllowed()) {
    // proceed
}
```

## Dependency Injection

You can inject `SoryxaClient` directly instead of using the facade:

```php
use Elvesora\Soryxa\SoryxaClient;

class EmailController
{
    public function verify(Request $request, SoryxaClient $soryxa)
    {
        $result = $soryxa->validate($request->email);

        return response()->json($result->toArray());
    }
}
```

## Error Handling

Every API error throws a specific exception extending `SoryxaException`:

```php
use Elvesora\Soryxa\Facades\Soryxa;
use Elvesora\Soryxa\Exceptions\AuthenticationException;
use Elvesora\Soryxa\Exceptions\SubscriptionException;
use Elvesora\Soryxa\Exceptions\InsufficientScopeException;
use Elvesora\Soryxa\Exceptions\ValidationException;
use Elvesora\Soryxa\Exceptions\UsageLimitException;
use Elvesora\Soryxa\Exceptions\ServerException;
use Elvesora\Soryxa\Exceptions\ConnectionException;
use Elvesora\Soryxa\Exceptions\SoryxaException;

try {
    $result = Soryxa::validate($email);
} catch (AuthenticationException $e) {
    // 401 — Invalid, expired, or missing token
} catch (SubscriptionException $e) {
    // 402 — No active subscription
} catch (InsufficientScopeException $e) {
    // 403 — Token lacks required scope
} catch (ValidationException $e) {
    // 422 — Invalid input (e.g. malformed email)
} catch (UsageLimitException $e) {
    // 429 — Usage limit exceeded
} catch (ServerException $e) {
    // 5xx — Server-side error
} catch (ConnectionException $e) {
    // Network failure or timeout
} catch (SoryxaException $e) {
    // Catch-all for any other API error
}
```

### Common Exception Methods

Every exception provides these methods:

```php
$e->getMessage();       // Human-readable error message
$e->getErrorCode();     // API error code (e.g. 'TOKEN_EXPIRED')
$e->getStatusCode();    // HTTP status code (e.g. 401)
$e->getResponseBody();  // Full API response as array
```

### Exception-Specific Helpers

#### AuthenticationException (401)

```php
$e->isTokenExpired();  // Token has expired
$e->isInvalidToken();  // Token is invalid
$e->isMissingToken();  // No token provided
```

#### SubscriptionException (402)

```php
$e->hasNoSubscription(); // No subscription found
$e->isInactive();        // Subscription is inactive
```

#### InsufficientScopeException (403)

```php
$e->getRequiredScopes(); // ['validate']
```

#### ValidationException (422)

```php
$e->getValidationErrors(); // ['email' => ['The email field is required.']]
```

#### UsageLimitException (429)

```php
$e->getLimit();       // Total credit limit
$e->getUsed();        // Credits used
$e->getRemaining();   // Credits remaining
$e->getPeriodEndsAt(); // Period end date (e.g. '2026-05-01')
```

### Exception Reference

| Exception | HTTP Status | Error Codes |
|---|---|---|
| `AuthenticationException` | 401 | `INVALID_AUTH_HEADER`, `MISSING_TOKEN`, `INVALID_TOKEN`, `TOKEN_EXPIRED`, `TEAM_NOT_FOUND`, `TOKEN_NOT_FOUND` |
| `SubscriptionException` | 402 | `NO_SUBSCRIPTION`, `SUBSCRIPTION_INACTIVE` |
| `InsufficientScopeException` | 403 | `INSUFFICIENT_SCOPE` |
| `ValidationException` | 422 | `VALIDATION_ERROR` |
| `UsageLimitException` | 429 | `USAGE_LIMIT_EXCEEDED`, `INSUFFICIENT_CREDITS` |
| `ServerException` | 5xx | `USAGE_CHECK_ERROR` |
| `ConnectionException` | — | `CONNECTION_ERROR` |

## Decision Reference

The `decision` field will be one of three values:

| Decision | Meaning |
|---|---|
| `allow` | Email passed validation |
| `block` | Email failed validation or matched a block rule |
| `review` | Email is risky and requires manual review |

### Reason Codes

| Decision | Reason Code | Description |
|---|---|---|
| `allow` | `ALLOW_LIST_MATCH` | Email matched your allow list |
| `allow` | `CLASSIFICATION_VALID` | Email classified as valid |
| `allow` | `DEFAULT_ALLOW` | No rules triggered — allowed by default |
| `block` | `BLOCK_LIST_MATCH` | Email matched your block list |
| `block` | `BLOCK_DISPOSABLE` | Disposable email address |
| `block` | `BLOCK_FREE_PROVIDER` | Free email provider blocked by rules |
| `block` | `BLOCK_ROLE_ACCOUNT` | Role-based address (e.g. info@, admin@) |
| `block` | `BLOCK_BOGUS_DOMAIN` | Domain is bogus or non-existent |
| `block` | `SCORE_BELOW_BLOCK_THRESHOLD` | Score below configured block threshold |
| `block` | `CLASSIFICATION_INVALID` | Email classified as invalid |
| `review` | `SCORE_BELOW_THRESHOLD` | Score below review threshold |
| `review` | `CLASSIFICATION_RISKY` | Email classified as risky |
| `review` | `DISPOSABLE_REVIEW` | Disposable address flagged for review |
| `review` | `SERVICE_UNAVAILABLE` | Upstream check unavailable |
| `allow` | `LIMIT_EXCEEDED` | Usage limit exceeded (silent mode only) |

## License

MIT
