<?php

namespace Elvesora\Soryxa\Responses;

class ValidationResult {
    public function __construct(
        public readonly string $decision,
        public readonly string $reasonCode,
        public readonly string $decisionMessage,
        public readonly array $decisionReasons,
        public readonly array $checks,
        public readonly int $score,
        public readonly ?array $rawData,
        public readonly Usage $usage,
        public readonly ?string $policyKey = null,
        public readonly ?string $customerMessage = null,
        public readonly mixed $autoResolution = null,
        public readonly array $rollout = [],
        public readonly ?int $baseScore = null,
        public readonly array $scoreAdjustments = [],
        public readonly ?array $upstream = null,
        public readonly array $data = [],
        public readonly array $headers = [],
    ) {
    }

    public static function limitExceeded(string $email): self {
        $parts = explode('@', $email, 2);
        $message = 'Usage limit exceeded. Review or retry after credits are available.';
        $rawData = [
            'email' => $email,
            'username' => $parts[0] ?? null,
            'domain' => $parts[1] ?? null,
            'first_name' => null,
            'last_name' => null,
            'score' => null,
            'classification' => null,
            'checks' => [
                'syntax' => null,
                'tld' => null,
                'tld_valid' => null,
                'domain_mx' => null,
                'smtp' => null,
                'disposable' => null,
                'free_provider' => null,
                'bogus' => null,
                'bogus_username' => null,
                'role_account' => null,
                'catch_all' => null,
                'domain_registered' => null,
                'is_newly_registered_domain' => null,
            ],
        ];
        $data = [
            'policy_key' => 'default',
            'decision' => 'review',
            'reason_code' => 'LIMIT_EXCEEDED',
            'decision_message' => $message,
            'customer_message' => $message,
            'decision_reasons' => ['Usage limit exceeded.'],
            'auto_resolution' => null,
            'rollout' => [],
            'checks' => [],
            'score' => 0,
            'base_score' => 0,
            'score_adjustments' => [],
            'raw_data' => $rawData,
            'upstream' => null,
        ];

        return new self(
            decision: 'review',
            reasonCode: 'LIMIT_EXCEEDED',
            decisionMessage: $message,
            decisionReasons: ['Usage limit exceeded.'],
            checks: [],
            score: 0,
            rawData: $rawData,
            usage: new Usage(remaining: 0, limit: 0),
            policyKey: 'default',
            customerMessage: $message,
            autoResolution: null,
            rollout: [],
            baseScore: 0,
            scoreAdjustments: [],
            upstream: null,
            data: $data,
        );
    }

    public static function fromResponse(array $body, array $headers = []): self {
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];
        unset($data['context_signals']);

