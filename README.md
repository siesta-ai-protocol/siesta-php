# Siesta PHP

PHP reference implementation of the [Siesta protocol](https://github.com/siesta-ai-protocol/siesta-protocol).

## Install

Clone **both** repos as siblings (or use the [Siesta monorepo](https://github.com/siesta-ai-protocol/siesta)):

```bash
git clone https://github.com/siesta-ai-protocol/siesta-protocol.git
git clone https://github.com/siesta-ai-protocol/siesta-php.git
cd siesta-php
composer install
```

`composer.json` resolves `siesta/protocol` from `../siesta-protocol`.

## Embed in Your Application

```php
use Siesta\Runtime\SiestaKernel;

$siesta = SiestaKernel::discover(__DIR__);

// Discover manifests on disk
$libraries = $siesta->handle('siesta.discover', []);

// Invoke protocol directly — no separate server
$result = $siesta->handle('siesta.create', [
    'library' => 'siesta-carbon',
    'factory' => 'now',
    'args' => [],
]);
```

Agents or middleware call `$siesta->handle($method, $params)` in-process. Your app is **Siesta protocol-aware**.

## CLI

```bash
php tools/siesta-cli/bin/siesta discover
php tools/siesta-cli/bin/siesta validate
php tools/siesta-cli/bin/siesta test:scenarios
php tools/siesta-cli/bin/siesta introspect siesta-carbon
```

## Packages

| Package | Description |
|---------|-------------|
| `siesta/runtime` | Discovery, validation, handles, protocol router |
| `siesta/carbon` | Carbon date/time adapter (reference library) |

## Discovery

Place `siesta.manifest.json` in your package and declare in `composer.json`:

```json
"extra": {
  "siesta": { "manifest": "siesta.manifest.json" }
}
```

## Tests

```bash
composer test
```

## License

MIT
