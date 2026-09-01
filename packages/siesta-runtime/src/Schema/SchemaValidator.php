<?php

declare(strict_types=1);

namespace Siesta\Runtime\Schema;

use Siesta\Runtime\Error\ErrorFactory;
use Siesta\Runtime\Error\SiestaError;

final class SchemaValidator
{
  /** @param array<string, mixed> $schema */
  /** @param array<string, mixed> $data */
    public function validate(array $schema, array $data, string $context = 'arguments'): void
    {
        if (($schema['type'] ?? null) !== 'object') {
            return;
        }

        $properties = $schema['properties'] ?? [];
        $required = $schema['required'] ?? [];

        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                throw ErrorFactory::invalidArgument(
                    "Missing required {$context} field: {$field}",
                    $field,
                    [$field => $this->exampleValue($properties[$field] ?? [])],
                );
            }
        }

        foreach ($data as $field => $value) {
            if (!isset($properties[$field])) {
                continue;
            }

            $this->validateValue($field, $value, $properties[$field], $context);
        }
    }

  /** @param array<string, mixed> $propertySchema */
    private function validateValue(string $field, mixed $value, array $propertySchema, string $context): void
    {
        $type = $propertySchema['type'] ?? null;

        if ($type === 'string' && !is_string($value)) {
            throw ErrorFactory::invalidArgument("{$context}.{$field} must be a string", $field);
        }

        if ($type === 'integer' && !is_int($value)) {
            if (is_numeric($value) && (int) $value == $value) {
                return;
            }

            throw ErrorFactory::invalidArgument("{$context}.{$field} must be an integer", $field, [$field => 1]);
        }

        if ($type === 'boolean' && !is_bool($value)) {
            throw ErrorFactory::invalidArgument("{$context}.{$field} must be a boolean", $field);
        }

        if ($type === 'number' && !is_numeric($value)) {
            throw ErrorFactory::invalidArgument("{$context}.{$field} must be a number", $field);
        }

        if (isset($propertySchema['enum']) && !in_array($value, $propertySchema['enum'], true)) {
            throw ErrorFactory::invalidArgument(
                "{$context}.{$field} must be one of: " . implode(', ', $propertySchema['enum']),
                $field,
                validValues: $propertySchema['enum'],
            );
        }

        if (isset($propertySchema['minimum']) && is_numeric($value) && $value < $propertySchema['minimum']) {
            throw ErrorFactory::invalidArgument(
                "{$context}.{$field} must be >= {$propertySchema['minimum']}",
                $field,
                [$field => $propertySchema['minimum']],
            );
        }
    }

  /** @param array<string, mixed> $propertySchema */
    private function exampleValue(array $propertySchema): mixed
    {
        return match ($propertySchema['type'] ?? 'string') {
            'integer' => $propertySchema['minimum'] ?? 1,
            'boolean' => true,
            'number' => 1.0,
            default => $propertySchema['default'] ?? 'value',
        };
    }
}
