<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Paywall;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpUtilities\Api\WpRemote;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use TheFrosty\WpX402\Api\Api;
use WP_Http;
use function base64_encode;

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
     * Return the payment signature from the request.
     * @return string|null
     */
    protected function getPaymentSignature(): ?string
    {
        if ($this->getRequest()?->server->has(Api::HEADER_PAYMENT_SIGNATURE)) {
            $signature = $this->getRequest()?->server->get(Api::HEADER_PAYMENT_SIGNATURE, '');
            // Simple Base64 Validation...
            $decode = base64_decode($signature, true);

            if ($decode === false) {
                return null;
            }

            // Check if signature and new base64 are identical.
            if (base64_encode($decode) !== $signature) {
                return null;
            }
            return $signature;
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
