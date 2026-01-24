<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall\Meta;

use TheFrosty\WpUtilities\WpAdmin\Models\OptionValueLabel;
use TheFrosty\WpUtilities\WpAdmin\RestrictPostsInterface;
use WpX402\WpX402\Integration\CarbonFields\CarbonFields;
use WpX402\WpX402\Paywall\PaywallInterface;
use function array_filter;
use function esc_html;

/**
 * Class Post
 * @package WpX402\WpX402\Paywall\Meta
 */
class Post extends CarbonFields
{

    protected const string DEFAULT_VALUE = 'yes';

    public function addHooks(): void
    {
        parent::addHooks();
        $this->addFilter(RestrictPostsInterface::TAG_FILTER_ADVANCED_SEARCH, '__return_false');
        $this->addFilter(RestrictPostsInterface::TAG_FILTER_META_KEYS, [$this, 'filterKeys'], 10, 2);
        $this->addFilter(RestrictPostsInterface::TAG_FILTER_META_VALUES, [$this, 'filterValues'], 10, 2);
        $this->addFilter(RestrictPostsInterface::TAG_FILTER_SCRIPT_DEPENDENCIES, [$this, 'filterDependencies']);
    }

    /**
     * Add the custom fields to the array of filterable options.
     * @param array $options
     * @param string $post_type
     * @return array
     */
    protected function filterKeys(array $options, string $post_type): array
    {
        if ($post_type === 'post') {
            $options[$this->getName(PaywallInterface::PAYWALL_ENABLED)] = [
                new OptionValueLabel(
                    $this->getName(PaywallInterface::PAYWALL_ENABLED), esc_html__('Paywall', 'wp-x402')
                ),
            ];
        }

        return $options;
    }

    /**
     * Add programs to the array of filterable options.
     * @param array $options
     * @param string $post_type
     * @return array
     */
    protected function filterValues(array $options, string $post_type): array
    {
        if ($post_type === 'post') {
            $options[$this->getName(PaywallInterface::PAYWALL_ENABLED)] = [
                new OptionValueLabel(self::DEFAULT_VALUE, 'Enabled'),
                new OptionValueLabel('COMPARE:=:', 'Disabled'),
                new OptionValueLabel('COMPARE:NOT EXISTS:NULL', 'Not Set'),
            ];
        }

        return $options;
    }

    /**
     * Remove 'select2' as a dependency.
     * @param array $dependencies
     * @return array
     */
    protected function filterDependencies(array $dependencies): array
    {
        return array_filter($dependencies, static fn(string $value): bool => $value !== 'select2');
    }

    /**
     * Register our Post fields.
     * @throws \Carbon_Fields\Exception\Incorrect_Syntax_Exception
     */
    protected function registerFields(): void
    {
        $this->postMetaContainer(esc_html('x402 Paywall Settings'))
            ->where('post_type', '=', 'post')
            ->add_fields($this->addFields())
            ->set_context('side')
            ->set_priority('low');
    }

    /**
     * Get our fields array.
     * @return \Carbon_Fields\Field\Field[]
     */
    private function addFields(): array
    {
        return [
            $this->createCheckboxField(
                PaywallInterface::PAYWALL_ENABLED,
                esc_html__('Enable Paywall', 'wp-x402')
            )->set_option_value(self::DEFAULT_VALUE)->set_default_value(self::DEFAULT_VALUE)->set_visible_in_rest_api(),
        ];
    }
}
