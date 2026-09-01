<?php

declare(strict_types=1);

namespace Siesta\Carbon\Tests;

use PHPUnit\Framework\TestCase;
use Siesta\Runtime\SiestaKernel;

final class CarbonAdapterTest extends TestCase
{
    private SiestaKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = SiestaKernel::discover(dirname(__DIR__, 3), sessionId: 'carbon-test');
    }

    public function test_all_factories_return_handles(): void
    {
        $factories = [
            'now' => [],
            'parse' => ['input' => '2026-06-15'],
            'createFromFormat' => ['format' => 'Y-m-d', 'time' => '2026-06-15'],
            'createFromDate' => ['year' => 2026, 'month' => 6, 'day' => 15],
            'yesterday' => [],
            'tomorrow' => [],
        ];

        foreach ($factories as $factory => $args) {
            $result = $this->kernel->handle('siesta.create', [
                'library' => 'siesta-carbon',
                'factory' => $factory,
                'args' => $args,
            ]);

            $this->assertArrayHasKey('handle', $result, "Factory {$factory} failed");
            $this->assertArrayHasKey('iso', $result['snapshot']);
        }
    }

    public function test_timezone_config_propagates(): void
    {
        $this->kernel->handle('siesta.configure', [
            'library' => 'siesta-carbon',
            'settings' => ['defaultTimezone' => 'Europe/London'],
        ]);

        $created = $this->kernel->handle('siesta.create', [
            'library' => 'siesta-carbon',
            'factory' => 'now',
            'args' => [],
        ]);

        $this->assertSame('Europe/London', $created['snapshot']['timezone']);
    }
}
