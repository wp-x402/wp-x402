<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Settings;

use Dwnload\WpSettingsApi\Api\Options;
use Dwnload\WpSettingsApi\Api\PluginSettings;
use Dwnload\WpSettingsApi\Api\SettingField;
use Dwnload\WpSettingsApi\Api\SettingSection;
use Dwnload\WpSettingsApi\Settings\FieldManager;
use Dwnload\WpSettingsApi\Settings\FieldTypes;
use Dwnload\WpSettingsApi\Settings\SectionManager;
use Dwnload\WpSettingsApi\SettingsApiFactory;
use Dwnload\WpSettingsApi\WpSettingsApi;
use NumberFormatter;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use TheFrosty\WpX402\Api\Api;
use TheFrosty\WpX402\Networks\Mainnet;
use TheFrosty\WpX402\Networks\Testnet;
use TheFrosty\WpX402\Paywall\PaywallInterface;
use TheFrosty\WpX402\ServiceProvider;
use function __;
use function array_unshift;
use function esc_attr__;
use function esc_html__;
use function menu_page_url;
use function sanitize_text_field;
use function sprintf;

/**
 * Class Settings
 * @package TheFrosty\WpLoginLocker\Settings
 */
class Settings extends AbstractContainerProvider
{
    public const string GENERAL_SETTINGS = self::PREFIX . 'general_settings';
    public const string WALLET = 'wallet';
    public const string NETWORK = 'network';
    public const string PRICE = 'price';
    private const string PREFIX = 'wp_x402_';
    private const string DOMAIN = 'wp-x402';
    private const string MENU_SLUG = self::DOMAIN . '-settings';

    /**
     * Creat the PluginSettings object.
     * @param string $version
     * @return PluginSettings
     */
    public static function factory(string $version): PluginSettings
    {
        return SettingsApiFactory::create([
            'domain' => self::DOMAIN,
            'file' => __FILE__, // Path to WpSettingsApi file (not required, see README for more info).
            'menu-slug' => self::MENU_SLUG,
            'menu-title' => 'x402', // Title found in menu.
            'page-title' => 'x402 Settings', // Title output at top of settings page.
            'prefix' => self::PREFIX,
            'version' => $version,
        ]);
    }

    /**
     * Return the wallet setting.
     * @return string
     */
    public static function getWallet(): string
    {
        return sanitize_text_field(self::getSetting(self::WALLET, PaywallInterface::TESTNET_WALLET));
    }

    /**
     * Return the network setting.
     * @return string
     */
    public static function getNetwork(): string
    {
        return sanitize_text_field(self::getSetting(self::NETWORK, 'testnet'));
    }

    /**
     * Return the price setting.
     * @return string
     */
    public static function getPrice(): string
    {
        $price = (float)self::getSetting(self::PRICE, PaywallInterface::DEFAULT_PRICE);
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        return sanitize_text_field($formatter->formatCurrency($price, 'USD'));
    }

    /**
     * Is the Mainnet selected?
     * @return bool
     */
    public static function isMainnet(): bool
    {
        return self::getNetwork() === Mainnet::class;
    }

    /**
     * Get our option key in our general setting index.
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function getSetting(string $key, mixed $default = null): mixed
    {
        return Options::getOption($key, self::GENERAL_SETTINGS, $default);
    }

    /**
     * Register our callback to the WP Settings API action hook
     * `WpSettingsApi::ACTION_PREFIX . 'init'`. This custom action passes three parameters (two prior to version 2.7)
     * so you have to register a priority and the parameter count.
     */
    public function addHooks(): void
    {
        $this->addAction(WpSettingsApi::HOOK_INIT, [$this, 'init'], 10, 3);
        $this->addFilter('plugin_action_links_' . $this->getPlugin()->getBasename(), [$this, 'addSettingsLink']);
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
        if (!$wp_settings_api->isCurrentMenuSlug(self::MENU_SLUG)) {
            return;
        }

        /**
         * Settings Section
         */
        $settings_section_id = $section_manager->addSection(
            new SettingSection([
                SettingSection::SECTION_ID => self::GENERAL_SETTINGS, // Unique section ID.
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

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::WALLET,
                SettingField::LABEL => esc_html__('Wallet', 'wp-x402'),
                SettingField::DESC => esc_html__('Merchant Wallet Address.', 'wp-x402'),
                SettingField::DEFAULT => PaywallInterface::TESTNET_WALLET,
                SettingField::TYPE => FieldTypes::FIELD_TYPE_TEXT,
                SettingField::SANITIZE => function (mixed $value): string {
                    $validator = $this->getContainer()?->get(ServiceProvider::WALLET_ADDRESS_VALIDATOR);
                    if (Api::isValidWallet($validator, $value)) {
                        return sanitize_text_field($value);
                    }

                    add_settings_error(
                        self::WALLET,
                        'invalid_wallet_address',
                        esc_html__('Invalid or unsupported wallet address.', 'wp-x402')
                    );

                    return '';
                },
                SettingField::SECTION_ID => $settings_section_id,
            ])
        );

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

    /**
     * Add settings page link to the plugins page.
     * @param array $actions
     * @return array
     */
    protected function addSettingsLink(array $actions): array
    {
        array_unshift(
            $actions,
            sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                menu_page_url(self::MENU_SLUG, false),
                esc_attr__('Settings for x402', 'wp-x402'),
                esc_html__('Settings', 'wp-x402')
            ),
        );

        return $actions;
    }
}
