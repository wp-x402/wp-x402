# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "x402" that implements a payments middleware for WordPress. It provides a paywall
system that allows content to be restricted based on payment verification, primarily targeting bots and automated
systems.

Key features include:

- Bot detection and handling for automated requests
- Payment verification using blockchain-based payment systems
- Integration with WordPress post and category paywall settings
- License validation for plugin functionality
- Telemetry for tracking payment attempts and success/failure rates

## Architecture and Structure

The plugin follows a structured PHP architecture with:

- Main plugin namespace: `WpX402\WpX402`
- Core functionality organized in `src/` directory
- Paywall system with different implementations (bots, regular users)
- API integration for payment verification
- Settings and configuration management
- Telemetry and event tracking

Key components:

- `src/Paywall/ForBots.php` - Main bot paywall handler
- `src/Api/Bots.php` - Bot agent detection and management
- Various service providers and models for payment handling
- WordPress integration hooks and filters

## Development Setup

### Prerequisites

- PHP 8.3 or higher
- WordPress 6.8 or higher
- Composer for dependency management

### Commands

#### Installation

```bash
composer install
```

#### Code Quality

```bash
# Run PHP_CodeSniffer
composer phpcs

# Run PHPStan
composer phpstan

# Run full PHP_CodeSniffer
composer phpcs:full

# Run full PHPStan
composer phpstan:full
```

#### Testing

```bash
# Run PHPUnit tests
composer phpunit

# Run PHPUnit with coverage
composer phpunit:coverage
```

### Key Files to Understand

- `src/Paywall/ForBots.php` - Core bot paywall logic
- `src/Api/Bots.php` - Bot detection and agent management
- `src/functions.php` - Global plugin functions
- `composer.json` - Dependencies and scripts

### WordPress Integration

The plugin integrates with WordPress through:

- WordPress hooks (`template_redirect`, etc.)
- WordPress settings API for configuration
- WordPress post and category paywall settings
- WordPress transient API for caching bot agents

### External Dependencies

- `dwnload/edd-software-license-manager` - License management
- `htmlburger/carbon-fields` - WordPress meta box framework
- `symfony/http-foundation` - HTTP request/response handling
- `thefrosty/wp-utilities` - Common WordPress utilities
