<?php

namespace Elvesora\Soryxa\Responses;

class Check {
    public function __construct(
        public readonly string $name,
        public readonly string $status,
        public readonly string $message,
    ) {
    }

    public function passed(): bool {
        return $this->status === 'passed';
    }

    public function failed(): bool {
        return $this->status === 'failed';
    }

    public function isWarning(): bool {
        return $this->status === 'warning';
    }

    public function isError(): bool {
        return $this->status === 'error';
    }

    public function toArray(): array {
        return [
            'name' => $this->name,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
