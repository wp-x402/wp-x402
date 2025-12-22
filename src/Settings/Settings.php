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
use function add_settings_error;
use function array_unshift;
use function esc_attr__;
use function esc_html__;
use function menu_page_url;
use function sanitize_text_field;
use function sprintf;
use function str_replace;
use function strtoupper;
use function wp_add_inline_script;
use function wp_enqueue_script;
use function wp_register_script;

/**
 * Class Settings
 * @package TheFrosty\WpLoginLocker\Settings
 */
class Settings extends AbstractContainerProvider
{
    public const string GENERAL_SETTINGS = self::PREFIX . 'general_settings';
    public const string ACCOUNT = 'account';
    public const string NETWORK = 'network';
    public const string PRICE = 'price';
    public const string WALLET = '%s_wallet';
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
        $account = self::getSetting(self::ACCOUNT, array_key_first((new static())->getAccounts()));
        return sanitize_text_field(self::getSetting(sprintf(self::WALLET, $account), ''));
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
        $this->addAction('admin_enqueue_scripts', [$this, 'adminScripts']);
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

        $accounts = $this->getAccounts();

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

        foreach ($this->getAccounts() as $account => $label) {
            $field_manager->addField(
                new SettingField([
                    SettingField::NAME => sprintf(self::WALLET, $account),
                    SettingField::LABEL => sprintf('%s %s', $label, esc_html__('Wallet', 'wp-x402')),
                    SettingField::DESC => esc_html__('Merchant Wallet Address.', 'wp-x402'),
                    SettingField::DEFAULT => Testnet::tryValue(sprintf('ASSET_%s', strtoupper($account))),
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

    protected function adminScripts(string $hook): void
    {
        if ($hook !== 'settings_page_' . self::MENU_SLUG) {
            return;
        }

        wp_register_script(self::DOMAIN, '');
        wp_enqueue_script(self::DOMAIN);
        $data = <<<'SCRIPT'
document.addEventListener('DOMContentLoaded', function () {
  const select = document.querySelector('select[name*="account"]')
  if (select) {
    const wallets = document.querySelectorAll('input[name*="_wallet"]')
    wallets.forEach((wallet) => {
      if (!wallet.id.includes(select.value)) {
        wallet.closest('tr').style.display = 'none'
      }
    })
  }
  select.addEventListener('change', function () {
    const wallets = document.querySelectorAll('input[name*="_wallet"]')
    wallets.forEach((wallet) => {
      if (!wallet.id.includes(this.value)) {
        wallet.closest('tr').style.display = 'none'
        return
      }
      wallet.closest('tr').style.display = ''
    })
  })
})

SCRIPT;
        wp_add_inline_script(self::DOMAIN, $data);
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
                $this->getAccounts()[str_replace('_wallet', '', $key)]
            ),
        );

        return '';
    }

    /**
     * Get allowed accounts.
     * @return array
     */
    private function getAccounts(): array
    {
        return [
            'base' => esc_html__('EVM', 'wp-x402'),
            'solana' => esc_html__('Solana', 'wp-x402'),
        ];
    }
}
