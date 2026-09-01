<?php

declare(strict_types=1);

namespace Siesta\Carbon\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class SiestaMethod
{
    public function __construct(
        public readonly string $returns,
        public readonly string $description = '',
    ) {
    }
}
