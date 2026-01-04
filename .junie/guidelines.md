### Coding Guidelines for wp-x402

#### Coding Conventions
The project follows a mix of WordPress Coding Standards and PSR-12, enforced via PHP_CodeSniffer.

*   **Standards:**
    *   **WordPress-Docs**: Documentation standards are followed (with minor exclusions for missing short descriptions and specific spacing).
    *   **PSR-12**: Extended PSR-2 coding style guide.
    *   **Slevomat Coding Standard**: Used for modern PHP features and stricter checks (e.g., type hints, unused imports, alphabetical uses).
    *   **PHPCompatibility**: Ensures compatibility with PHP 8.3 and higher.
*   **Key Rules:**
    *   Tab width: 4.
    *   Strict types are declared (`declare(strict_types=1);`).
    *   Text domain for i18n: `wp-x402`.
    *   Yoda comparisons are disallowed (`SlevomatCodingStandard.ControlStructures.DisallowYodaComparison`).
    *   Alphabetically sorted use statements.
    *   Trailing commas in multi-line arrays.

#### Code Organization and Package Structure
The project uses PSR-4 autoloading with the namespace `WpX402\WpX402` mapping to the `src/` directory.

*   **Core Structure:**
    *   `wp-x402.php`: Main plugin entry point and initialization logic.
    *   `src/ServiceProvider.php`: Dependency injection container (Pimple) registration for services.
    *   `src/functions.php`: Global utility functions.
*   **Packages:**
    *   `Api/`: REST API endpoints and handlers.
    *   `Middleware/`: Request/Response middleware logic.
    *   `Models/`: Data models and structures (e.g., `PaymentRequired`).
    *   `Networks/`: Blockchain network configurations (Mainnet, Testnet).
    *   `Paywall/`: Core paywall logic and entitlement checks for both humans and bots.
    *   `Settings/`: Plugin settings management using `WpSettingsApi`.
    *   `Telemetry/`: Usage tracking and telemetry data collection.

#### Testing Approaches
The project uses PHPUnit for testing.

*   **Testing Framework:** PHPUnit 11.
*   **Types of Tests:**
    *   **Unit Tests**: Located in `tests/unit`. Focus on testing individual components in isolation.
    *   **Integration Tests**: The infrastructure for integration tests is defined in `phpunit.xml` (using `WP_PHP_UNIT` and `wp-tests-config.php`), although the specific directories may be added as the project grows.
*   **Environment:**
    *   Uses `WP_PHPUNIT__TESTS_CONFIG` for WordPress integration tests.
    *   Requires PHP 8.3 for running tests.
    *   Xdebug is used for coverage reports.
*   **Commands:**
    *   `composer phpunit`: Runs tests without coverage.
    *   `composer phpunit:coverage`: Runs tests and generates a clover XML and HTML report.
