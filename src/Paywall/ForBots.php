<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall;

use Dwnload\EddSoftwareLicenseManager\Edd\License;
use WP_Http;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Api\Bots;
use WpX402\WpX402\Models\PaymentRequired;
use WpX402\WpX402\Models\PaymentRequired\Accepts;
use WpX402\WpX402\Models\PaymentRequired\UrlResource;
use WpX402\WpX402\Networks\Mainnet;
use WpX402\WpX402\Networks\Testnet;
use WpX402\WpX402\ServiceProvider;
use WpX402\WpX402\Settings\Setting;
use WpX402\WpX402\Telemetry\EventType;
use function array_keys;
use function base64_encode;
use function esc_html__;
use function get_permalink;
use function get_post;
use function get_the_date;
use function get_the_title;
use function is_attachment;
use function is_singular;
use function is_wp_error;
use function json_encode;
use function sprintf;
use function status_header;
use function strip_tags;
use function TheFrosty\WpUtilities\exitOrThrow;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_response_code;
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

        // 1. Validate the license.
        if (!License::isActiveValid(PLUGIN_SLUG) || License::isExpired(PLUGIN_SLUG)) {
            return;
        }

        $user_agent = $this->getRequest()?->server->get('HTTP_USER_AGENT', '');
        $is_fake_bot = $this->getRequest()?->query->has('fakeBot');

        $agents = Bots::getAgents();
        if (!$agents) {
            return;
        }

        $is_bot_agent = false;
        foreach (array_keys($agents) as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                $is_bot_agent = true;
                break;
            }
        }

        if (!$is_bot_agent && !$is_fake_bot) {
            return;
        }

        // 2. Validate the wallet.
        $account = Setting::getAccount();
        $wallet = Setting::getWallet();
        $validator = $this->getContainer()?->get(ServiceProvider::WALLET_ADDRESS_VALIDATOR);
        if (!Api::isValidWallet($validator, $wallet)) {
            return; // @TODO we should look into doing something if a wallet is invalid
        }

        $is_mainnet = Setting::isMainnet();

        $payment_required = new PaymentRequired([
            PaymentRequired::ERROR => esc_html__('PAYMENT-SIGNATURE header is required', 'wp-x402'),
            PaymentRequired::RESOURCE => [
                UrlResource::URL => get_permalink(),
                UrlResource::DESCRIPTION => Entitlement::PAYMENT_REQUIRED->label(),
                UrlResource::MIME_TYPE => 'text/html',
            ],
            PaymentRequired::ACCEPTS => [
                [
                    Accepts::SCHEME => 'exact',
                    Accepts::NETWORK => $is_mainnet ?
                        Mainnet::getBase($account)->value :
                        Testnet::getBase($account)->value,
                    Accepts::AMOUNT => Setting::getPrice(),
                    Accepts::ASSET => $is_mainnet ?
                        Mainnet::getAsset($account)->value :
                        Testnet::getAsset($account)->value,
                    Accepts::PAY_TO => $wallet,
                    Accepts::MAX_TIMEOUT_SECONDS => 60,
                    Accepts::EXTRA => [
                        'name' => 'USDC',
                        'version' => 2,
                    ],
                ],
            ],
        ]);

        // 3. Check for Payment Header.
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
                    Accepts::NETWORK => $is_mainnet ?
                        Mainnet::getBase($account)->value :
                        Testnet::getBase($account)->value,
                    UrlResource::URL => $payment_required->getResource()->getUrl(),
                ]
            );
            exitOrThrow();
        }

        // Scenario B: Verify Payment Hash.
        $response = Api::wpRemote(
            Api::getApiUrl(Api::ACTION_VERIFY),
            [
                Api::ACTION => Api::ACTION_VERIFY,
                Api::PAYMENT_REQUIREMENTS => base64_encode(
                    json_encode($payment_required->toArray(), JSON_THROW_ON_ERROR)
                ),
                Api::PAYMENT_SIGNATURE => $paymentSignature,
            ]
        );

        if (is_wp_error($response)) {
            status_header(WP_Http::BAD_REQUEST);
            $data = [
                PaymentRequired::ERROR => sprintf('Bad request: %s', $response->get_error_message()),
            ];
            $this->sendJsonResponse($data, WP_Http::BAD_REQUEST);
            exitOrThrow();
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code === WP_Http::ACCEPTED) {
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
            $this->sendJsonResponse($data, WP_Http::OK, [
                Api::HEADER_PAYMENT_RESPONSE => wp_remote_retrieve_body($response),
            ]);

            // Telemetry: Success.
            telemetry(EventType::SUCCESS, ['signature' => $paymentSignature]);
            exitOrThrow();
        }

        // Payment Invalid (likely).
        status_header(WP_Http::PAYMENT_REQUIRED);
        $payment_required->setError(esc_html__('Payment Invalid or Expired.', 'wp-x402'));

        $data = [PaymentRequired::ERROR => $payment_required->getError()];
        if (true) { // @TODO: Create a debug function for local development.
            $data['extra'] = ['code' => $response_code, 'message' => json_decode(wp_remote_retrieve_body($response))];
        }
        $this->sendJsonResponse(
            $data,
            WP_Http::PAYMENT_REQUIRED,
            [
                Api::HEADER_PAYMENT_RESPONSE => base64_encode(
                    json_encode($payment_required->toArray(), JSON_THROW_ON_ERROR)
                ),
            ]
        );

        // Telemetry: Failed.
        telemetry(EventType::FAILED, ['signature' => $paymentSignature, 'code' => $response_code]);
        exitOrThrow();
    }
}