        return new self(
            decision: (string) ($data['decision'] ?? 'review'),
            reasonCode: (string) ($data['reason_code'] ?? 'UNKNOWN'),
            decisionMessage: (string) ($data['decision_message'] ?? ''),
            decisionReasons: is_array($data['decision_reasons'] ?? null) ? $data['decision_reasons'] : [],
            checks: array_map(
                fn (array $check) => new Check(
                    name: (string) ($check['name'] ?? ''),
                    status: (string) ($check['status'] ?? ''),
                    message: (string) ($check['message'] ?? ''),
                ),
                is_array($data['checks'] ?? null) ? $data['checks'] : [],
            ),
            score: (int) ($data['score'] ?? 0),
            rawData: is_array($data['raw_data'] ?? null) ? $data['raw_data'] : null,
            usage: Usage::fromArray(is_array($body['usage'] ?? null) ? $body['usage'] : []),
            policyKey: isset($data['policy_key']) ? (string) $data['policy_key'] : null,
            customerMessage: isset($data['customer_message']) ? (string) $data['customer_message'] : null,
            autoResolution: $data['auto_resolution'] ?? null,
            rollout: is_array($data['rollout'] ?? null) ? $data['rollout'] : [],
            baseScore: isset($data['base_score']) ? (int) $data['base_score'] : null,
            scoreAdjustments: is_array($data['score_adjustments'] ?? null) ? $data['score_adjustments'] : [],
            upstream: is_array($data['upstream'] ?? null) ? $data['upstream'] : null,
            data: $data,
            headers: $headers,
        );
    }

    public function decision(): string {
        return $this->decision;
    }

    public function reasonCode(): string {
        return $this->reasonCode;
    }

    public function decisionMessage(): string {
        return $this->decisionMessage;
    }

    public function customerMessage(): ?string {
        return $this->customerMessage;
    }

    public function decisionReasons(): array {
        return $this->decisionReasons;
    }

    public function policyKey(): ?string {
        return $this->policyKey;
    }

    public function score(): int {
        return $this->score;
    }

    public function baseScore(): ?int {
        return $this->baseScore;
    }

    public function scoreAdjustments(): array {
        return $this->scoreAdjustments;
    }

    public function autoResolution(): mixed {
        return $this->autoResolution;
    }

    public function rollout(): array {
        return $this->rollout;
    }

    public function upstream(): ?array {
        return $this->upstream;
    }

    public function data(): array {
        return $this->data;
    }

    public function headers(): array {
        return $this->headers;
    }

    public function header(string $name): ?string {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp((string) $headerName, $name) === 0) {
                return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
            }
        }

        return null;
    }

    public function reasonCodeHeader(): ?string {
        return $this->header('X-Soryxa-Reason-Code');
    }

    public function correlationId(): ?string {
        return $this->header('X-Soryxa-Correlation-Id');
    }

    public function isAllowed(): bool {
        return $this->decision === 'allow';
    }

    public function isBlocked(): bool {
        return $this->decision === 'block';
    }

    public function needsReview(): bool {
        return $this->decision === 'review';
    }

    public function isLimitExceeded(): bool {
        return $this->reasonCode === 'LIMIT_EXCEEDED';
    }

    public function email(): ?string {
        return $this->rawData['email'] ?? null;
    }

    public function username(): ?string {
        return $this->rawData['username'] ?? null;
    }

    public function domain(): ?string {
        return $this->rawData['domain'] ?? null;
    }

    public function firstName(): ?string {
        return $this->rawData['first_name'] ?? null;
    }

    public function lastName(): ?string {
        return $this->rawData['last_name'] ?? null;
    }

    public function classification(): ?string {
        return $this->rawData['classification'] ?? null;
    }

    public function isSyntaxValid(): bool {
        return $this->rawData['checks']['syntax'] ?? false;
    }

    public function hasMxRecords(): bool {
        return $this->rawData['checks']['domain_mx'] ?? false;
    }

    public function isSmtpValid(): bool {
        return ($this->rawData['checks']['smtp'] ?? '') === 'valid';
    }

    public function isDisposable(): bool {
        return $this->rawData['checks']['disposable'] ?? false;
    }

    public function isFreeProvider(): bool {
        return $this->rawData['checks']['free_provider'] ?? false;
    }

    public function isFree(): bool {
        return $this->isFreeProvider();
    }

    public function isRoleAccount(): bool {
        return $this->rawData['checks']['role_account'] ?? false;
    }

    public function isRole(): bool {
        return $this->isRoleAccount();
    }

    public function isBogus(): bool {
        return $this->rawData['checks']['bogus_username'] ?? false;
    }

    public function isCatchAll(): bool {
        return $this->rawData['checks']['catch_all'] ?? false;
    }

    public function isDomainRegistered(): bool {
        return $this->rawData['checks']['domain_registered'] ?? false;
    }

    public function isNewlyRegisteredDomain(): bool {
        return $this->rawData['checks']['is_newly_registered_domain'] ?? false;
    }

    public function getCheck(string $name): ?Check {
        foreach ($this->checks as $check) {
            if (strcasecmp($check->name, $name) === 0) {
                return $check;
            }
        }

        return null;
    }

    public function passedChecks(): array {
        return array_values(array_filter($this->checks, fn (Check $c) => $c->passed()));
    }

    public function failedChecks(): array {
        return array_values(array_filter($this->checks, fn (Check $c) => $c->failed()));
    }

    public function warningChecks(): array {
        return array_values(array_filter($this->checks, fn (Check $c) => $c->isWarning()));
    }

    public function toArray(): array {
        return array_merge($this->data, [
            'policy_key' => $this->policyKey,
            'decision' => $this->decision,
            'reason_code' => $this->reasonCode,
            'decision_message' => $this->decisionMessage,
            'customer_message' => $this->customerMessage,
            'decision_reasons' => $this->decisionReasons,
            'auto_resolution' => $this->autoResolution,
            'rollout' => $this->rollout,
            'checks' => array_map(fn (Check $c) => $c->toArray(), $this->checks),
            'score' => $this->score,
            'base_score' => $this->baseScore,
            'score_adjustments' => $this->scoreAdjustments,
            'raw_data' => $this->rawData,
            'upstream' => $this->upstream,
            'usage' => $this->usage->toArray(),
            'headers' => $this->headers,
        ]);
    }
}
