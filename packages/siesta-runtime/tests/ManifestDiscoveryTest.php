<?php

declare(strict_types=1);

namespace Siesta\Runtime\Tests;

use PHPUnit\Framework\TestCase;
use Siesta\Runtime\SiestaKernel;

final class ManifestDiscoveryTest extends TestCase
{
    public function test_discovers_carbon_manifest_in_project(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $kernel = SiestaKernel::discover($projectRoot);
        $result = $kernel->handle('siesta.discover', []);

        $this->assertNotEmpty($result['libraries']);

        $carbon = array_values(array_filter(
            $result['libraries'],
            static fn (array $lib): bool => $lib['id'] === 'siesta-carbon',
        ))[0] ?? null;

        $this->assertNotNull($carbon);
        $this->assertTrue($carbon['valid']);
        $this->assertTrue($carbon['executable']);
        $this->assertTrue($carbon['registered']);
    }

    public function test_validate_all_manifests(): void
    {
        $kernel = SiestaKernel::discover(dirname(__DIR__, 3));
        $result = $kernel->handle('siesta.validate', []);

        $this->assertTrue($result['valid']);
    }
}
