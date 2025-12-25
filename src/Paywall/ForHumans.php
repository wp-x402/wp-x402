<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Paywall;

use TheFrosty\WpX402\Api\Api;
use TheFrosty\WpX402\Models\PaymentRequired;
use TheFrosty\WpX402\Models\PaymentRequired\Accepts;
use TheFrosty\WpX402\Models\PaymentRequired\UrlResource;
use TheFrosty\WpX402\Networks\Mainnet;
use TheFrosty\WpX402\Networks\Testnet;
use TheFrosty\WpX402\ServiceProvider;
use TheFrosty\WpX402\Settings\Settings;
use function esc_html__;
use function get_permalink;

/**
 * Class ForHumans
 * @package TheFrosty\WpX402\Paywall
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
     * @throws \JsonException
     * @throws \TheFrosty\WpUtilities\Exceptions\TerminationException
     * @throws \Exception
     */
    protected function theContent(string $content): string
    {
        $wallet = Settings::getWallet();
        $validator = $this->getContainer()?->get(ServiceProvider::WALLET_ADDRESS_VALIDATOR);
        if (!Api::isValidWallet($validator, $wallet)) {
            return $content; // @TODO we should look into doing something if a wallet is invalid
        }

        $is_mainnet = Settings::isMainnet();

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
                    Accepts::NETWORK => $is_mainnet ? Mainnet::BASE->value : Testnet::BASE->value,
                    Accepts::AMOUNT => Settings::getPrice(),
                    Accepts::ASSET => $is_mainnet ? Mainnet::ASSET_BASE->value : Testnet::ASSET_BASE->value,
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
