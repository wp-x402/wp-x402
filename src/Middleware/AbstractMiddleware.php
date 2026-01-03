<?php

declare(strict_types=1);

namespace WpX402\WpX402\Middleware;

use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function is_array;
use function is_callable;
use function rest_convert_error_to_response;

/**
 * This is the abstract class that catches the request at the `rest_pre_dispatch` hook and passes it to
 * the `rest_pre_dispatch` hook to check the request, and either allow or reject it in a series of callbacks.
 */
abstract class AbstractMiddleware implements WpHooksInterface
{
    use HooksTrait, Methods;

    /**
     * Internal callable middleware callbacks.
     * @var callable[] $callables
     */
    protected array $callables = [];

    /**
     * Inbound request object.
     * @var WP_REST_Request
     */
    protected WP_REST_Request $request;

    /**
     * Request method for inbound request. This property is used to determine correct method for route.
     * @var array
     */
    protected array $methodsToProtect = [];

    /**
     * Route input provided to compare inbound route against.
     * @var string|null
     */
    protected ?string $routeInput = null;

    /**
     * Determines if all requests should be rejected.
     * @var bool
     */
    protected bool $rejectAll = false;

    /**
     * Array of Rejection objects.
     * @var Rejection[]
     */
    protected array $rejections = [];

    /**
     * Response object provided to check hook.
     * @var WP_Error|WP_REST_Response
     */
    protected WP_Error|WP_REST_Response $response;

    public function addHooks(): void
    {
        $this->addFilter('rest_pre_dispatch', [$this, 'setRequest'], 0, 3);
        $this->addFilter('rest_post_dispatch', [$this, 'check']);
    }

    /**
     * Grab the inbound WP_REST_Request and save it to a private property for the `checkRoute` method to inject
     * into callbacks.
     * @param mixed $result Response to replace the requested version with. Can be anything
     *                                  a normal endpoint can return, or null to not hijack the request.
     * @param WP_REST_Server $server Server instance.
     * @param WP_REST_Request $request Request used to generate the response.
     * @return mixed
     */
    protected function setRequest(mixed $result, WP_REST_Server $server, WP_REST_Request $request): mixed
    {
        $this->request = $request;

        return $result;
    }

    /**
     * Check inbound request against registered middleware.
     * @param WP_Error|WP_HTTP_Response|WP_REST_Response $response Result to send to the client.
     * @return WP_Error|WP_HTTP_Response|WP_REST_Response
     */
    protected function check(
        WP_Error|WP_HTTP_Response|WP_REST_Response $response
    ): WP_Error|WP_HTTP_Response|WP_REST_Response {
        $this->response = $response;

        if ($this->rejectAll) {
            return $this->rejectWpResponse($this->response, Rejection::unauthorized());
        }

        /**
         * If no HTTP Method setting method was used, just default to the inbound request.
         * This allows `guard()` to protect all methods.
         */
        if (!$this->methodsToProtect) {
            $this->methodsToProtect[] = $this->request->get_method();
        }

        /**
         * Check route against all callbacks and populate rejections property.
         */
        $this->checkRoute();

        // Check Rejections' property.
        if (count($this->rejections) > 0) {
            return $this->rejectWpResponse($this->response, $this->rejections);
        }

        return $this->response;
    }

    /**
     * Checks the callbacks registered to a given route. If the response is a Rejection, then it is saved into the
     * $rejections property to be counted in the `check` method.
     */
    protected function checkRoute(): void
    {
        if ($this->requestMethodMatch() && $this->routePathMatch()) {
            $this->checkCallbacks();
        }
    }

    /**
     * Iterate through the callbacks in the $middleware property and see if they return Rejection class.
     */
    protected function checkCallbacks(): void
    {
        foreach ($this->callables as $callback) {
            if (is_callable($callback)) {
                $result = $callback($this->request, $this->response);
                if ($result instanceof Rejection) {
                    $this->rejections[] = $result;
                }
            }
        }
    }

    /**
     * This method takes the current $request object property and determines if a given input string
     * or array of strings containing methods match the request's method.
     * @return bool
     */
    protected function requestMethodMatch(): bool
    {
        return in_array($this->request->get_method(), $this->methodsToProtect, true);
    }

    /**
     * Determines if there is a matching route.
     * @return bool
     */
    protected function routePathMatch(): bool
    {
        return !is_wp_error($this->response) && $this->routeInput === $this->response->get_matched_route();
    }

    /**
     * Rejection response factory.
     * @param WP_Error|WP_HTTP_Response $response
     * @param Rejection[]|Rejection $rejection
     * @return WP_Error|WP_HTTP_Response
     */
    private function rejectWpResponse(
        WP_Error|WP_HTTP_Response $response,
        array|Rejection $rejection
    ): WP_Error|WP_HTTP_Response {
        $error = $response instanceof WP_Error ? $response : new WP_Error();
        if (is_array($rejection)) {
            foreach ($rejection as $reject) {
                $error->add($reject->getCode(), $reject->getMessage(), ['status' => $reject->getStatus()]);
            }
            return rest_convert_error_to_response($error);
        }

        $error->add($rejection->getCode(), $rejection->getMessage(), ['status' => $rejection->getStatus()]);

        return rest_convert_error_to_response($error);
    }
}
