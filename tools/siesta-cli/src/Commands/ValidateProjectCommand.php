<?php

declare(strict_types=1);

namespace Siesta\Cli\Commands;

use Siesta\Cli\RuntimeFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'validate', description: 'Validate all discovered manifests in the project')]
final class ValidateProjectCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kernel = RuntimeFactory::kernel();
        $result = $kernel->handle('siesta.validate', []);

        foreach ($result['libraries'] ?? [] as $library) {
            if ($library['valid']) {
                $output->writeln("<info>OK</info> {$library['id']} ({$library['manifest']})");
                continue;
            }

            $output->writeln("<error>FAIL</error> {$library['id']} ({$library['manifest']})");

            foreach ($library['errors'] ?? [] as $error) {
                $output->writeln("  [{$error['property']}] {$error['message']}");
            }
        }

        return ($result['valid'] ?? false) ? Command::SUCCESS : Command::FAILURE;
    }
}
