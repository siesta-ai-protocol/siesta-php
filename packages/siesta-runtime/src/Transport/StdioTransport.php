<?php

declare(strict_types=1);

namespace Siesta\Runtime\Transport;

use Siesta\Runtime\Protocol\JsonRpcRouter;

final class StdioTransport
{
    public function __construct(private readonly JsonRpcRouter $router)
    {
    }

    public function listen(): void
    {
        while (($line = fgets(STDIN)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $response = $this->router->process($line);
            fwrite(STDOUT, json_encode($response, JSON_THROW_ON_ERROR) . "\n");
        }
    }

    public function processLine(string $line): string
    {
        $response = $this->router->process($line);

        return json_encode($response, JSON_THROW_ON_ERROR);
    }
}
