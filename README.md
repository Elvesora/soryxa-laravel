# Elvesora Soryxa Laravel SDK

Official Laravel SDK for the [Soryxa](https://www.elvesora.com/soryxa) email validation API. It validates email addresses through `/api/v1/validate` and returns the current Soryxa decision contract, including policy, rollout, score-adjustment, usage, and response-header metadata.

## Requirements

- PHP 8.1+
- Laravel 10, 11, 12, or 13

## Installation

```bash
composer require elvesora/soryxa-laravel
```

The service provider and facade are auto-discovered.

### Publish The Config

```bash
php artisan vendor:publish --tag=soryxa-config
```

## Configuration

Add your API token to `.env`:

```env
SORYXA_API_TOKEN=your-api-token
```

| Variable | Default | Description |
|---|---|---|
| `SORYXA_API_TOKEN` | required | Bearer token from your Soryxa dashboard |
| `SORYXA_TIMEOUT` | `30` | Request timeout in seconds |
| `SORYXA_RETRIES` | `0` | Number of retries on 5xx errors |
| `SORYXA_RETRY_DELAY` | `100` | Delay between retries in milliseconds |
| `SORYXA_SILENT_ON_LIMIT` | `false` | Return a local `review` result instead of throwing on usage-limit errors |

## Quick Start

```php
use Elvesora\Soryxa\Facades\Soryxa;

$result = Soryxa::validate('user@example.com');

if ($result->isAllowed()) {
    // Email is valid; proceed.
}

if ($result->isBlocked()) {
    // Email failed validation or matched a block rule.
}

if ($result->needsReview()) {
    // Email needs manual review or safe fallback handling.
}
```

## Policy, Headers, And Correlation

`validate()` remains backward-compatible with `validate($email)`. Current optional arguments are:

```php
$result = Soryxa::validate(
    'user@example.com',
    'signup',
    [
        'X-Soryxa-Correlation-Id' => 'checkout-2026-06-21-001',
    ],
);
```

Method signature:

```php
validate(
    string $email,
    ?string $policyKey = null,
    array $headers = [],
): ValidationResult
```

`policy_key` is optional and defaults server-side to `default`. The validation API accepts only `email`, optional `policy_key`, and optional headers.

## Validation Result

The `ValidationResult` object exposes the full current response contract.

```php
$result->decision;          // allow, block, or review
$result->reasonCode;        // e.g. CLASSIFICATION_VALID
$result->decisionMessage;   // Internal/operator explanation
$result->customerMessage;   // Optional customer-safe message
$result->decisionReasons;   // Human-readable contributing reasons
$result->policyKey;         // Applied policy key, e.g. signup
$result->score;             // Final score, 0-100
$result->baseScore;         // Base score before adjustments, 0-100 or null
$result->scoreAdjustments;  // Policy score adjustments
$result->autoResolution;    // Auto-resolution metadata or null
$result->rollout;           // Rollout telemetry
$result->rawData;           // Raw upstream validation data or null
$result->upstream;          // Upstream health/degradation metadata or null
$result->data;              // Full response data, including future additive fields
$result->headers;           // Captured response headers
$result->usage;             // Usage summary
```

Helper methods mirror the properties:

```php
$result->decision();
$result->reasonCode();
$result->decisionMessage();
$result->customerMessage();
$result->decisionReasons();
$result->policyKey();
$result->score();
$result->baseScore();
$result->scoreAdjustments();
$result->autoResolution();
$result->rollout();
$result->upstream();
$result->data();
$result->headers();
```

Response-header helpers:

```php
$result->reasonCodeHeader(); // X-Soryxa-Reason-Code
$result->correlationId();    // X-Soryxa-Correlation-Id
$result->header('X-Soryxa-Correlation-Id');
```

Decision helpers:

```php
$result->isAllowed();
$result->isBlocked();
$result->needsReview();
$result->isLimitExceeded();
```

## Email And Checks

```php
$result->email();
$result->username();
$result->domain();
$result->firstName();
$result->lastName();
$result->classification();

$result->isSyntaxValid();
$result->hasMxRecords();
$result->isSmtpValid();
$result->isDisposable();
$result->isFreeProvider();
$result->isRoleAccount();
$result->isBogus();
$result->isCatchAll();
$result->isDomainRegistered();
$result->isNewlyRegisteredDomain();
```

Each check is a `Check` object:

```php
foreach ($result->checks as $check) {
    $check->name;
    $check->status;  // passed, failed, warning, or error
    $check->message;

    $check->passed();
    $check->failed();
    $check->isWarning();
    $check->isError();
}

$result->getCheck('Domain MX');
$result->passedChecks();
$result->failedChecks();
$result->warningChecks();
```

## Usage Tracking

Every successful API response includes current usage:

```php
$result->usage->remaining;
$result->usage->limit;
$result->usage->usagePercent();
```

## Serialization

```php
$array = $result->toArray();
```

`toArray()` includes all known fields, `usage`, `headers`, and any unknown future fields returned under `data`.

## Silent Mode

By default, exceeding your API usage limit throws `UsageLimitException`. If `SORYXA_SILENT_ON_LIMIT=true`, `validate()` returns a local fallback result instead:

- `decision` is `review`
- `reasonCode` is `LIMIT_EXCEEDED`
- `decisionMessage` explains the quota state
- `score` and `baseScore` are `0`
- `isLimitExceeded()` returns `true`
- `isAllowed()` returns `false`

This keeps application code running without silently approving traffic when quota is exhausted.

```php
$result = Soryxa::validate('user@example.com');

if ($result->isLimitExceeded()) {
    // Log, alert, queue for review, or ask the customer to retry later.
}
```

## Dependency Injection

```php
use Elvesora\Soryxa\SoryxaClient;
use Illuminate\Http\Request;

class EmailController
{
    public function verify(Request $request, SoryxaClient $soryxa)
    {
        $result = $soryxa->validate(
            $request->email,
            'signup',
            ['X-Soryxa-Correlation-Id' => (string) $request->headers->get('X-Request-Id')],
        );

        return response()->json($result->toArray());
    }
}
```

## Error Handling

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
    // 401: invalid, expired, or missing token.
} catch (SubscriptionException $e) {
    // 402: no active subscription.
} catch (InsufficientScopeException $e) {
    // 403: token lacks required scope.
} catch (ValidationException $e) {
    // 422: invalid input.
} catch (UsageLimitException $e) {
    // 429: usage limit exceeded.
} catch (ServerException $e) {
    // 5xx: server-side error.
} catch (ConnectionException $e) {
    // Network failure, timeout, or invalid response.
} catch (SoryxaException $e) {
    // Other API error.
}
```

Every exception exposes:

```php
$e->getMessage();
$e->getErrorCode();
$e->getStatusCode();
$e->getResponseBody();
```

## Decision Reference

The `decision` field is always one of:

| Decision | Meaning |
|---|---|
| `allow` | Email passed validation |
| `block` | Email failed validation or matched a block rule |
| `review` | Email is risky, degraded, quota-limited, or requires manual review |

Common reason codes include:

| Decision | Reason Code | Description |
|---|---|---|
| `allow` | `ALLOW_LIST_MATCH` | Email matched your allow list |
| `allow` | `CLASSIFICATION_VALID` | Email classified as valid |
| `allow` | `DEFAULT_ALLOW` | No blocking rules triggered |
| `block` | `BLOCK_LIST_MATCH` | Email matched your block list |
| `block` | `BLOCK_DISPOSABLE` | Disposable email address blocked |
| `block` | `BLOCK_FREE_PROVIDER` | Free provider blocked by rules |
| `block` | `BLOCK_ROLE_ACCOUNT` | Role-based address blocked |
| `block` | `BLOCK_BOGUS_DOMAIN` | Domain is bogus or non-existent |
| `block` | `SCORE_BELOW_BLOCK_THRESHOLD` | Score below configured block threshold |
| `block` | `CLASSIFICATION_INVALID` | Email classified as invalid |
| `review` | `SCORE_BELOW_THRESHOLD` | Score below review threshold |
| `review` | `CLASSIFICATION_RISKY` | Email classified as risky |
| `review` | `DISPOSABLE_REVIEW` | Disposable address flagged for review |
| `review` | `SERVICE_UNAVAILABLE` | Upstream check unavailable |
| `review` | `LIMIT_EXCEEDED` | Local silent-mode fallback only |

## Development

Run the package contract checks:

```bash
composer test
```

## License

MIT
