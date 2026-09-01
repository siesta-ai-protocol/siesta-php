<?php

declare(strict_types=1);

namespace Siesta\Runtime;

use Siesta\Runtime\Discovery\AdapterFactory;
use Siesta\Runtime\Discovery\DiscoveredLibrary;
use Siesta\Runtime\Discovery\ManifestDiscovery;
use Siesta\Runtime\Registry\LibraryRegistry;
use Siesta\Runtime\Validation\ManifestValidator;

/**
 * Embeddable Siesta entry point. Discover manifest files in a project,
 * validate them, register adapters, and handle protocol calls in-process.
 */
final class SiestaKernel
{
  /** @param list<DiscoveredLibrary> $discovered */
    private function __construct(
        private readonly SiestaRuntime $runtime,
        private readonly array $discovered,
        private readonly string $projectRoot,
    ) {
    }

    public static function discover(string $projectRoot, ?string $schemaPath = null, ?string $sessionId = null): self
    {
        $projectRoot = rtrim($projectRoot, '/\\');
        $schemaPath ??= self::resolveSchemaPath($projectRoot);

        $validator = is_file($schemaPath) ? new ManifestValidator($schemaPath) : null;
        $discovery = new ManifestDiscovery($validator);
        $discovered = $discovery->discover($projectRoot);

        $registry = new LibraryRegistry();
        $adapterFactory = new AdapterFactory();

        foreach ($discovered as $library) {
            if (!$library->isExecutable()) {
                continue;
            }

            $registry->register($adapterFactory->create($library));
        }

        return new self(new SiestaRuntime($registry, sessionId: $sessionId), $discovered, $projectRoot);
    }

    private static function resolveSchemaPath(string $projectRoot): string
    {
        $candidates = [
            $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'siesta' . DIRECTORY_SEPARATOR . 'protocol' . DIRECTORY_SEPARATOR . 'spec' . DIRECTORY_SEPARATOR . 'manifest-schema.json',
            dirname($projectRoot) . DIRECTORY_SEPARATOR . 'siesta-protocol' . DIRECTORY_SEPARATOR . 'spec' . DIRECTORY_SEPARATOR . 'manifest-schema.json',
            $projectRoot . DIRECTORY_SEPARATOR . 'spec' . DIRECTORY_SEPARATOR . 'manifest-schema.json',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $candidates[1];
    }

  /** @param array<string, mixed> $params */
  /** @return array<string, mixed> */
    public function handle(string $method, array $params): array
    {
        if ($method === 'siesta.discover') {
            return $this->discoverWithValidation();
        }

        if ($method === 'siesta.validate') {
            return $this->validateAll();
        }

        return $this->runtime->handle($method, $params);
    }

    public function getRuntime(): SiestaRuntime
    {
        return $this->runtime;
    }

  /** @return list<DiscoveredLibrary> */
    public function getDiscoveredLibraries(): array
    {
        return $this->discovered;
    }

    public function getProjectRoot(): string
    {
        return $this->projectRoot;
    }

  /** @return array<string, mixed> */
    private function discoverWithValidation(): array
    {
        $registeredIds = array_column(
            $this->runtime->handle('siesta.discover', [])['libraries'] ?? [],
            'id',
        );

        $libraries = [];

        foreach ($this->discovered as $library) {
            $libraries[] = [
                'id' => $library->getId(),
                'name' => (string) ($library->manifest['library'] ?? $library->getId()),
                'version' => (string) ($library->manifest['version'] ?? '0.0.0'),
                'manifest' => $library->manifestPath,
                'package' => $library->packageName,
                'valid' => $library->valid,
                'executable' => $library->isExecutable(),
                'registered' => in_array($library->getId(), $registeredIds, true),
                'adapter' => $library->adapterClass,
                'validationErrors' => $library->validationErrors,
            ];
        }

        return [
            'siestaVersion' => '1.0',
            'sessionId' => $this->runtime->getSessionId(),
            'projectRoot' => $this->projectRoot,
            'libraries' => $libraries,
        ];
    }

  /** @return array<string, mixed> */
    private function validateAll(): array
    {
        $results = [];

        foreach ($this->discovered as $library) {
            $results[] = [
                'id' => $library->getId(),
                'manifest' => $library->manifestPath,
                'valid' => $library->valid,
                'executable' => $library->isExecutable(),
                'errors' => $library->validationErrors,
            ];
        }

        return [
            'siestaVersion' => '1.0',
            'valid' => array_all($results, static fn (array $r): bool => $r['valid']),
            'libraries' => $results,
        ];
    }
}
