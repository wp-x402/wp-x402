<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Paywall;

use Symfony\Component\HttpFoundation\JsonResponse;
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
abstract class AbstractPaywall extends AbstractContainerProvider implements
    HttpFoundationRequestInterface,
    PaywallInterface
{

    use HttpFoundationRequestTrait, WpRemote;

    /**
     * Return the payment hash from the request.
     * @return string|null
     */
    protected function getPaymentHash(): ?string
    {
        if ($this->getRequest()?->server->has('HTTP_X_PAYMENT_HASH')) {
            return $this->getRequest()?->server->get('HTTP_X_PAYMENT_HASH');
        }
        if ($this->getRequest()?->server->has('HTTP_X_PAYMENT')) {
            return $this->getRequest()?->server->get('HTTP_X_PAYMENT');
        }
        if ($this->getRequest()?->server->has('HTTP_X_PAYMENT_RESPONSE')) {
            return $this->getRequest()?->server->get('HTTP_X_PAYMENT_RESPONSE');
        }

        return null;
    }

    /**
     * Return a JSON Response.
     * @param mixed|null $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function sendJsonResponse(mixed $data = null, int $status = WP_Http::OK, array $headers = []): Response
    {
        return (new JsonResponse($data, $status, $headers))->send();
    }
}
