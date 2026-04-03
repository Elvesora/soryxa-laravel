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
    ) {
    }

    public static function limitExceeded(string $email): self {
        $parts = explode('@', $email, 2);

        return new self(
            decision: 'allow',
            reasonCode: 'LIMIT_EXCEEDED',
            decisionMessage: 'You have exceeded your monthly usage limit.',
            decisionReasons: [],
            checks: [],
            score: 0,
            rawData: [
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
            ],
            usage: new Usage(remaining: 0, limit: 0),
        );
    }

    public static function fromResponse(array $body): self {
        $data = $body['data'];

        return new self(
            decision: $data['decision'],
            reasonCode: $data['reason_code'] ?? 'UNKNOWN',
            decisionMessage: $data['decision_message'] ?? '',
            decisionReasons: $data['decision_reasons'] ?? [],
            checks: array_map(
                fn (array $check) => new Check(
                    name: $check['name'],
                    status: $check['status'],
                    message: $check['message'] ?? '',
                ),
                $data['checks'] ?? [],
            ),
            score: $data['score'] ?? 0,
            rawData: $data['raw_data'] ?? null,
            usage: Usage::fromArray($body['usage']),
        );
    }

    // ── Decision helpers ──────────────────────────────────────

    public function decision(): string {
        return $this->decision;
    }

    public function reasonCode(): string {
        return $this->reasonCode;
    }

    public function decisionMessage(): string {
        return $this->decisionMessage;
    }

    public function decisionReasons(): array {
        return $this->decisionReasons;
    }

    public function score(): int {
        return $this->score;
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

    // ── Email & identity accessors ───────────────────────────

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

    // ── Boolean check helpers ────────────────────────────────

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

    // ── Check collection helpers ─────────────────────────────

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

    // ── Serialization ────────────────────────────────────────

    public function toArray(): array {
        return [
            'decision' => $this->decision,
            'reason_code' => $this->reasonCode,
            'decision_message' => $this->decisionMessage,
            'decision_reasons' => $this->decisionReasons,
            'checks' => array_map(fn (Check $c) => $c->toArray(), $this->checks),
            'score' => $this->score,
            'raw_data' => $this->rawData,
            'usage' => $this->usage->toArray(),
        ];
    }
}
