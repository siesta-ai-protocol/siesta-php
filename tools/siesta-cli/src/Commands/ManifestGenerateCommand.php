<?php

declare(strict_types=1);

namespace Siesta\Cli\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'manifest:generate', description: 'Copy canonical manifest for a library (attribute scanning v1.1)')]
final class ManifestGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('library', InputArgument::REQUIRED, 'Library id (e.g. siesta-carbon)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $library = (string) $input->getArgument('library');

        if ($library !== 'siesta-carbon') {
            $output->writeln("<error>Unknown library: {$library}</error>");

            return Command::FAILURE;
        }

        $source = dirname(__DIR__, 4) . '/packages/siesta-carbon/siesta.manifest.json';
        $output->writeln("<info>Canonical manifest: {$source}</info>");
        $output->writeln('Attribute-based generation ships in v1.1; manifest is source of truth for v1.');

        return Command::SUCCESS;
    }
}
