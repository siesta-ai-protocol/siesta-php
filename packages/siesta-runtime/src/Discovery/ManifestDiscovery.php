<?php

declare(strict_types=1);

namespace Siesta\Runtime\Discovery;

use Siesta\Runtime\Validation\ManifestValidator;

final class ManifestDiscovery
{
    public function __construct(
        private readonly ?ManifestValidator $validator = null,
    ) {
    }

  /** @return list<DiscoveredLibrary> */
    public function discover(string $projectRoot): array
    {
        $projectRoot = rtrim($projectRoot, '/\\');
        $manifestPaths = $this->collectManifestPaths($projectRoot);
        $discovered = [];

        foreach (array_unique($manifestPaths) as $manifestPath) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (!is_array($manifest)) {
                continue;
            }

            $errors = $this->validator?->validateFile($manifestPath) ?? [];
            $adapter = $manifest['adapter']['class'] ?? null;

            $discovered[] = new DiscoveredLibrary(
                manifestPath: $manifestPath,
                manifest: $manifest,
                valid: $errors === [],
                validationErrors: $errors,
                adapterClass: is_string($adapter) ? $adapter : null,
                packageName: $this->resolvePackageName($projectRoot, $manifestPath),
            );
        }

        usort($discovered, static fn (DiscoveredLibrary $a, DiscoveredLibrary $b): int => strcmp($a->getId(), $b->getId()));

        return $discovered;
    }

  /** @return list<string> */
    private function collectManifestPaths(string $projectRoot): array
    {
        $paths = [];

        $paths = array_merge($paths, $this->fromConfig($projectRoot));
        $paths = array_merge($paths, $this->fromComposerInstalled($projectRoot));
        $paths = array_merge($paths, $this->fromGlobPatterns($projectRoot));

        return array_values(array_filter($paths, 'is_file'));
    }

  /** @return list<string> */
    private function fromConfig(string $projectRoot): array
    {
        $configPath = $projectRoot . DIRECTORY_SEPARATOR . 'siesta.json';

        if (!is_file($configPath)) {
            return [];
        }

        $config = json_decode((string) file_get_contents($configPath), true);

        if (!is_array($config)) {
            return [];
        }

        $paths = [];

        foreach ($config['manifests'] ?? [] as $relative) {
            if (is_string($relative)) {
                $paths[] = $this->resolvePath($projectRoot, $relative);
            }
        }

        foreach ($config['discovery']['paths'] ?? [] as $relative) {
            if (!is_string($relative)) {
                continue;
            }

            $paths = array_merge($paths, $this->globManifests($this->resolvePath($projectRoot, $relative)));
        }

        return $paths;
    }

  /** @return list<string> */
    private function fromComposerInstalled(string $projectRoot): array
    {
        $installedPath = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';

        if (!is_file($installedPath)) {
            return [];
        }

        $installed = json_decode((string) file_get_contents($installedPath), true);

        if (!is_array($installed)) {
            return [];
        }

        $packages = $installed['packages'] ?? $installed;
        $paths = [];

        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            $installPath = $package['install_path'] ?? null;

            if (!is_string($installPath)) {
                continue;
            }

            $extra = $package['extra']['siesta'] ?? null;

            if (is_array($extra) && isset($extra['manifest']) && is_string($extra['manifest'])) {
                $paths[] = $installPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $extra['manifest']);
                continue;
            }

            $default = $installPath . DIRECTORY_SEPARATOR . 'siesta.manifest.json';

            if (is_file($default)) {
                $paths[] = $default;
            }
        }

        return $paths;
    }

  /** @return list<string> */
    private function fromGlobPatterns(string $projectRoot): array
    {
        $paths = [];

        foreach (['packages', 'vendor'] as $dir) {
            $base = $projectRoot . DIRECTORY_SEPARATOR . $dir;

            if (!is_dir($base)) {
                continue;
            }

            $paths = array_merge($paths, $this->globManifests($base));
        }

        $rootManifest = $projectRoot . DIRECTORY_SEPARATOR . 'siesta.manifest.json';

        if (is_file($rootManifest)) {
            $paths[] = $rootManifest;
        }

        return $paths;
    }

  /** @return list<string> */
    private function globManifests(string $base): array
    {
        $pattern = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . '**' . DIRECTORY_SEPARATOR . 'siesta.manifest.json';
        $matches = glob($pattern, GLOB_BRACE) ?: [];

        if ($matches !== []) {
            return $matches;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
        );

        $paths = [];

        foreach ($iterator as $file) {
            if ($file->getFilename() === 'siesta.manifest.json') {
                $paths[] = $file->getPathname();
            }
        }

        return $paths;
    }

    private function resolvePath(string $projectRoot, string $relative): string
    {
        if (str_starts_with($relative, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:\\\\#', $relative)) {
            return $relative;
        }

        return $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function resolvePackageName(string $projectRoot, string $manifestPath): ?string
    {
        $vendorPos = strpos($manifestPath, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR);

        if ($vendorPos === false) {
            return null;
        }

        $remainder = substr($manifestPath, $vendorPos + 9);
        $parts = explode(DIRECTORY_SEPARATOR, $remainder, 3);

        if (count($parts) >= 2) {
            return $parts[0] . '/' . $parts[1];
        }

        return null;
    }
}
