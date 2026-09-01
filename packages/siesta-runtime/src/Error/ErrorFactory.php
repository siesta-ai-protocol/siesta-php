<?php

declare(strict_types=1);

namespace Siesta\Runtime\Error;

final class ErrorFactory
{
    public static function libraryNotFound(string $library): SiestaError
    {
        return new SiestaError('LIBRARY_NOT_FOUND', "Library not found: {$library}", retryable: false);
    }

    public static function handleExpired(string $handle): SiestaError
    {
        return new SiestaError(
            'HANDLE_EXPIRED',
            "Handle expired or not found: {$handle}",
            retryable: true,
            suggestedFix: ['action' => 'recreate'],
        );
    }

    public static function methodNotFound(string $method, string $type): SiestaError
    {
        return new SiestaError(
            'METHOD_NOT_FOUND',
            "Method not found: {$type}::{$method}",
            retryable: false,
            docs: "{$type}#{$method}",
        );
    }

  /** @param array<string, mixed>|null $suggestedFix */
    public static function invalidArgument(string $message, ?string $field = null, ?array $suggestedFix = null): SiestaError
    {
        return new SiestaError('INVALID_ARGUMENT', $message, retryable: true, field: $field, suggestedFix: $suggestedFix);
    }

  /** @param array<string, mixed>|null $suggestedFix */
    public static function configInvalid(string $message, ?string $field = null, ?array $suggestedFix = null): SiestaError
    {
        return new SiestaError('CONFIG_INVALID', $message, retryable: true, field: $field, suggestedFix: $suggestedFix);
    }

    public static function internal(string $message): SiestaError
    {
        return new SiestaError('INTERNAL', $message, retryable: false);
    }
}
