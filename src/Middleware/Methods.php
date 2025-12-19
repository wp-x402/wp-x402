<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Middleware;

use WP_REST_Server;

/**
 * This trait adds the helper methods to designate the method to protect against.
 */
trait Methods
{
    /**
     * GET method setter helper.
     * @param array ...$input
     * @return void
     */
    public function get(...$input): void
    {
        $this->methodsToProtect[] = WP_REST_Server::READABLE;
        $this->guard(...$input);
    }

    /**
     * GET method setter helper.
     * @param array ...$input
     * @return void
     */
    public function post(...$input): void
    {
        $this->methodsToProtect[] = WP_REST_Server::CREATABLE;
        $this->guard(...$input);
    }

    /**
     * GET method setter helper.
     * @param array ...$input
     * @return void
     */
    public function put(...$input): void
    {
        $this->methodsToProtect[] = 'PUT';
        $this->guard(...$input);
    }

    /**
     * GET method setter helper.
     * @param array ...$input
     * @return void
     */
    public function patch(...$input): void
    {
        $this->methodsToProtect[] = 'PATCH';
        $this->guard(...$input);
    }

    /**
     * GET method setter helper.
     * @param array ...$input
     * @return void
     */
    public function delete(...$input): void
    {
        $this->methodsToProtect[] = WP_REST_Server::DELETABLE;
        $this->guard(...$input);
    }

    /**
     * GET method setter helper.
     * @param array ...$input
     * @return void
     */
    public function head(...$input): void
    {
        $this->methodsToProtect[] = 'HEAD';
        $this->guard(...$input);
    }

    /**
     * Reject all requests to the route(s).
     * @param array ...$input
     * @return void
     */
    public function reject(...$input): void
    {
        $this->rejectAll = true;
        $this->guard(...$input);
    }

    /**
     * Only authenticated users can access route.
     * @param array ...$input
     * @return void
     */
    public function authenticated(...$input): void
    {
        if (!is_user_logged_in()) {
            $this->rejectAll = true;
        }
        $this->guard(...$input);
    }

    /**
     * Compare a string of $route, against an array of functions to call.
     * @param string $route
     * @param callable[] $functions
     * @return Methods|AbstractMiddleware
     */
    protected function guard(string $route, array $functions = []): self
    {
        $this->routeInput = $route;
        $this->callables = $functions;
        return $this;
    }
}
