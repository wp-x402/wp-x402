<?php

declare(strict_types=1);

namespace WpX402\WpX402\Settings;

use Dwnload\WpSettingsApi\Api\SettingField;
use Dwnload\WpSettingsApi\Api\SettingSection;
use Dwnload\WpSettingsApi\Settings\FieldManager;
use Dwnload\WpSettingsApi\Settings\FieldTypes;
use Dwnload\WpSettingsApi\Settings\SectionManager;
use Dwnload\WpSettingsApi\WpSettingsApi;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use WpX402\WpX402\Networks\Mainnet;
use WpX402\WpX402\Networks\Testnet;
use WpX402\WpX402\Paywall\PaywallInterface;
use function __;
use function esc_html__;
use function sprintf;

/**
 * Class General
 * @package WpX402\WpLoginLocker\Settings
 */
class General extends AbstractContainerProvider
{

    use ValidateSetting;

    public const string SECTION_ID = Factory::PREFIX . 'general_settings';
    public const string ACCOUNT = 'account';
    public const string NETWORK = 'network';
    public const string PRICE = 'price';
    public const string WALLET = '%s_wallet';

    /**
     * Register our callback to the WP Settings API action hook
     * `WpSettingsApi::ACTION_PREFIX . 'init'`. This custom action passes three parameters (two prior to version 2.7)
     * so you have to register a priority and the parameter count.
     */
    public function addHooks(): void
    {
        $this->addAction(WpSettingsApi::HOOK_INIT, [$this, 'init'], 10, 3);
    }

    /**
     * Initiate our setting to the Section & Field Manager classes.
     * SettingField requires the following settings (passes as an array or set explicitly):
     * [
     *  SettingField::NAME
     *  SettingField::LABEL
     *  SettingField::DESC
     *  SettingField::TYPE
     *  SettingField::SECTION_ID
     * ]
     * @param SectionManager $section_manager
     * @param FieldManager $field_manager
     * @param WpSettingsApi $wp_settings_api
     * @see SettingField for additional options for each field passed to the output
     */
    protected function init(
        SectionManager $section_manager,
        FieldManager $field_manager,
        WpSettingsApi $wp_settings_api
    ): void {
        if (!$wp_settings_api->isCurrentMenuSlug($this->getPlugin()->getSlug())) {
            return;
        }

        /**
         * Settings Section
         */
        $settings_section_id = $section_manager->addSection(
            new SettingSection([
                SettingSection::SECTION_ID => self::SECTION_ID, // Unique section ID.
                SettingSection::SECTION_TITLE => 'General Settings',
            ])
        );

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::NETWORK,
                SettingField::LABEL => esc_html__('Network', 'wp-x402'),
                SettingField::DESC => esc_html__('Blockchain infrastructure.', 'wp-x402'),
                SettingField::DEFAULT => Testnet::class,
                SettingField::TYPE => FieldTypes::FIELD_TYPE_SELECT,
                SettingField::OPTIONS => [
                    Testnet::class => esc_html__('Testnet (development, testing, or QA)', 'wp-x402'),
                    Mainnet::class => esc_html__('Mainnet (production-grade transactions)', 'wp-x402'),
                ],
                SettingField::SECTION_ID => $settings_section_id,
            ])
        );

        $accounts = Setting::getAccounts();

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::ACCOUNT,
                SettingField::LABEL => esc_html__('Account', 'wp-x402'),
                SettingField::DESC => esc_html__(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                    'Accounts refer to an address on a blockchain that has the ability to sign transactions on behalf of the address, allowing you to not only send and receive funds, but also interact with smart contracts. Cryptographically, an account corresponds to a private/public key pair.',
                    'wp-x402'
                ),
                SettingField::DEFAULT => array_key_first($accounts),
                SettingField::TYPE => FieldTypes::FIELD_TYPE_SELECT,
                SettingField::OPTIONS => $accounts,
                SettingField::SECTION_ID => $settings_section_id,
            ])
        );

        foreach ($accounts as $account => $label) {
            $field_manager->addField(
                new SettingField([
                    SettingField::NAME => sprintf(self::WALLET, $account),
                    SettingField::LABEL => sprintf('%s %s', $label, esc_html__('Wallet', 'wp-x402')),
                    SettingField::DESC => esc_html__('Merchant Wallet Address.', 'wp-x402'),
                    SettingField::DEFAULT => '',
                    SettingField::TYPE => FieldTypes::FIELD_TYPE_TEXT,
                    SettingField::SANITIZE => function (mixed $value, array $settings, string $key): string {
                        return $this->validateWallet($value, $settings, $key);
                    },
                    SettingField::SECTION_ID => $settings_section_id,
                ])
            );
        }

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::PRICE,
                SettingField::LABEL => esc_html__('Price', 'wp-x402'),
                SettingField::DESC => sprintf(
                    __('Price (<abbr title="%s">USDC</abbr>).', 'wp-x402'),
                    __(
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                        'USDC is a cryptocurrency stablecoin which is issued by Circle. It is pegged to the United States dollar, and is distinct from a central bank digital currency.',
                        'wp-x402'
                    )
                ),
                SettingField::DEFAULT => (string)PaywallInterface::DEFAULT_PRICE,
                SettingField::TYPE => FieldTypes::FIELD_TYPE_NUMBER,
                SettingField::SIZE => 'small',
                SettingField::ATTRIBUTES => [
                    'min' => '0.001',
                    'step' => '0.001',
                ],
                SettingField::SECTION_ID => $settings_section_id,
            ])
        );
    }
}
