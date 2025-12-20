<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Paywall;

use TheFrosty\WpX402\Api\Api;
use TheFrosty\WpX402\Api\Bots;
use TheFrosty\WpX402\Telemetry\EventType;
use WP_Http;
use function array_keys;
use function get_permalink;
use function get_post;
use function get_the_date;
use function get_the_ID;
use function get_the_title;
use function is_attachment;
use function is_singular;
use function is_wp_error;
use function sprintf;
use function status_header;
use function strip_tags;
use function TheFrosty\WpUtilities\exitOrThrow;
use function TheFrosty\WpX402\getPrice;
use function TheFrosty\WpX402\getWallet;
use function TheFrosty\WpX402\telemetry;
use function wp_remote_retrieve_response_code;
use const FILTER_VALIDATE_BOOL;

/**
 * Class Payment
 * @package TheFrosty\WpX402\Paywall
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
     */
    protected function templateRedirect(): void
    {
        if (is_admin() || (!is_singular() || is_attachment())) {
            return;
        }

        $request = $this->getRequest();
        $is_bot_param = filter_var($request?->query->get('bot'), FILTER_VALIDATE_BOOL) === true;
        $user_agent = $request?->server->get('HTTP_USER_AGENT', '');

        // --- SEO WHITELIST ---
        // Allow Googlebot (Search Indexing) to access content for free.
        // BUT block Google-Extended (AI Training) -> they must pay.
        if (stripos($user_agent, 'Googlebot') !== false && stripos($user_agent, 'Google-Extended') === false) {
            return;
        }

        $agents = Bots::getAgents();
        if (!$agents) {
            return;
        }

        $is_bot_agent = (bool)$request?->query->get('fakeBot', false);
        foreach (array_keys($agents) as $agent) {
            if (stripos($user_agent, $agent) !== false) {
                $is_bot_agent = true;
                break;
            }
        }

        if (!$is_bot_param && !$is_bot_agent) {
            return;
        }

        // 2. Retrieve Settings.
        $url = Api::getApiUrl();

        // 3. Check for Payment Header.
        $payment_hash = $this->getPaymentHash($request);

        // Scenario A: No Payment Hash -> Return 402 Offer.
        if (!$payment_hash) {
            status_header(WP_Http::PAYMENT_REQUIRED);

            $data = [
                'maxAmountRequired' => getPrice(),
                'payTo' => getWallet(),
                'resource' => get_permalink(),
                'asset' => self::TESTNET_ASSET, // USDC on Base.
                'network' => 'base-mainnet',
                'description' => 'Payment required.',
            ];

            $this->sendJsonResponse($data, WP_Http::PAYMENT_REQUIRED);
            // Telemetry: Impression.
            telemetry(EventType::REQUIRED, ['url' => get_permalink()]);
            exitOrThrow();
        }

        // Scenario B: Verify Payment Hash with Brain (Backend).
        $response = Api::wpRemote($url, [Api::ACTION => Api::ACTION_VERIFY, 'paymentHash' => $payment_hash]);

        if (is_wp_error($response)) {
            status_header(WP_Http::BAD_REQUEST);
            $data = [
                'error' => sprintf('Bad request: %s', $response->get_error_message()),
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
                'id' => get_the_ID($post),
                'date' => get_the_date('c', $post),
            ];
            $this->sendJsonResponse($data, WP_Http::BAD_REQUEST);

            // Telemetry: Success.
            telemetry(EventType::SUCCESS, ['hash' => $payment_hash]);
            exitOrThrow();
        }

        // Payment Invalid.
        status_header(WP_Http::PAYMENT_REQUIRED);
        $data = ['error' => 'Payment Invalid or Expired'];
        $this->sendJsonResponse($data, WP_Http::PAYMENT_REQUIRED);

        // Telemetry: Failed.
        telemetry(EventType::FAILED, ['hash' => $payment_hash, 'code' => $response_code]);
        exitOrThrow();
    }
}
