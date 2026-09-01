<?php

declare(strict_types=1);

namespace Siesta\Carbon\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class SiestaFactory
{
    public function __construct(
        public readonly string $name,
        public readonly string $returns = 'DateTime',
        public readonly string $description = '',
    ) {
    }
}
