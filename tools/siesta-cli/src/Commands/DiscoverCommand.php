<?php

declare(strict_types=1);

namespace Siesta\Cli\Commands;

use Siesta\Cli\RuntimeFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(name: 'discover', description: 'Discover siesta.manifest.json files in the project')]
final class DiscoverCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kernel = RuntimeFactory::kernel();
        $result = $kernel->handle('siesta.discover', []);

        if ($input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['ID', 'Manifest', 'Valid', 'Executable', 'Registered']);

        foreach ($result['libraries'] ?? [] as $library) {
            $table->addRow([
                $library['id'],
                $library['manifest'],
                $library['valid'] ? 'yes' : 'no',
                $library['executable'] ? 'yes' : 'no',
                $library['registered'] ? 'yes' : 'no',
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
