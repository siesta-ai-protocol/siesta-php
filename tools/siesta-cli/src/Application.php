<?php

declare(strict_types=1);

namespace Siesta\Cli;

use Siesta\Cli\Commands\DiscoverCommand;
use Siesta\Cli\Commands\IntrospectCommand;
use Siesta\Cli\Commands\ManifestGenerateCommand;
use Siesta\Cli\Commands\ManifestValidateCommand;
use Siesta\Cli\Commands\TestScenariosCommand;
use Siesta\Cli\Commands\ValidateProjectCommand;
use Symfony\Component\Console\Application as SymfonyApplication;

final class Application extends SymfonyApplication
{
    public function __construct()
    {
        parent::__construct('Siesta', '1.0.0');
        $this->addCommands([
            new DiscoverCommand(),
            new ValidateProjectCommand(),
            new ManifestValidateCommand(),
            new ManifestGenerateCommand(),
            new TestScenariosCommand(),
            new IntrospectCommand(),
        ]);
    }
}
