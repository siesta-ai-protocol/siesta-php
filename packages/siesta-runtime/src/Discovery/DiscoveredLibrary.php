<?php

declare(strict_types=1);

namespace Siesta\Runtime\Discovery;

final class DiscoveredLibrary
{
  /** @param list<array{property: string, message: string}> $validationErrors */
    public function __construct(
        public readonly string $manifestPath,
      /** @var array<string, mixed> */
        public readonly array $manifest,
        public readonly bool $valid,
        public readonly array $validationErrors = [],
        public readonly ?string $adapterClass = null,
        public readonly ?string $packageName = null,
    ) {
    }

    public function getId(): string
    {
        return (string) ($this->manifest['library'] ?? basename(dirname($this->manifestPath)));
    }

    public function isExecutable(): bool
    {
        return $this->valid && $this->adapterClass !== null && class_exists($this->adapterClass);
    }
}
