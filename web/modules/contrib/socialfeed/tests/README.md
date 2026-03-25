# Social Feed Module - Unit Tests

This directory contains PHPUnit unit tests for the Social Feed module.

## Running Tests

### Prerequisites

1. Install dev dependencies:
   ```bash
   cd /path/to/drupal/web/modules/contrib/socialfeed
   composer install --dev
   ```

2. Ensure your Drupal site is properly configured for testing.

### Run All Unit Tests

From the Drupal root directory:

```bash
vendor/bin/phpunit -c web/modules/contrib/socialfeed/phpunit.xml.dist
```

Or from the module directory:

```bash
../../../../../../vendor/bin/phpunit
```

### Run Specific Test Suites

#### Instagram Tests

```bash
# Instagram API Service
vendor/bin/phpunit -c web/modules/contrib/socialfeed/phpunit.xml.dist \
  web/modules/contrib/socialfeed/tests/src/Unit/Services/InstagramApiServiceTest.php

# Instagram Post Collector
vendor/bin/phpunit -c web/modules/contrib/socialfeed/phpunit.xml.dist \
  web/modules/contrib/socialfeed/tests/src/Unit/Services/InstagramPostCollectorTest.php

# Instagram Factory
vendor/bin/phpunit -c web/modules/contrib/socialfeed/phpunit.xml.dist \
  web/modules/contrib/socialfeed/tests/src/Unit/Services/InstagramPostCollectorFactoryTest.php
```

#### Twitter Tests

```bash
# Twitter Post Collector
vendor/bin/phpunit -c web/modules/contrib/socialfeed/phpunit.xml.dist \
  web/modules/contrib/socialfeed/tests/src/Unit/Services/TwitterPostCollectorTest.php

# Twitter Factory
vendor/bin/phpunit -c web/modules/contrib/socialfeed/phpunit.xml.dist \
  web/modules/contrib/socialfeed/tests/src/Unit/Services/TwitterPostCollectorFactoryTest.php
```

#### Facebook Tests

```bash
# Facebook Factory
vendor/bin/phpunit -c web/modules/contrib/socialfeed/phpunit.xml.dist \
  web/modules/contrib/socialfeed/tests/src/Unit/Services/FacebookPostCollectorFactoryTest.php
```

### Run with Coverage

```bash
vendor/bin/phpunit -c web/modules/contrib/socialfeed/phpunit.xml.dist \
  --coverage-html coverage
```

## Test Structure

```
tests/
├── src/
│   └── Unit/
│       └── Services/
│           ├── FacebookPostCollectorFactoryTest.php
│           ├── InstagramApiServiceTest.php
│           ├── InstagramPostCollectorTest.php
│           ├── InstagramPostCollectorFactoryTest.php
│           ├── TwitterPostCollectorTest.php
│           └── TwitterPostCollectorFactoryTest.php
└── README.md
```

## Writing New Tests

When adding new features or services, please add corresponding unit tests:

1. Create test files in the appropriate directory under `tests/src/Unit/`
2. Extend `Drupal\Tests\UnitTestCase`
3. Use PHPUnit mocks for dependencies
4. Follow Drupal coding standards
5. Add `@group socialfeed` annotation
6. Add `@coversDefaultClass` annotation

Example:

```php
<?php

namespace Drupal\Tests\socialfeed\Unit\Services;

use Drupal\Tests\UnitTestCase;

/**
 * Tests for MyService.
 *
 * @group socialfeed
 * @coversDefaultClass \Drupal\socialfeed\Services\MyService
 */
class MyServiceTest extends UnitTestCase {
  // Tests here...
}
```

## CI/CD Integration

These tests can be integrated into your CI/CD pipeline. Example for GitHub
Actions:

```yaml
- name: Run PHPUnit Tests
  run: |
    cd web / modules / contrib / socialfeed
    . . / . . / . . / . . / . . / . . / vendor / bin / phpunit
```

## Additional Resources

- [Drupal PHPUnit Documentation](https://www.drupal.org/docs/automated-testing/phpunit-in-drupal)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
