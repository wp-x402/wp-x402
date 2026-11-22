<?php

declare(strict_types=1);

namespace WpX402\WpX402\Tests;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Plugin\Plugin;
use TheFrosty\WpUtilities\Plugin\PluginFactory;
use WpX402\WpX402\Settings\General;
use WpX402\WpX402\Settings\Misc;
use function add_filter;
use function delete_option;
use function dirname;
use function remove_all_filters;
use function update_option;
use function wp_json_encode;
use const WpX402\WpX402\PLUGIN_SLUG;

/**
 * Class TestCase
 * @package WpX402\WpX402\Tests
 */
class TestCase extends PhpUnitTestCase
{

    protected Plugin $plugin;

    /**
     * HTTP requests captured by `captureHttp()`.
     * @var array<int, array{url: string, args: array}>
     */
    protected array $requests = [];

    /**
     * Setup.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->plugin = PluginFactory::create(PLUGIN_SLUG, dirname(__DIR__, 2) . '/wp-x402.php');
        delete_option(General::SECTION_ID);
        delete_option(Misc::SECTION_ID);
    }

    /**
     * Tear down.
     */
    public function tearDown(): void
    {
        remove_all_filters('pre_http_request');
        delete_option(General::SECTION_ID);
        delete_option(Misc::SECTION_ID);
        $this->requests = [];
        unset($this->plugin);
        parent::tearDown();
    }

    /**
     * Save settings to the General section.
     * @param array $settings
     */
    protected function setGeneralSettings(array $settings): void
    {
        update_option(General::SECTION_ID, $settings);
    }

    /**
     * Save settings to the Misc section.
     * @param array $settings
     */
    protected function setMiscSettings(array $settings): void
    {
        update_option(Misc::SECTION_ID, $settings);
    }

    /**
     * Call a (protected or private) method.
     * @param object|string $object An object, or a class name for static methods.
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    protected function invoke(object|string $object, string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod($object, $method);

        return $reflection->invoke($reflection->isStatic() ? null : $object, ...$args);
    }

    /**
     * Replace the static Symfony Request held by a class using the `HttpFoundationRequestTrait`.
     * (The trait's `setRequest()` only sets it once, so tests swap it directly.)
     * @param string $class
     * @param Request|null $request
     */
    protected function setStaticRequest(string $class, ?Request $request): void
    {
        (new ReflectionProperty($class, 'request'))->setValue(null, $request);
    }

    /**
     * Short-circuit HTTP requests with a JSON response, and record each request made.
     * @param mixed $body
     * @param int $code
     * @param array $headers
     */
    protected function fakeHttp(mixed $body, int $code = 200, array $headers = []): void
    {
        add_filter('pre_http_request', function ($preempt, array $args, string $url) use ($body, $code, $headers) {
            $this->requests[] = ['url' => $url, 'args' => $args];
            return [
                'headers' => $headers,
                'body' => wp_json_encode($body),
                'response' => ['code' => $code, 'message' => 'OK'],
                'cookies' => [],
                'filename' => null,
            ];
        }, 10, 3);
    }
}
