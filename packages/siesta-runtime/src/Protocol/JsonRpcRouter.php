<?php

declare(strict_types=1);

namespace Siesta\Runtime\Protocol;

use Siesta\Runtime\Error\ErrorFactory;
use Siesta\Runtime\Error\SiestaError;
use Siesta\Runtime\SiestaRuntime;

final class JsonRpcRouter
{
    public function __construct(private readonly SiestaRuntime $runtime)
    {
    }

  /** @return array<string, mixed> */
    public function process(string $input): array
    {
        $payload = json_decode($input, true);

        if (!is_array($payload)) {
            return $this->errorResponse(null, -32700, 'Parse error');
        }

        $id = $payload['id'] ?? null;
        $method = $payload['method'] ?? null;
        $params = $payload['params'] ?? [];

        if (($payload['jsonrpc'] ?? '') !== '2.0' || !is_string($method)) {
            return $this->errorResponse($id, -32600, 'Invalid Request');
        }

        if (!is_array($params)) {
            return $this->errorResponse($id, -32602, 'Invalid params');
        }

        try {
            $result = $this->runtime->handle($method, $params);

            if (isset($result['error'])) {
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => -32000,
                        'message' => (string) $result['error']['message'],
                        'data' => $result['error'],
                    ],
                ];
            }

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result,
            ];
        } catch (SiestaError $e) {
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32000,
                    'message' => $e->getMessage(),
                    'data' => $e->toArray(),
                ],
            ];
        } catch (\Throwable $e) {
            return $this->errorResponse($id, -32603, $e->getMessage());
        }
    }

  /** @return array<string, mixed> */
    private function errorResponse(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
