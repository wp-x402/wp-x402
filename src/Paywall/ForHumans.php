<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall;

use Dwnload\EddSoftwareLicenseManager\Edd\License;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\Models\PaymentRequired;
use WpX402\WpX402\Models\PaymentRequired\Accepts;
use WpX402\WpX402\Models\PaymentRequired\UrlResource;
use WpX402\WpX402\Networks\Mainnet;
use WpX402\WpX402\Networks\Testnet;
use WpX402\WpX402\Schema\Payment;
use WpX402\WpX402\ServiceProvider;
use WpX402\WpX402\Settings\Setting;
use function esc_html__;
use function get_permalink;
use const WpX402\WpX402\PLUGIN_SLUG;

/**
 * Class ForHumans
 * @package WpX402\WpX402\Paywall
 */
class ForHumans extends AbstractPaywall
{

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addFilter('the_content', [$this, 'theContent']);
    }

    /**
     * Redirect based on current template conditions.
     * @param string $content
     * @throws \JsonException
     * @throws \TheFrosty\WpUtilities\Exceptions\TerminationException
     * @throws \Exception
     */
    protected function theContent(string $content): string
    {
        // 1. Validate the license.
        if (!License::isActiveValid(PLUGIN_SLUG) || License::isExpired(PLUGIN_SLUG)) {
            $prefix = '<!-- x402 Error: This content can\'t be restricted. -->';
            return $prefix . $content;
        }

        // 2. Validate the wallet.
        $account = Setting::getAccount();
        $wallet = Setting::getWallet();
        $validator = $this->getContainer()?->get(ServiceProvider::WALLET_ADDRESS_VALIDATOR);
        if (!Api::isValidWallet($validator, $wallet)) {
            $prefix = '<!-- x402 Error: This content can\'t be restricted. -->';
            return $content; // @TODO we should look into doing something if a wallet is invalid
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
                    Accepts::SCHEME => Payment::from('exact')->value,
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

        return <<<HTML
            <div style="filter:blur(5px); text-shadow:0 0 2px #000; z-index:-999">$content</div>
            HTML;
    }
}
