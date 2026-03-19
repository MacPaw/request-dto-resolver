# AGENTS.md

## Cursor Cloud specific instructions

This is a PHP Symfony Bundle library (`macpaw/request-dto-resolver`). It has no runtime services, databases, or web servers — only CLI-based dev tools.

### Quick reference

Commands are defined in `composer.json` scripts section:

| Task | Command |
|---|---|
| Install deps | `composer install` |
| Tests | `composer phpunit` or `XDEBUG_MODE=coverage vendor/bin/phpunit` |
| Lint (PHPCS) | `composer cs` |
| Lint fix | `composer cs-fix` |
| Static analysis | `composer phpstan` |
| Validate | `composer validate` |

### Gotchas

- **Xdebug coverage mode**: PHPUnit warns if `XDEBUG_MODE=coverage` is not set. Use `XDEBUG_MODE=coverage vendor/bin/phpunit` to suppress the warning and generate `coverage.xml`.
- **No composer.lock**: This repo does not commit `composer.lock` (standard for libraries). `composer install` runs `composer update` under the hood.
