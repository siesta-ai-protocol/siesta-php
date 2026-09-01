<?php

declare(strict_types=1);

namespace Siesta\Cli\Commands;

use Siesta\Cli\RuntimeFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'test:scenarios', description: 'Run agent scenario tests')]
final class TestScenariosCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('library', null, InputOption::VALUE_REQUIRED, 'Library id', 'siesta-carbon');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kernel = RuntimeFactory::kernel('test_session');
        $runtime = $kernel->getRuntime();
        $passed = 0;
        $failed = 0;

        $scenarios = [
            'Add 2 weeks and format' => function () use ($runtime): void {
                $create = $runtime->handle('siesta.create', ['library' => 'siesta-carbon', 'factory' => 'now', 'args' => []]);
                $handle = $create['handle'];
                $add = $runtime->handle('siesta.invoke', ['handle' => $handle, 'method' => 'addWeeks', 'args' => ['weeks' => 2]]);
                $format = $runtime->handle('siesta.invoke', ['handle' => $add['handle'], 'method' => 'format', 'args' => ['pattern' => 'Y-m-d']]);

                if (!isset($format['value']) || !is_string($format['value'])) {
                    throw new \RuntimeException('Expected formatted value');
                }
            },
            'Parse and diff days' => function () use ($runtime): void {
                $parsed = $runtime->handle('siesta.create', [
                    'library' => 'siesta-carbon',
                    'factory' => 'parse',
                    'args' => ['input' => '2026-01-01'],
                ]);
                $now = $runtime->handle('siesta.create', ['library' => 'siesta-carbon', 'factory' => 'now', 'args' => []]);
                $diff = $runtime->handle('siesta.invoke', [
                    'handle' => $parsed['handle'],
                    'method' => 'diffInDays',
                    'args' => ['otherHandle' => $now['handle']],
                ]);

                if (!isset($diff['value'])) {
                    throw new \RuntimeException('Expected diff value');
                }
            },
            'Error recovery' => function () use ($runtime): void {
                $create = $runtime->handle('siesta.create', ['library' => 'siesta-carbon', 'factory' => 'now', 'args' => []]);
                $error = $runtime->handle('siesta.invoke', [
                    'handle' => $create['handle'],
                    'method' => 'addWeeks',
                    'args' => ['weeks' => -1],
                ]);

                if (!($error['error']['retryable'] ?? false)) {
                    throw new \RuntimeException('Expected retryable error');
                }

                $retry = $runtime->handle('siesta.invoke', [
                    'handle' => $create['handle'],
                    'method' => 'addWeeks',
                    'args' => $error['error']['suggestedFix'] ?? ['weeks' => 1],
                ]);

                if (!isset($retry['handle'])) {
                    throw new \RuntimeException('Retry failed');
                }
            },
            'Batch workflow' => function () use ($runtime): void {
                $batch = $runtime->handle('siesta.batch', [
                    'operations' => [
                        ['method' => 'siesta.configure', 'params' => ['library' => 'siesta-carbon', 'settings' => ['defaultTimezone' => 'UTC']]],
                        ['method' => 'siesta.create', 'params' => ['library' => 'siesta-carbon', 'factory' => 'now', 'args' => []]],
                    ],
                ]);

                if (!isset($batch['results']) || count($batch['results']) < 2) {
                    throw new \RuntimeException('Batch incomplete');
                }
            },
        ];

        foreach ($scenarios as $name => $scenario) {
            try {
                $scenario();
                $output->writeln("<info>PASS</info> {$name}");
                $passed++;
            } catch (\Throwable $e) {
                $output->writeln("<error>FAIL</error> {$name}: {$e->getMessage()}");
                $failed++;
            }
        }

        $output->writeln("\n{$passed} passed, {$failed} failed");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
