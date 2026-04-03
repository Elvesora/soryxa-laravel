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
    public function validate(string $email): ValidationResult {
        try {
            $body = $this->post('/api/v1/validate', [
                'email' => $email,
            ]);

            return ValidationResult::fromResponse($body);
        } catch (UsageLimitException $e) {
            if ($this->silentOnLimit) {
                return ValidationResult::limitExceeded($email);
            }

            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    //  HTTP Transport
    // -------------------------------------------------------------------------

    protected function get(string $path, array $query = []): array {
        $response = $this->send('get', $path, $query);

        return $response->json();
    }

    protected function post(string $path, array $data = []): array {
        $response = $this->send('post', $path, $data);

        return $response->json();
    }

    protected function send(string $method, string $path, array $data = []): Response {
        try {
            $request = $this->request();

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

    protected function request(): PendingRequest {
        $pending = Http::withToken($this->token)
            ->timeout($this->timeout)
            ->acceptJson();

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
