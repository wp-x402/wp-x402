<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall;

use Dwnload\EddSoftwareLicenseManager\Edd\License;
use JsonException;
use ReflectionClass;
use WP_Error;
use WP_Http;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Api\Bots;
use WpX402\WpX402\Models\PaymentRequired;
use WpX402\WpX402\Models\PaymentRequired\Accepts;
use WpX402\WpX402\Models\PaymentRequired\UrlResource;
use WpX402\WpX402\ServiceProvider;
use WpX402\WpX402\Settings\Setting;
use WpX402\WpX402\Telemetry\EventType;
use function array_keys;
use function base64_encode;
use function esc_html__;
use function get_permalink;
use function get_post;
use function get_the_category;
use function get_the_date;
use function get_the_ID;
use function get_the_title;
use function is_array;
use function is_attachment;
use function is_singular;
use function is_string;
use function is_wp_error;
use function json_decode;
use function json_encode;
use function sprintf;
use function status_header;
use function strip_tags;
use function stripos;
use function strtolower;
use function TheFrosty\WpUtilities\exitOrThrow;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_response_code;
use function WpX402\WpX402\setHeader;
use function WpX402\WpX402\telemetry;
use const JSON_THROW_ON_ERROR;
use const WpX402\WpX402\PLUGIN_SLUG;

/**
 * Class ForBots
 * @package WpX402\WpX402\Paywall
 */
