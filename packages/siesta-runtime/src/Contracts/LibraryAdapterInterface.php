<?php

declare(strict_types=1);

namespace Siesta\Runtime\Contracts;

interface LibraryAdapterInterface
{
    public function getId(): string;

    public function getManifestPath(): string;

  /** @return array<string, mixed> */
    public function getConfig(): array;

  /** @param array<string, mixed> $settings */
    public function configure(array $settings): void;

  /** @param array<string, mixed> $args */
    public function create(string $factory, array $args): object;

  /** @param array<string, mixed> $args */
    public function invoke(object $instance, string $method, array $args, ?object $contextInstance = null): mixed;

  /** @return array<string, mixed> */
    public function snapshot(object $instance): array;

    public function getType(object $instance): string;

  /** @return array<string, mixed> */
    public function getManifest(): array;
}
