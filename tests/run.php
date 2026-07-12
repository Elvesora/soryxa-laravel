<?php

declare(strict_types=1);

require __DIR__ . '/../src/Responses/Usage.php';
require __DIR__ . '/../src/Responses/Check.php';
require __DIR__ . '/../src/Responses/ValidationResult.php';
require __DIR__ . '/../src/Exceptions/SoryxaException.php';
require __DIR__ . '/../src/Exceptions/AuthenticationException.php';
require __DIR__ . '/../src/Exceptions/SubscriptionException.php';
require __DIR__ . '/../src/Exceptions/InsufficientScopeException.php';
require __DIR__ . '/../src/Exceptions/ValidationException.php';
require __DIR__ . '/../src/Exceptions/UsageLimitException.php';
require __DIR__ . '/../src/Exceptions/ServerException.php';
require __DIR__ . '/../src/Exceptions/ConnectionException.php';
require __DIR__ . '/../src/SoryxaClient.php';

use Elvesora\Soryxa\Responses\ValidationResult;
use Elvesora\Soryxa\SoryxaClient;

function assertSameValue(mixed $expected, mixed $actual, string $message): void {
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$body = [
    'success' => true,
    'data' => [
        'policy_key' => 'signup',
        'decision' => 'block',
        'reason_code' => 'BLOCK_DISPOSABLE',
        'decision_message' => 'Disposable email address blocked.',
        'customer_message' => 'Use a permanent email address and try again.',
        'decision_reasons' => ['Disposable email domain blocked.'],
        'auto_resolution' => ['applied' => false],
        'rollout' => [
            'mode' => 'staged',
            'status' => 'active',
            'percentage' => 50,
        ],
        'checks' => [
            ['name' => 'Disposable Domain', 'status' => 'failed', 'message' => 'Disposable email domain blocked.'],
        ],
        'score' => 42,
        'base_score' => 80,
        'score_adjustments' => [
            ['type' => 'penalty', 'points' => -38, 'reason' => 'disposable'],
        ],
        'raw_data' => [
            'email' => 'user@example.com',
            'username' => 'user',
            'domain' => 'example.com',
            'checks' => ['syntax' => true, 'domain_mx' => true],
        ],
        'upstream' => ['status' => 'ok'],
        'context_signals' => ['legacy' => true],
        'future_field' => ['kept' => true],
    ],
    'usage' => ['remaining' => 99, 'limit' => 100],
];

$result = ValidationResult::fromResponse($body, [
    'X-Soryxa-Reason-Code' => 'BLOCK_DISPOSABLE',
    'X-Soryxa-Correlation-Id' => 'srx_test',
]);

assertSameValue('signup', $result->policyKey(), 'policy key should be exposed');
assertSameValue('Use a permanent email address and try again.', $result->customerMessage(), 'customer message should be exposed');
assertSameValue(80, $result->baseScore(), 'base score should be exposed');
assertSameValue('ok', $result->upstream()['status'], 'upstream health should be exposed');
assertSameValue('BLOCK_DISPOSABLE', $result->reasonCodeHeader(), 'reason-code header should be exposed');
assertSameValue('srx_test', $result->correlationId(), 'correlation id should be exposed');
assertSameValue(true, $result->toArray()['future_field']['kept'], 'unknown response fields should be preserved');
assertSameValue(false, array_key_exists('context_signals', $result->toArray()), 'context signals should not be emitted');

$limit = ValidationResult::limitExceeded('limit@example.com');
assertSameValue('review', $limit->decision(), 'silent limit fallback should be review');
assertSameValue(true, $limit->needsReview(), 'silent limit fallback should need review');
assertSameValue(false, $limit->isAllowed(), 'silent limit fallback must not allow');

$client = new SoryxaClient('https://soryxa.test', 'token');
$payloadMethod = new ReflectionMethod($client, 'validationPayload');
$payload = $payloadMethod->invoke($client, 'user@example.com', 'signup');

assertSameValue([
    'email' => 'user@example.com',
    'policy_key' => 'signup',
], $payload, 'validation payload should include email and optional policy key only');

echo "All Laravel SDK contract tests passed." . PHP_EOL;
