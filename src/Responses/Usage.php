<?php

namespace Elvesora\Soryxa\Responses;

class Usage {
    public function __construct(
        public readonly int $remaining,
        public readonly int $limit,
    ) {
    }

    public static function fromArray(array $data): self {
        return new self(
            remaining: $data['remaining'] ?? 0,
            limit: $data['limit'] ?? 0,
        );
    }

    public function usagePercent(): float {
        return $this->limit > 0
            ? round((($this->limit - $this->remaining) / $this->limit) * 100, 2)
            : 0;
    }

    public function toArray(): array {
        return [
            'remaining' => $this->remaining,
            'limit' => $this->limit,
        ];
    }
}
