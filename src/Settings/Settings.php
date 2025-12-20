<?php

declare(strict_types=1);

namespace TheFrosty\WpX402\Settings;

use Dwnload\WpSettingsApi\Api\PluginSettings;
use Dwnload\WpSettingsApi\Api\SettingField;
use Dwnload\WpSettingsApi\Api\SettingSection;
use Dwnload\WpSettingsApi\Settings\FieldManager;
use Dwnload\WpSettingsApi\Settings\FieldTypes;
use Dwnload\WpSettingsApi\Settings\SectionManager;
use Dwnload\WpSettingsApi\SettingsApiFactory;
use Dwnload\WpSettingsApi\WpSettingsApi;
use Multicoin\AddressValidator\CurrencyFactory;
use Multicoin\AddressValidator\WalletAddressValidator;
use TheFrosty\WpUtilities\Plugin\AbstractHookProvider;
use TheFrosty\WpX402\Paywall\PaywallInterface;
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
class Settings extends AbstractHookProvider
{
    public const string GENERAL_SETTINGS = self::PREFIX . 'general_settings';
    public const string WALLET = 'wallet';
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
                SettingField::NAME => self::WALLET,
                SettingField::LABEL => esc_html__('Wallet', 'wp-x402'),
                SettingField::DESC => esc_html__('Merchant Wallet Address.', 'wp-x402'),
                SettingField::DEFAULT => PaywallInterface::TESTNET_WALLET,
                SettingField::TYPE => FieldTypes::FIELD_TYPE_TEXT,
                SettingField::SANITIZE => static function (mixed $value): string {
                    $validator = new WalletAddressValidator(CurrencyFactory::createRegistry());
                    if (
                        $validator->validate($value, 'eth') ||
                        $validator->validate($value, 'sol')
                    ) {
                        return sanitize_text_field($value);
                    }

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
                    // phpcs:ignore Generic.Files.LineLength.TooLong
                    __(
                        'USDC is a cryptocurrency stablecoin which is issued by Circle. It is pegged to the United States dollar, and is distinct from a central bank digital currency.',
                        'wp-x402'
                    )
                ),
                SettingField::DEFAULT => PaywallInterface::DEFAULT_PRICE,
                SettingField::TYPE => FieldTypes::FIELD_TYPE_NUMBER,
                SettingField::ATTRIBUTES => [
                    'min' => '0.01',
                    'step' => '0.01',
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
