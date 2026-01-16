<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall\Meta;

use Carbon_Fields\Exception\Incorrect_Syntax_Exception;
use Carbon_Fields\Field\Field;
use WpX402\WpX402\Integration\CarbonFields\CarbonFields;
use WpX402\WpX402\Paywall\PaywallInterface;
use function esc_html;

/**
 * Class Post
 * @package WpX402\WpX402\Paywall\Meta
 */
class Post extends CarbonFields
{

    /**
     * @throws Incorrect_Syntax_Exception
     */
    protected function registerFields(): void
    {
        $this->postMetaContainer(esc_html('x402 Paywall Settings'))
            ->where('post_type', '=', 'post')
            ->add_fields($this->getFields())
            ->set_context('side')
            ->set_priority('low');
    }

    /**
     * Get our fields array.
     * @return Field[]
     */
    private function getFields(): array
    {
        return [
            $this->createCheckboxField(
                PaywallInterface::PAYWALL_ENABLED,
                esc_html__('Enable Paywall', 'wp-x402')
            )->set_option_value('yes')->set_default_value('yes')->set_visible_in_rest_api(),
        ];
    }
}
