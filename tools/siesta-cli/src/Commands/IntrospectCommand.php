<?php

declare(strict_types=1);

namespace Siesta\Cli\Commands;

use Siesta\Cli\RuntimeFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'introspect', description: 'Pretty-print a library manifest')]
final class IntrospectCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('library', InputArgument::REQUIRED, 'Library id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $library = (string) $input->getArgument('library');
        $kernel = RuntimeFactory::kernel();
        $result = $kernel->handle('siesta.introspect', ['library' => $library]);

        if (isset($result['error'])) {
            $output->writeln('<error>' . $result['error']['message'] . '</error>');

            return Command::FAILURE;
        }

        $output->writeln(json_encode($result['manifest'] ?? $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Command::SUCCESS;
    }
}
