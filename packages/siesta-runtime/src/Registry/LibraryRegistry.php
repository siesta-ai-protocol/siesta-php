<?php

declare(strict_types=1);

namespace Siesta\Runtime\Registry;

use Siesta\Runtime\Contracts\LibraryAdapterInterface;
use Siesta\Runtime\Error\ErrorFactory;

final class LibraryRegistry
{
  /** @var array<string, LibraryAdapterInterface> */
    private array $libraries = [];

    public function register(LibraryAdapterInterface $adapter): void
    {
        $this->libraries[$adapter->getId()] = $adapter;
    }

    public function get(string $id): LibraryAdapterInterface
    {
        if (!isset($this->libraries[$id])) {
            throw ErrorFactory::libraryNotFound($id);
        }

        return $this->libraries[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->libraries[$id]);
    }

  /** @return list<array{id: string, name: string, version: string, manifest: string}> */
    public function discover(): array
    {
        $result = [];

        foreach ($this->libraries as $id => $adapter) {
            $manifest = $adapter->getManifest();
            $result[] = [
                'id' => $id,
                'name' => (string) ($manifest['library'] ?? $id),
                'version' => (string) ($manifest['version'] ?? '0.0.0'),
                'manifest' => $adapter->getManifestPath(),
            ];
        }

        return $result;
    }

  /** @return array<string, LibraryAdapterInterface> */
    public function all(): array
    {
        return $this->libraries;
    }
}
