<?php

declare(strict_types=1);

namespace Siesta\Runtime\Discovery;

use Siesta\Runtime\Contracts\LibraryAdapterInterface;
use Siesta\Runtime\Error\ErrorFactory;

final class AdapterFactory
{
    public function create(DiscoveredLibrary $library): LibraryAdapterInterface
    {
        if (!$library->isExecutable()) {
            throw ErrorFactory::internal(
                "Library {$library->getId()} has no executable adapter. Manifest: {$library->manifestPath}",
            );
        }

        $class = $library->adapterClass;
        $adapter = new $class($library->manifestPath);

        if (!$adapter instanceof LibraryAdapterInterface) {
            throw ErrorFactory::internal("Adapter {$class} must implement LibraryAdapterInterface");
        }

        return $adapter;
    }
}