class ForBots extends AbstractPaywall
{

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('template_redirect', [$this, 'templateRedirect']);
    }

    /**
     * Redirect based on current template conditions.
     * @throws \JsonException
     * @throws \TheFrosty\WpUtilities\Exceptions\TerminationException
     * @throws \Exception
     */
    protected function templateRedirect(): void
    {
        if (is_admin() || (!is_singular() || is_attachment())) {
            return;
        }

        if (!$this->isX402Client()) {
            return;
        }

        // 1. Validate the wallet.
        $wallet = Setting::getWallet();
        $validator = $this->getContainer()?->get(ServiceProvider::WALLET_ADDRESS_VALIDATOR);
        if (!Api::isValidWallet($validator, $wallet)) {
            setHeader(__('Error: Invalid Wallet Address', 'wp-x402'));
            return; // @TODO we should look into doing something if a wallet is invalid
        }

        // 2. Validate paywall enabled for object or category.
        if (
            !Paywall::isPaywallEnabled(get_the_ID()) &&
            Paywall::areCategoriesExcludedFromPaywall(get_the_category(get_the_ID()))
        ) {
            setHeader(__('Notice: Paywall Disabled for Object or Excluded by Category', 'wp-x402'));
            return;
        }

        // 3. Validate the license.
        if (!License::isActiveValid(PLUGIN_SLUG) || License::isExpired(PLUGIN_SLUG)) {
            // @TODO: Add logging or temp transient storage for admin notice.
            setHeader(__('Error: Invalid or Expired License', 'wp-x402'));
            return;
        }

        $payment_required = $this->getPaymentRequired((string)get_permalink());

        // 4. Check for Payment Header.
        $paymentSignature = $this->getPaymentSignature();

        // Scenario A: No Payment Hash -> Return 402 Offer.
        if (!$paymentSignature) {
            status_header(WP_Http::PAYMENT_REQUIRED);

            $this->sendJsonResponse(
                [PaymentRequired::ERROR => Entitlement::PAYMENT_REQUIRED->label()],
                WP_Http::PAYMENT_REQUIRED,
                [
                    Api::HEADER_PAYMENT_REQUIRED => base64_encode(
                        json_encode($payment_required->toArray(), JSON_THROW_ON_ERROR)
                    ),
                ]
            );
            // Telemetry: Impression.
            telemetry(
                EventType::REQUIRED,
                [
                    Accepts::NETWORK => $payment_required->getAccepts()[0]->getNetwork(),
                    UrlResource::URL => $payment_required->getResource()->getUrl(),
                ]
            );
            exitOrThrow();
        }

        // Scenario B: Verify Payment Hash.
        $response = $this->facilitate(Api::ACTION_VERIFY, $payment_required, $paymentSignature);

        if (is_wp_error($response)) {
            status_header(WP_Http::BAD_REQUEST);
            $data = [
                PaymentRequired::ERROR => sprintf('Bad request: %s', $response->get_error_message()),
            ];
            $this->sendJsonResponse($data, WP_Http::BAD_REQUEST);
            exitOrThrow();
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $verification = self::getJsonBody($response);

        if (self::isPaymentValid($response)) {
            $post = get_post();
            $data = [
                'status' => 'paid',
                'access' => 'granted',
                'title' => get_the_title($post),
                'content' => strip_tags($post->post_content), // Cleaned content.
                'id' => $post->ID,
                'date' => get_the_date('c', $post),
            ];
            $data = apply_filters('wp_x402_response_data', $data, $post);

            // Settle once the resource is ready and before responding (x402's "verify, serve, settle"):
            // verifying alone doesn't move any funds.
            $settlement = $this->facilitate(Api::ACTION_SETTLE, $payment_required, $paymentSignature);
            if (!is_wp_error($settlement) && self::isSettled($settlement)) {
                $settled = self::getJsonBody($settlement);
                $this->sendJsonResponse($data, WP_Http::OK, [
                    Api::HEADER_PAYMENT_RESPONSE => base64_encode(json_encode($settled, JSON_THROW_ON_ERROR)),
                ]);

                // Telemetry: Success.
                telemetry(
                    EventType::SUCCESS,
                    ['signature' => $paymentSignature, 'transaction' => $settled['transaction'] ?? '']
                );
                exitOrThrow();
            }

            // Settlement failed: report its reason below.
            $response_code = is_wp_error($settlement) ? 0 : wp_remote_retrieve_response_code($settlement);
            $verification = is_wp_error($settlement) ?
                ['error' => $settlement->get_error_message()] :
                self::getJsonBody($settlement);
        }

        // Payment Invalid (likely): offer the requirements again, with the facilitator's reason.
        status_header(WP_Http::PAYMENT_REQUIRED);
        $reason = $verification['invalidReason'] ?? $verification['errorReason'] ?? $verification['error'] ?? null;
        $payment_required->setError(
            is_string($reason) && $reason !== '' ?
                sprintf(esc_html__('Payment invalid: %s', 'wp-x402'), $reason) :
                esc_html__('Payment Invalid or Expired.', 'wp-x402')
        );

        $data = [PaymentRequired::ERROR => $payment_required->getError()];
        if ($this->isDebug()) {
            $data['extra'] = ['code' => $response_code, 'message' => $verification];
        }
        $this->sendJsonResponse(
            $data,
            WP_Http::PAYMENT_REQUIRED,
            [
                Api::HEADER_PAYMENT_REQUIRED => base64_encode(
                    json_encode($payment_required->toArray(), JSON_THROW_ON_ERROR)
                ),
            ]
        );

        // Telemetry: Failed.
        telemetry(EventType::FAILED, ['signature' => $paymentSignature, 'code' => $response_code]);
        exitOrThrow();
    }

    /**
     * Should this request get the x402 flow? Any client sending a payment does, whatever its user agent, as do
     * known AI agents (ai.robots.txt), and `?fakeBot` when debugging.
     * @return bool
     */
    protected function isX402Client(): bool
    {
        if ($this->getPaymentSignature() !== null) {
            return true;
        }

        if ($this->isDebug() && $this->getRequest()?->query->has('fakeBot')) {
            return true;
        }

        $user_agent = (string)$this->getRequest()?->headers->get('User-Agent', '');
        if ($user_agent === '') {
            return false;
        }

        foreach (array_keys(Bots::getAgents() ?? []) as $agent) {
            if (stripos($user_agent, (string)$agent) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Did the facilitator say the payment is valid? Only a 200 with `isValid: true` counts.
     * @param array<mixed> $response A `wp_remote_*()` response.
     * @return bool
     */
    protected static function isPaymentValid(array $response): bool
    {
        return wp_remote_retrieve_response_code($response) === WP_Http::OK &&
            (self::getJsonBody($response)['isValid'] ?? false) === true;
    }

    /**
     * Did the facilitator settle the payment? Only a 200 with `success: true` counts.
     * @param array<mixed> $response A `wp_remote_*()` response.
     * @return bool
     */
    protected static function isSettled(array $response): bool
    {
        return wp_remote_retrieve_response_code($response) === WP_Http::OK &&
            (self::getJsonBody($response)['success'] ?? false) === true;
    }

    /**
     * Decode a `wp_remote_*()` response's JSON body.
     * @param array<mixed> $response
     * @return array<mixed> The decoded object, or an empty array when the body isn't a JSON object.
     */
    protected static function getJsonBody(array $response): array
    {
        try {
            $body = json_decode(wp_remote_retrieve_body($response), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return is_array($body) ? $body : [];
    }

    /**
     * Send the payment to our API, which forwards it to the facilitator's `verify` or `settle` endpoint.
     * @param string $action Api::ACTION_VERIFY or Api::ACTION_SETTLE.
     * @param PaymentRequired $payment_required
     * @param string $signature The PAYMENT-SIGNATURE header value.
     * @return array<mixed>|WP_Error
     * @throws \JsonException
     */
    protected function facilitate(
        string $action,
        PaymentRequired $payment_required,
        string $signature
    ): array|WP_Error {
        return Api::wpRemote(
            Api::getApiUrl($action),
            $this->getFacilitatorData($action, $payment_required, $signature)
        );
    }

    /**
     * Build the API request data. The facilitator wants the single requirements entry the client paid
     * against (not the whole PaymentRequired object), and the client's PaymentPayload, which is the
     * PAYMENT-SIGNATURE value.
     * @param string $action
     * @param PaymentRequired $payment_required
     * @param string $signature
     * @return array<string, string>
     * @throws \JsonException
     */
    protected function getFacilitatorData(string $action, PaymentRequired $payment_required, string $signature): array
    {
        return [
            Api::ACTION => $action,
            Api::NETWORK => strtolower((new ReflectionClass(Setting::getNetwork()))->getShortName()),
            Api::PAYMENT_REQUIREMENTS => base64_encode(
                json_encode($payment_required->getAccepts()[0]->toArray(), JSON_THROW_ON_ERROR)
            ),
            Api::PAYMENT_SIGNATURE => $signature,
        ];
    }
}
