<?php

declare(strict_types=1);

namespace Siesta\Cli\Commands;

use JsonSchema\Validator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'manifest:validate', description: 'Validate a siesta.manifest.json file')]
final class ManifestValidateCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to siesta.manifest.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = (string) $input->getArgument('path');
        $schemaPath = dirname(__DIR__, 4) . '/vendor/siesta/protocol/spec/manifest-schema.json';

        if (!is_file($schemaPath)) {
            $schemaPath = dirname(__DIR__, 4) . '/../siesta-protocol/spec/manifest-schema.json';
        }

        if (!is_file($schemaPath)) {
            $schemaPath = dirname(__DIR__, 4) . '/spec/manifest-schema.json';
        }

        if (!is_file($path)) {
            $output->writeln("<error>Manifest not found: {$path}</error>");

            return Command::FAILURE;
        }

        $manifest = json_decode((string) file_get_contents($path), false);

        if ($manifest === null) {
            $output->writeln('<error>Invalid JSON</error>');

            return Command::FAILURE;
        }

        $schema = json_decode((string) file_get_contents($schemaPath), false);
        $validator = new Validator();
        $validator->validate($manifest, $schema);

        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $output->writeln("<error>[{$error['property']}] {$error['message']}</error>");
            }

            return Command::FAILURE;
        }

        $output->writeln("<info>Valid manifest: {$path}</info>");

        return Command::SUCCESS;
    }
}
