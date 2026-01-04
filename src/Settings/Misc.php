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
use WpX402\WpX402\Api\Bots;
use function array_keys;
use function esc_html__;

/**
 * Class Misc
 * @package WpX402\WpLoginLocker\Settings
 */
class Misc extends AbstractContainerProvider
{

    use ValidateSetting;

    public const string SECTION_ID = Factory::PREFIX . 'misc_settings';
    public const string TELEMETRY = 'telemetry';

    /**
     * Register our callback to the WP Settings API action hook
     * `WpSettingsApi::ACTION_PREFIX . 'init'`. This custom action passes three parameters (two prior to version 2.7)
     * so you have to register a priority and the parameter count.
     */
    public function addHooks(): void
    {
        $this->addAction(WpSettingsApi::HOOK_INIT, [$this, 'init'], 12, 3);
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
                SettingSection::SECTION_TITLE => 'Misc Settings',
            ])
        );

        $field_manager->addField(
            new SettingField([
                SettingField::NAME => self::TELEMETRY,
                SettingField::LABEL => esc_html__('Allow telemetry', 'wp-x402'),
                SettingField::DESC => esc_html__('Check this to enable telemetry.', 'wp-x402'),
                SettingField::DEFAULT => 'off',
                SettingField::TYPE => FieldTypes::FIELD_TYPE_CHECKBOX,
                SettingField::SECTION_ID => $settings_section_id,
            ])
        );
    }

    /**
     * Build a value for the "agents" HTML setting.
     * @return string
     */
    private function getDefault(): string
    {
        global $pagenow;
        static $html = '';

        if ($pagenow !== 'options-general.php' || $html !== '') {
            return $html;
        }

        $agents = Bots::getAgents();
        $html .= '<ul style="list-style-type: disc;">';
        foreach (array_keys($agents) as $agent) {
            $html .= "<li>$agent</li>";
        }
        $html .= '<ul>';

        return $html;
    }
}
