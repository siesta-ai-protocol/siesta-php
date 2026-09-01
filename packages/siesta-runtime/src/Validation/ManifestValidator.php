<?php

declare(strict_types=1);

namespace Siesta\Runtime\Validation;

use JsonSchema\Validator;

final class ManifestValidator
{
    public function __construct(private readonly string $schemaPath)
    {
    }

  /** @return list<array{property: string, message: string}> */
    public function validateFile(string $manifestPath): array
    {
        if (!is_file($manifestPath)) {
            return [['property' => '', 'message' => "Manifest not found: {$manifestPath}"]];
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), false);

        if ($manifest === null) {
            return [['property' => '', 'message' => 'Invalid JSON']];
        }

        if (!is_file($this->schemaPath)) {
            return [];
        }

        $schema = json_decode((string) file_get_contents($this->schemaPath), false);
        $validator = new Validator();
        $validator->validate($manifest, $schema);

        if ($validator->isValid()) {
            return [];
        }

        return array_map(
            static fn (array $error): array => [
                'property' => (string) ($error['property'] ?? ''),
                'message' => (string) ($error['message'] ?? 'Validation error'),
            ],
            $validator->getErrors(),
        );
    }

    public function isValid(string $manifestPath): bool
    {
        return $this->validateFile($manifestPath) === [];
    }
}
