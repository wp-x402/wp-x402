<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall\Meta;

use WpX402\WpX402\Integration\CarbonFields\CarbonFields;
use WpX402\WpX402\Paywall\Paywall;
use function esc_attr__;
use function esc_html;
use function esc_html__;
use function wp_doing_ajax;

/**
 * Class Category
 * @package WpX402\WpX402\Paywall\Meta
 */
class Category extends CarbonFields
{

    public const string COLUMN_HEADER = 'x402';
    public const string NAME = 'remove_paywall';

    public function addHooks(): void
    {
        parent::addHooks();
        $this->addFilter('manage_edit-category_columns', [$this, 'addColumnHeader']);
        $this->addFilter('manage_category_custom_column', [$this, 'addColumnStatus'], 10, 3);
        $this->addAction('admin_head-edit-tags.php', [$this, 'addStyles']);
    }

    /**
     * Register our Category fields.
     * @throws \Carbon_Fields\Exception\Incorrect_Syntax_Exception
     */
    protected function registerFields(): void
    {
        $this->termMetaContainer(esc_html('x402 Paywall Settings'))
            ->where('term_taxonomy', '=', 'category')
            ->add_fields($this->addFields());
    }

    /**
     * Adds x402 column header to category view.
     * @param array $columns
     * @return array
     */
    protected function addColumnHeader(array $columns): array
    {
        $columns[self::COLUMN_HEADER] = 'x402';
        return $columns;
    }

    /**
     * Adds a green or red icon to represent whether category has x402 exclusion.
     * @param mixed $content The content.
     * @param string $column_name The column name.
     * @param int $term_id The cat term ID.
     * @return mixed
     */
    protected function addColumnStatus(mixed $content, string $column_name, int $term_id): mixed
    {
        if ($column_name === self::COLUMN_HEADER) {
            $content = sprintf(
                '<span class="dashicons dashicons-money-alt x402-green" 
title="%s"></span>',
                esc_attr__('Not excluded from x402 Paywall', 'wp-x402')
            );
            if (Paywall::isCategoryExcludedFromPaywall($term_id)) {
                $content = sprintf(
                    '<span class="dashicons dashicons-yes-alt x402-red" title="%s"></span>',
                    esc_attr__('Excluded from x402 Paywall', 'wp-x402')
                );
            }
        }

        return $content;
    }

    /**
     * Adds Status Column styles for category view.
     */
    protected function addStyles(): void
    {
        if (wp_doing_ajax()) {
            return;
        }
        echo <<<STYLE
            <style>
                #x402 {
                    width: 45px;
                }
                .term_x402 {
                    text-align: center;
                }
                .x402-red {
                    color: #F94449;
                }    
                .x402-green {
                    color: #6FC276;
                    font-weight: bolder;
                }
                .x402-has-tooltip {
                    position: relative;
                    text-decoration: line-through;
                }
                .x402-tooltip {
                    position: absolute;
                    top: -325%;
                    left: -50%;
                    display: none;
                    width: 200%;
                    height: 37px;
                    padding: 10%;
                    text-align: center;
                    color: white;
                    font-size: 0.75em;
                    line-height: 1.1em;
                    background: rgba(0, 0, 0, 0.75);
                    border-radius: 3px;
                }
                .x402-tooltip:before {
                    position: absolute;
                    bottom: -10px;
                    left: 40%;
                    display: block;
                    width: 0;
                    height: 0;
                    content: '';
                    border-width: 5px;
                    border-style: solid;
                    border-color: rgba(0, 0, 0, 0.75) transparent transparent transparent;
                }
                .x402-has-tooltip:hover .x402-tooltip {
                    display: block;
                }
            </style>
            STYLE;
    }

    /**
     * Get our fields array.
     * @return \Carbon_Fields\Field\Field[]
     */
    private function addFields(): array
    {
        return [
            $this->createCheckboxField(
                self::NAME,
                esc_html__('Remove Paywall from Category', 'wp-x402')
            )->set_option_value('yes')->set_default_value('')->set_visible_in_rest_api(),
        ];
    }
}
