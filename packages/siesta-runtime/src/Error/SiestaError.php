<?php

declare(strict_types=1);

namespace Siesta\Runtime\Error;

final class SiestaError extends \RuntimeException
{
  /** @param array<string, mixed>|null $suggestedFix */
  /** @param list<mixed>|null $validValues */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly bool $retryable = false,
        public readonly ?string $field = null,
        public readonly ?array $suggestedFix = null,
        public readonly ?array $validValues = null,
        public readonly ?string $docs = null,
    ) {
        parent::__construct($message);
    }

  /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'retryable' => $this->retryable,
            'field' => $this->field,
            'validValues' => $this->validValues,
            'suggestedFix' => $this->suggestedFix,
            'docs' => $this->docs,
        ], static fn ($value) => $value !== null);
    }
}
