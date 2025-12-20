<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Content;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Api\WpRemote;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
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
use function is_singular;
use function is_wp_error;
use function sprintf;
use function status_header;
use function strip_tags;
use function TheFrosty\WpUtilities\exitOrThrow;
use function TheFrosty\WpX402\telemetry;
use function wp_remote_retrieve_response_code;
use const FILTER_VALIDATE_BOOL;

/**
 * Class Payment
 * @package TheFrosty\WpX402\Content
 */
class Payment extends AbstractContainerProvider implements HttpFoundationRequestInterface
{

    use HttpFoundationRequestTrait, WpRemote;

    public const float DEFAULT_PRICE = 0.01;
    public const string TESTNET_ASSET = '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913'; // phpcs:ignore
    public const string TESTNET_WALLET = '0x505bc35f0a83c9ed06c6f94e68f0f86cf2812a6b'; // phpcs:ignore

    public function addHooks(): void
    {
        $this->addAction('template_redirect', [$this, 'templateRedirect']);
    }

    /**
     * Redirect based on current template conditions.
     * @throws \JsonException
     * @throws TheFrosty\WpUtilities\Exceptions\TerminationException
     */
    protected function templateRedirect(): void
    {
        if (is_admin() || !is_singular()) {
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
        $wallet = get_option('x402_wallet', self::TESTNET_WALLET);
        $price = get_option('x402_price', self::DEFAULT_PRICE);
        $url = Api::getApiUrl();

        // 3. Check for Payment Header.
        $payment_hash = $this->getPaymentHash($request);

        // Scenario A: No Payment Hash -> Return 402 Offer.
        if (!$payment_hash) {
            status_header(WP_Http::PAYMENT_REQUIRED);

            $data = [
                'maxAmountRequired' => $price,
                'payTo' => $wallet,
                'resource' => get_permalink(),
                'asset' => self::TESTNET_ASSET, // USDC on Base.
                'network' => 'base-mainnet',
                'description' => 'Payment required.',
            ];

            (new JsonResponse($data, WP_Http::PAYMENT_REQUIRED))->send();
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
            (new JsonResponse($data, WP_Http::BAD_REQUEST))->send();
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
            (new JsonResponse($data, WP_Http::BAD_REQUEST))->send();

            // Telemetry: Success.
            telemetry(EventType::SUCCESS, ['hash' => $payment_hash]);
            exitOrThrow();
        }

        // Payment Invalid.
        status_header(WP_Http::PAYMENT_REQUIRED);
        $data = ['error' => 'Payment Invalid or Expired'];
        (new JsonResponse($data, WP_Http::PAYMENT_REQUIRED))->send();

        // Telemetry: Failed.
        telemetry(EventType::FAILED, ['hash' => $payment_hash, 'code' => $response_code]);
        exitOrThrow();
    }

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
}
