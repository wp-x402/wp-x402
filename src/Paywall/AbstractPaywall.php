<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Paywall;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpUtilities\Api\WpRemote;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use WP_Http;

/**
 * Class AbstractPaywall
 * @package TheFrosty\WpX402\Paywall
 */
abstract class AbstractPaywall
    extends AbstractContainerProvider implements HttpFoundationRequestInterface, PaywallInterface
{

    use HttpFoundationRequestTrait, WpRemote;

    /**
     * Return the payment hash from the request.
     * @param Request|null $request
     * @return string|null
     */
    protected function getPaymentHash(Request|null $request): ?string
    {
        if ($request?->server->has('HTTP_X_PAYMENT_HASH')) {
            return $request?->server->get('HTTP_X_PAYMENT_HASH');
        }
        if ($request?->server->has('HTTP_X_PAYMENT')) {
            return $request?->server->get('HTTP_X_PAYMENT');
        }

        return null;
    }

    /**
     * Return a JSON Response.
     * @param mixed|null $data
     * @param int $status
     * @return Response
     */
    protected function sendJsonResponse(mixed $data = null, int $status = WP_Http::OK): Response
    {
        return (new JsonResponse($data, $status))->send();
    }
}
