<?php

declare(strict_types=1);

namespace Siesta\Runtime;

use Siesta\Runtime\Contracts\LibraryAdapterInterface;
use Siesta\Runtime\Error\ErrorFactory;
use Siesta\Runtime\Error\SiestaError;
use Siesta\Runtime\Registry\LibraryRegistry;
use Siesta\Runtime\Schema\SchemaValidator;
use Siesta\Runtime\Session\HandleRegistry;

final class SiestaRuntime
{
    private string $sessionId;

    public function __construct(
        private readonly LibraryRegistry $registry,
        private readonly HandleRegistry $handles = new HandleRegistry(),
        private readonly SchemaValidator $validator = new SchemaValidator(),
        ?string $sessionId = null,
    ) {
        $this->sessionId = $sessionId ?? 'sess_' . bin2hex(random_bytes(8));
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

  /** @param array<string, mixed> $params */
  /** @return array<string, mixed> */
    public function handle(string $method, array $params): array
    {
        try {
            return match ($method) {
                'siesta.discover' => $this->discover(),
                'siesta.introspect' => $this->introspect($params),
                'siesta.configure' => $this->configure($params),
                'siesta.create' => $this->create($params),
                'siesta.invoke' => $this->invoke($params),
                'siesta.release' => $this->release($params),
                'siesta.batch' => $this->batch($params),
                'siesta.describe' => $this->describe($params),
                default => throw ErrorFactory::internal("Unknown method: {$method}"),
            };
        } catch (SiestaError $e) {
            return ['error' => $e->toArray()];
        } catch (\InvalidArgumentException $e) {
            if (str_starts_with($e->getMessage(), 'hdl_')) {
                throw ErrorFactory::handleExpired($e->getMessage());
            }

            throw $e;
        }
    }

  /** @return array<string, mixed> */
    private function discover(): array
    {
        return [
            'siestaVersion' => '1.0',
            'sessionId' => $this->sessionId,
            'libraries' => $this->registry->discover(),
        ];
    }

  /** @param array<string, mixed> $params */
  /** @return array<string, mixed> */
    private function introspect(array $params): array
    {
        $library = $this->requireLibrary($params);
        $adapter = $this->registry->get($library);

        return [
            'siestaVersion' => '1.0',
            'manifest' => $adapter->getManifest(),
        ];
    }

  /** @param array<string, mixed> $params */
  /** @return array<string, mixed> */
    private function configure(array $params): array
    {
        $library = $this->requireLibrary($params);
        $adapter = $this->registry->get($library);
        $manifest = $adapter->getManifest();
        $settings = $params['settings'] ?? [];

        if (!is_array($settings)) {
            throw ErrorFactory::configInvalid('settings must be an object');
        }

        $configSchema = [
            'type' => 'object',
            'properties' => $manifest['config'] ?? [],
            'required' => [],
        ];

        $this->validator->validate($configSchema, $settings, 'config');
        $adapter->configure($settings);

        return [
            'siestaVersion' => '1.0',
            'library' => $library,
            'config' => $adapter->getConfig(),
        ];
    }

  /** @param array<string, mixed> $params */
  /** @return array<string, mixed> */
    private function create(array $params): array
    {
        $library = $this->requireLibrary($params);
        $factory = $params['factory'] ?? null;
        $args = $params['args'] ?? [];

        if (!is_string($factory)) {
            throw ErrorFactory::invalidArgument('factory is required');
        }

        if (!is_array($args)) {
            throw ErrorFactory::invalidArgument('args must be an object');
        }

        $adapter = $this->registry->get($library);
        $manifest = $adapter->getManifest();
        $factoryDef = $manifest['factories'][$factory] ?? null;

        if ($factoryDef === null) {
            throw ErrorFactory::methodNotFound($factory, 'factory');
        }

        $this->validator->validate($factoryDef['params'] ?? ['type' => 'object', 'properties' => []], $args);

        $instance = $adapter->create($factory, $args);
        $type = $adapter->getType($instance);
        $handle = $this->handles->create($this->sessionId, $library, $type, $instance);

        return [
            'siestaVersion' => '1.0',
            'handle' => $handle,
            'type' => $type,
            'snapshot' => $adapter->snapshot($instance),
        ];
    }

  /** @param array<string, mixed> $params */
  /** @return array<string, mixed> */
    private function invoke(array $params): array
    {
        $handle = $params['handle'] ?? null;
        $method = $params['method'] ?? null;
        $args = $params['args'] ?? [];

        if (!is_string($handle) || !is_string($method)) {
            throw ErrorFactory::invalidArgument('handle and method are required');
        }

        if (!is_array($args)) {
            throw ErrorFactory::invalidArgument('args must be an object');
        }

        try {
            $resolved = $this->handles->resolve($handle);
        } catch (\InvalidArgumentException) {
            throw ErrorFactory::handleExpired($handle);
        }

        $adapter = $this->registry->get($resolved['library']);
        $manifest = $adapter->getManifest();
        $typeDef = $manifest['types'][$resolved['type']] ?? null;
        $methodDef = $typeDef['methods'][$method] ?? null;

        if ($methodDef === null) {
            throw ErrorFactory::methodNotFound($method, $resolved['type']);
        }

        $this->validator->validate($methodDef['params'] ?? ['type' => 'object', 'properties' => []], $args);

        $contextInstance = null;
        if (isset($args['otherHandle']) && is_string($args['otherHandle'])) {
            $other = $this->handles->resolve($args['otherHandle']);
            $contextInstance = $other['instance'];
        }

        $result = $adapter->invoke($resolved['instance'], $method, $args, $contextInstance);
        $returns = $methodDef['returns'] ?? 'void';

        if ($returns === 'void' || $result === null) {
            return ['siestaVersion' => '1.0', 'handle' => $handle, 'snapshot' => $adapter->snapshot($resolved['instance'])];
        }

        if (is_object($result)) {
            $newHandle = $this->handles->create($this->sessionId, $resolved['library'], $adapter->getType($result), $result);

            return [
                'siestaVersion' => '1.0',
                'handle' => $newHandle,
                'type' => $adapter->getType($result),
                'snapshot' => $adapter->snapshot($result),
            ];
        }

        return [
            'siestaVersion' => '1.0',
            'value' => $result,
            'type' => $returns,
        ];
    }

  /** @param array<string, mixed> $params */
  /** @return array<string, mixed> */
    private function release(array $params): array
    {
        $handles = $params['handles'] ?? [];

        if (!is_array($handles)) {
            throw ErrorFactory::invalidArgument('handles must be an array');
        }

        $released = $this->handles->release(array_values(array_filter($handles, 'is_string')));

        return [
            'siestaVersion' => '1.0',
            'released' => $released,
        ];
    }

  /** @param array<string, mixed> $params */
  /** @return array<string, mixed> */
    private function batch(array $params): array
    {
        $operations = $params['operations'] ?? [];

        if (!is_array($operations)) {
            throw ErrorFactory::invalidArgument('operations must be an array');
        }

        $results = [];

        foreach ($operations as $operation) {
            if (!is_array($operation)) {
                throw ErrorFactory::invalidArgument('Each operation must be an object');
            }

            $opMethod = $operation['method'] ?? null;
            $opParams = $operation['params'] ?? [];

            if (!is_string($opMethod) || !is_array($opParams)) {
                throw ErrorFactory::invalidArgument('Each operation requires method and params');
            }

            $result = $this->handle($opMethod, $opParams);

            if (isset($result['error'])) {
                return [
                    'siestaVersion' => '1.0',
                    'error' => $result['error'],
                    'completed' => count($results),
                ];
            }

            $results[] = $result;
        }

        return [
            'siestaVersion' => '1.0',
            'results' => $results,
        ];
    }

  /** @param array<string, mixed> $params */
  /** @return array<string, mixed> */
    private function describe(array $params): array
    {
        $library = $this->requireLibrary($params);
        $capability = $params['capability'] ?? null;
        $adapter = $this->registry->get($library);
        $manifest = $adapter->getManifest();

        if (!is_string($capability)) {
            throw ErrorFactory::invalidArgument('capability is required');
        }

        if (isset($manifest['factories'][$capability])) {
            return [
                'siestaVersion' => '1.0',
                'capability' => $capability,
                'kind' => 'factory',
                'definition' => $manifest['factories'][$capability],
            ];
        }

        foreach ($manifest['types'] ?? [] as $typeName => $typeDef) {
            if (isset($typeDef['methods'][$capability])) {
                return [
                    'siestaVersion' => '1.0',
                    'capability' => $capability,
                    'kind' => 'method',
                    'type' => $typeName,
                    'definition' => $typeDef['methods'][$capability],
                ];
            }
        }

        throw ErrorFactory::methodNotFound($capability, $library);
    }

  /** @param array<string, mixed> $params */
    private function requireLibrary(array $params): string
    {
        $library = $params['library'] ?? null;

        if (!is_string($library)) {
            throw ErrorFactory::invalidArgument('library is required');
        }

        return $library;
    }
}
