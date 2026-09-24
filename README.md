# SAGE Counseling Helpers

[![Tests](https://github.com/SAGE-Counseling/helpers/actions/workflows/tests.yml/badge.svg)](https://github.com/SAGE-Counseling/helpers/actions/workflows/tests.yml)

Shared PHP helper package used across SAGE Counseling's sites.

## Installation

```bash
composer require sage-counseling/helpers
```

## Usage

```php
use SageCounseling\Helpers\Str;

Str::slug('Hello World'); // "hello-world"
```

## Testing

```bash
composer install
vendor/bin/phpunit
```
