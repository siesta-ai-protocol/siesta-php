<?php

declare(strict_types=1);

namespace Siesta\Runtime\Tests;

use PHPUnit\Framework\TestCase;
use Siesta\Runtime\SiestaKernel;

final class SiestaRuntimeTest extends TestCase
{
    private SiestaKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = SiestaKernel::discover(dirname(__DIR__, 3), sessionId: 'test');
    }

    public function test_discover_lists_libraries(): void
    {
        $result = $this->kernel->handle('siesta.discover', []);

        $this->assertSame('1.0', $result['siestaVersion']);
        $this->assertCount(1, $result['libraries']);
        $this->assertSame('siesta-carbon', $result['libraries'][0]['id']);
        $this->assertTrue($result['libraries'][0]['valid']);
    }

    public function test_create_invoke_release_lifecycle(): void
    {
        $created = $this->kernel->handle('siesta.create', [
            'library' => 'siesta-carbon',
            'factory' => 'now',
            'args' => [],
        ]);

        $this->assertArrayHasKey('handle', $created);
        $this->assertArrayHasKey('snapshot', $created);

        $formatted = $this->kernel->handle('siesta.invoke', [
            'handle' => $created['handle'],
            'method' => 'format',
            'args' => ['pattern' => 'Y'],
        ]);

        $this->assertArrayHasKey('value', $formatted);

        $released = $this->kernel->handle('siesta.release', [
            'handles' => [$created['handle']],
        ]);

        $this->assertSame(1, $released['released']);

        $expired = $this->kernel->handle('siesta.invoke', [
            'handle' => $created['handle'],
            'method' => 'format',
            'args' => ['pattern' => 'Y'],
        ]);

        $this->assertSame('HANDLE_EXPIRED', $expired['error']['code']);
        $this->assertTrue($expired['error']['retryable']);
    }

    public function test_configure_validates_settings(): void
    {
        $result = $this->kernel->handle('siesta.configure', [
            'library' => 'siesta-carbon',
            'settings' => ['defaultTimezone' => 'Asia/Riyadh'],
        ]);

        $this->assertSame('Asia/Riyadh', $result['config']['defaultTimezone']);
    }

    public function test_invalid_argument_includes_suggested_fix(): void
    {
        $created = $this->kernel->handle('siesta.create', [
            'library' => 'siesta-carbon',
            'factory' => 'now',
            'args' => [],
        ]);

        $error = $this->kernel->handle('siesta.invoke', [
            'handle' => $created['handle'],
            'method' => 'addWeeks',
            'args' => ['weeks' => -1],
        ]);

        $this->assertSame('INVALID_ARGUMENT', $error['error']['code']);
        $this->assertTrue($error['error']['retryable']);
        $this->assertArrayHasKey('suggestedFix', $error['error']);
    }
}
