<?php

declare(strict_types=1);

namespace Siesta\Runtime\Manifest;

use Siesta\Runtime\Error\ErrorFactory;

final class ManifestLoader
{
  /** @var array<string, array<string, mixed>> */
    private array $cache = [];

  /** @return array<string, mixed> */
    public function load(string $path): array
    {
        if (isset($this->cache[$path])) {
            return $this->cache[$path];
        }

        if (!is_file($path)) {
            throw ErrorFactory::internal("Manifest not found: {$path}");
        }

        $content = file_get_contents($path);
        $manifest = json_decode($content ?: '', true);

        if (!is_array($manifest)) {
            throw ErrorFactory::internal("Invalid manifest JSON: {$path}");
        }

        return $this->cache[$path] = $manifest;
    }
}
