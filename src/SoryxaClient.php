<?php

namespace Elvesora\Soryxa;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Elvesora\Soryxa\Exceptions\ConnectionException;
use Elvesora\Soryxa\Exceptions\SoryxaException;
use Elvesora\Soryxa\Exceptions\UsageLimitException;
use Elvesora\Soryxa\Responses\ValidationResult;

class SoryxaClient {
    protected string $baseUrl;
    protected string $token;
    protected int $timeout;
    protected int $retries;
    protected int $retryDelay;
    protected bool $silentOnLimit;

    public function __construct(string $baseUrl, string $token, int $timeout = 30, int $retries = 0, int $retryDelay = 100, bool $silentOnLimit = false) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
        $this->timeout = $timeout;
        $this->retries = $retries;
        $this->retryDelay = $retryDelay;
        $this->silentOnLimit = $silentOnLimit;
    }

    // -------------------------------------------------------------------------
    //  Validation
    // -------------------------------------------------------------------------

    /**
     * Validate a single email address.
     *
     * @throws SoryxaException
     */
    public function validate(
        string $email,
        ?string $policyKey = null,
        array $headers = [],
    ): ValidationResult {
        try {
            $response = $this->send(
                'post',
                '/api/v1/validate',
                $this->validationPayload($email, $policyKey),
                $headers,
            );

            return ValidationResult::fromResponse(
                $response->json() ?? [],
                $this->validationHeaders($response),
            );
        } catch (UsageLimitException $e) {
            if ($this->silentOnLimit) {
                return ValidationResult::limitExceeded($email);
            }

            throw $e;
        }
    }

    protected function validationPayload(string $email, ?string $policyKey = null): array {
        $payload = ['email' => $email];

        if ($policyKey !== null) {
            $payload['policy_key'] = $policyKey;
        }

        return $payload;
    }

    protected function validationHeaders(Response $response): array {
        $headers = [];

        foreach (['X-Soryxa-Reason-Code', 'X-Soryxa-Correlation-Id'] as $name) {
            $value = $response->header($name);

            if ($value !== null) {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    // -------------------------------------------------------------------------
    //  HTTP Transport
    // -------------------------------------------------------------------------

    protected function get(string $path, array $query = [], array $headers = []): array {
        $response = $this->send('get', $path, $query, $headers);

        return $response->json();
    }

    protected function post(string $path, array $data = [], array $headers = []): array {
        $response = $this->send('post', $path, $data, $headers);

        return $response->json();
    }

    protected function send(string $method, string $path, array $data = [], array $headers = []): Response {
        try {
            $request = $this->request($headers);

            $response = $method === 'get'
                ? $request->get($this->url($path), $data)
                : $request->post($this->url($path), $data);
        } catch (\Exception $e) {
            throw ConnectionException::fromException($e);
        }

        if ($response->failed()) {
            $this->handleErrorResponse($response);
        }

        return $response;
    }

    protected function request(array $headers = []): PendingRequest {
        $pending = Http::withToken($this->token)
            ->timeout($this->timeout)
            ->acceptJson();

        if ($headers !== []) {
            $pending = $pending->withHeaders($headers);
        }

        if ($this->retries > 0) {
            $pending->retry($this->retries, $this->retryDelay, fn ($e, $request) => $e->response?->status() >= 500);
        }

        return $pending;
    }

    protected function url(string $path): string {
        return $this->baseUrl . $path;
    }

    /**
     * @throws SoryxaException
     */
    protected function handleErrorResponse(Response $response): never {
        $body = $response->json() ?? [];
        $status = $response->status();

        throw SoryxaException::fromResponse($status, $body);
    }
}
