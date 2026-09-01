<?php

declare(strict_types=1);

namespace Siesta\Carbon;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Siesta\Carbon\Attributes\SiestaFactory;
use Siesta\Carbon\Attributes\SiestaMethod;
use Siesta\Runtime\Contracts\LibraryAdapterInterface;
use Siesta\Runtime\Error\ErrorFactory;
use Siesta\Runtime\Manifest\ManifestLoader;

final class CarbonLibraryAdapter implements LibraryAdapterInterface
{
    private const TYPE = 'DateTime';

  /** @var array<string, mixed> */
    private array $config = [
        'defaultTimezone' => 'UTC',
        'locale' => 'en',
        'weekStartsAt' => 1,
    ];

    public function __construct(
        private readonly string $manifestPath,
        private readonly ManifestLoader $loader = new ManifestLoader(),
    ) {
    }

    public function getId(): string
    {
        return 'siesta-carbon';
    }

    public function getManifestPath(): string
    {
        return $this->manifestPath;
    }

  /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return $this->config;
    }

  /** @param array<string, mixed> $settings */
    public function configure(array $settings): void
    {
        $this->config = array_merge($this->config, $settings);
        Carbon::setLocale((string) $this->config['locale']);
    }

  /** @param array<string, mixed> $args */
    public function create(string $factory, array $args): object
    {
        $tz = $args['timezone'] ?? $this->config['defaultTimezone'];

        return match ($factory) {
            'now' => Carbon::now($tz),
            'parse' => Carbon::parse((string) ($args['input'] ?? ''), $tz),
            'createFromFormat' => Carbon::createFromFormat(
                (string) ($args['format'] ?? 'Y-m-d H:i:s'),
                (string) ($args['time'] ?? ''),
                $tz,
            ),
            'createFromDate' => Carbon::createFromDate(
                (int) ($args['year'] ?? date('Y')),
                (int) ($args['month'] ?? 1),
                (int) ($args['day'] ?? 1),
                $tz,
            ),
            'yesterday' => Carbon::yesterday($tz),
            'tomorrow' => Carbon::tomorrow($tz),
            default => throw ErrorFactory::methodNotFound($factory, 'factory'),
        };
    }

  /** @param array<string, mixed> $args */
    public function invoke(object $instance, string $method, array $args, ?object $contextInstance = null): mixed
    {
        if (!$instance instanceof CarbonInterface) {
            throw ErrorFactory::internal('Expected Carbon instance');
        }

        $carbon = $instance->copy();
        $carbon->settings(['weekStartsAt' => (int) $this->config['weekStartsAt']]);

        try {
            return match ($method) {
                'addWeeks' => $this->requirePositiveInt($args, 'weeks', $carbon->addWeeks((int) $args['weeks'])),
                'addDays' => $this->requirePositiveInt($args, 'days', $carbon->addDays((int) $args['days'])),
                'subDays' => $this->requirePositiveInt($args, 'days', $carbon->subDays((int) $args['days'])),
                'addMonths' => $this->requirePositiveInt($args, 'months', $carbon->addMonths((int) $args['months'])),
                'startOfWeek' => $carbon->startOfWeek((int) $this->config['weekStartsAt']),
                'endOfWeek' => $carbon->endOfWeek((int) $this->config['weekStartsAt']),
                'startOfMonth' => $carbon->startOfMonth(),
                'endOfMonth' => $carbon->endOfMonth(),
                'startOfDay' => $carbon->startOfDay(),
                'endOfDay' => $carbon->endOfDay(),
                'format' => $carbon->format((string) ($args['pattern'] ?? 'Y-m-d')),
                'diffInDays' => $contextInstance instanceof CarbonInterface
                    ? (int) $carbon->diffInDays($contextInstance)
                    : (int) $carbon->diffInDays(Carbon::now($this->config['defaultTimezone'])),
                'isWeekend' => $carbon->isWeekend(),
                'isPast' => $carbon->isPast(),
                'isFuture' => $carbon->isFuture(),
                'isToday' => $carbon->isToday(),
                'setTimezone' => $carbon->setTimezone((string) ($args['timezone'] ?? $this->config['defaultTimezone'])),
                'copy' => $carbon->copy(),
                'toIso8601String' => $carbon->toIso8601String(),
                default => throw ErrorFactory::methodNotFound($method, self::TYPE),
            };
        } catch (\Throwable $e) {
            if ($e instanceof \Siesta\Runtime\Error\SiestaError) {
                throw $e;
            }

            throw ErrorFactory::invalidArgument($e->getMessage(), suggestedFix: $args);
        }
    }

  /** @param array<string, mixed> $args */
    private function requirePositiveInt(array $args, string $field, CarbonInterface $result): CarbonInterface
    {
        $value = $args[$field] ?? null;

        if (!is_int($value) || $value < 0) {
            throw ErrorFactory::invalidArgument(
                "{$field} must be a non-negative integer",
                $field,
                [$field => max(0, is_numeric($value) ? (int) $value : 1)],
            );
        }

        return $result;
    }

  /** @return array<string, mixed> */
    public function snapshot(object $instance): array
    {
        if (!$instance instanceof CarbonInterface) {
            return [];
        }

        return [
            'iso' => $instance->toIso8601String(),
            'timezone' => $instance->getTimezone()->getName(),
            'timestamp' => $instance->getTimestamp(),
            'formatted' => $instance->format('Y-m-d H:i:s'),
        ];
    }

    public function getType(object $instance): string
    {
        return self::TYPE;
    }

  /** @return array<string, mixed> */
    public function getManifest(): array
    {
        return $this->loader->load($this->manifestPath);
    }
}
