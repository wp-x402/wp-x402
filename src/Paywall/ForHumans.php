<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall;

use Dwnload\EddSoftwareLicenseManager\Edd\License;
use WpX402\WpX402\Api\Api;
use WpX402\WpX402\ServiceProvider;
use WpX402\WpX402\Settings\Setting;
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
        // 1. Validate the wallet.
        $wallet = Setting::getWallet();
        $validator = $this->getContainer()?->get(ServiceProvider::WALLET_ADDRESS_VALIDATOR);
        if (!Api::isValidWallet($validator, $wallet)) {
            $prefix = '<!-- x402 Error: Invalid Wallet Address. -->';
            return $prefix . $content; // @TODO we should look into doing something if a wallet is invalid
        }

        // 2. Validate paywall enabled for object or category.
        if (
            !Paywall::isPaywallEnabled(get_the_ID()) &&
            Paywall::areCategoriesExcludedFromPaywall(get_the_category(get_the_ID()))
        ) {
            $prefix = '<!-- x402 Notice: Paywall Disabled for Object or Excluded by Category. -->';
            return $prefix . $content;
        }

        // 3. Validate the license.
        if (!License::isActiveValid(PLUGIN_SLUG) || License::isExpired(PLUGIN_SLUG)) {
            $prefix = '<!-- x402 Error: Invalid or Expired License. -->';
            return $prefix . $content;
        }

        return <<<HTML
            <div style="filter:blur(5px); text-shadow:0 0 2px #000; z-index:-999">$content</div>
            HTML;
    }
}
