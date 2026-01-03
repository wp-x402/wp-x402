<?php

declare(strict_types=1);

namespace WpX402\WpX402\Settings;

use WpX402\WpX402\Api\Api;
use WpX402\WpX402\ServiceProvider;
use function sprintf;
use function str_replace;

/**
 * Trait Validation
 * @package WpX402\WpX402\Settings
 */
trait ValidateSetting
{

    /**
     * Validate the wallet setting value.
     * @param mixed $value The passed value.
     * @param array $settings The settings $_POST array.
     * @param string $key The current settings key.
     * @return string
     */
    protected function validateWallet(mixed $value, array $settings, string $key): string
    {
        $validator = $this->getContainer()?->get(ServiceProvider::WALLET_ADDRESS_VALIDATOR);
        if (Api::isValidWallet($validator, $value)) {
            return sanitize_text_field($value);
        }

        // Don't add an error notice on empty value.
        if ($value === '') {
            return $value;
        }

        add_settings_error(
            $key,
            'invalid_wallet_address',
            sprintf(
                esc_html__('%s: Invalid or unsupported wallet address.', 'wp-x402'),
                Setting::getAccounts()[str_replace('_wallet', '', $key)]
            ),
        );

        return '';
    }
}
