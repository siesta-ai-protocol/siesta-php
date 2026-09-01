<?php

declare(strict_types=1);

namespace Siesta\Cli;

use Siesta\Runtime\SiestaKernel;

final class RuntimeFactory
{
    public static function kernel(?string $sessionId = null): SiestaKernel
    {
        return SiestaKernel::discover(dirname(__DIR__, 3), sessionId: $sessionId);
    }
}
