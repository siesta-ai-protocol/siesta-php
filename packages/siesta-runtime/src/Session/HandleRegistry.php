<?php

declare(strict_types=1);

namespace Siesta\Runtime\Session;

final class HandleRegistry
{
    private int $counter = 0;

  /** @var array<string, array{library: string, type: string, instance: object, sessionId: string}> */
    private array $handles = [];

    public function create(string $sessionId, string $library, string $type, object $instance): string
    {
        $this->counter++;
        $handle = 'hdl_' . $this->counter;

        $this->handles[$handle] = [
            'library' => $library,
            'type' => $type,
            'instance' => $instance,
            'sessionId' => $sessionId,
        ];

        return $handle;
    }

  /** @return array{library: string, type: string, instance: object, sessionId: string} */
    public function resolve(string $handle): array
    {
        if (!isset($this->handles[$handle])) {
            throw new \InvalidArgumentException($handle);
        }

        return $this->handles[$handle];
    }

    public function has(string $handle): bool
    {
        return isset($this->handles[$handle]);
    }

  /** @param list<string> $handles */
    public function release(array $handles): int
    {
        $released = 0;

        foreach ($handles as $handle) {
            if (isset($this->handles[$handle])) {
                unset($this->handles[$handle]);
                $released++;
            }
        }

        return $released;
    }

    public function clear(): void
    {
        $this->handles = [];
        $this->counter = 0;
    }
}
