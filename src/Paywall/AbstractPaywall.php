<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpUtilities\Api\WpRemote;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use WP_Http;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Models\PaymentRequired;
use WpX402\WpX402\Models\PaymentRequired\Accepts;
use WpX402\WpX402\Models\PaymentRequired\UrlResource;
use WpX402\WpX402\Networks\Mainnet;
use WpX402\WpX402\Networks\Testnet;
use WpX402\WpX402\Schema\Payment;
use WpX402\WpX402\Settings\Setting;
use function apply_filters;
use function base64_encode;
use function defined;
use function esc_html__;
use function sprintf;

/**
 * Class AbstractPaywall
 * @package WpX402\WpX402\Paywall
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
        // Headers: x402 v1 API => X-PAYMENT, x402 v2 API: X-PAYMENT-SIGNATURE.
        $headers = [Api::HEADER_X_PAYMENT, Api::HEADER_PAYMENT_SIGNATURE];
        foreach ($headers as $header) {
            if ($this->getRequest()?->headers->has($header)) {
                $signature = $this->getRequest()?->headers->get($header, '');
                // Simple Base64 Validation...
                $decode = base64_decode($signature, true);

                if ($decode === false) {
                    continue;
                }

                // Check if signature and new base64 are identical.
                if (base64_encode($decode) !== $signature) {
                    continue;
                }
                return $signature;
            }
        }

        return null;
    }

    /**
     * Build the x402 PaymentRequired model for a resource from the current settings.
     * @param string $url The resource URL.
     * @return PaymentRequired
     */
    protected function getPaymentRequired(string $url): PaymentRequired
    {
        $account = Setting::getAccount();
        $network = Setting::isMainnet() ? Mainnet::class : Testnet::class;

        return new PaymentRequired([
            PaymentRequired::ERROR => sprintf(
                esc_html__('`%s` header is required', 'wp-x402'),
                Api::HEADER_PAYMENT_SIGNATURE
            ),
            PaymentRequired::RESOURCE => [
                UrlResource::URL => $url,
                UrlResource::DESCRIPTION => Entitlement::PAYMENT_REQUIRED->label(),
                UrlResource::MIME_TYPE => 'text/html',
            ],
            PaymentRequired::ACCEPTS => [
                [
                    Accepts::SCHEME => Payment::EXACT->value,
                    Accepts::NETWORK => $network::getBase($account)->value,
                    Accepts::AMOUNT => Setting::getAmount(),
                    Accepts::ASSET => $network::getAsset($account)->value,
                    Accepts::PAY_TO => Setting::getWallet(),
                    Accepts::MAX_TIMEOUT_SECONDS => 60,
                    Accepts::EXTRA => self::getExtra($network::getBase($account)->value),
                ],
            ],
        ]);
    }

    /**
     * Return the scheme specific `extra` field for a CAIP-2 network.
     * EVM `exact` payments sign an EIP-3009 authorization, so clients need the token's EIP-712 domain
     * name and version. USDC's domain name is "USD Coin" on Base and "USDC" on Base Sepolia.
     * Solana needs the facilitator's fee payer instead, which isn't known here yet, so it gets none.
     * @param string $network
     * @return array<string, string>|null
     */
    protected static function getExtra(string $network): ?array
    {
        return match ($network) {
            Mainnet::BASE->value => ['name' => 'USD Coin', 'version' => '2'],
            Testnet::BASE->value => ['name' => 'USDC', 'version' => '2'],
            default => null,
        };
    }

    /**
     * Is debug output allowed? Defaults to WP_DEBUG; filter with `wp_x402_debug`.
     * Debug mode adds the facilitator's response to failed payment responses, and enables `?fakeBot`.
     * @return bool
     */
    protected function isDebug(): bool
    {
        return (bool)apply_filters('wp_x402_debug', defined('WP_DEBUG') && WP_DEBUG);
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
        return $this->buildJsonResponse($data, $status, $headers)->send();
    }

    /**
     * Build a JSON Response. Paywall responses depend on the payment headers and may hold paid content,
     * so page caches and CDNs must not store them or serve one client's response to another.
     * @param mixed|null $data
     * @param int $status
     * @param array $headers
     * @return JsonResponse
     */
    protected function buildJsonResponse(
        mixed $data = null,
        int $status = WP_Http::OK,
        array $headers = []
    ): JsonResponse {
        $headers += [
            'Cache-Control' => 'no-store, private',
            'Vary' => sprintf('%s, %s', Api::HEADER_PAYMENT_SIGNATURE, Api::HEADER_X_PAYMENT),
        ];

        return new JsonResponse($data, $status, $headers);
    }
}
