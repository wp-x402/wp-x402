<?php

declare(strict_types=1);

namespace WpX402\WpX402\Paywall;

use WP_Term;
use WpX402\WpX402\Paywall\Meta\Category;
use function carbon_get_post_meta;
use function carbon_get_term_meta;
use function filter_var;
use const FILTER_VALIDATE_BOOL;

/**
 * Class Paywall
 * @package WpX402\WpX402\Paywall
 */
class Paywall
{

    /**
     * Check all given post categories for paywall exclusion metadata.
     * @param WP_Term[] $categories
     * @return bool
     */
    public static function areCategoriesExcludedFromPaywall(array $categories): bool
    {
        foreach ($categories as $category) {
            if (self::isCategoryExcludedFromPaywall($category->term_id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check category for paywall exclusion metadata.
     * @param int $term_id
     * @return bool
     */
    public static function isCategoryExcludedFromPaywall(int $term_id): bool
    {
        return (bool)filter_var(carbon_get_term_meta($term_id, Category::NAME), FILTER_VALIDATE_BOOL);
    }

    /**
     * Check if paywall is enabled for given post.
     * @param int|false $post_id
     * @return bool
     */
    public static function isPaywallEnabled(int|false $post_id): bool
    {
        if ($post_id === false) {
            return false;
        }

        return self::isPaywallEnabledForObject($post_id);
    }

    /**
     * Check if paywall is enabled for given object id.
     * @param int $post_id
     * @return bool
     */
    private static function isPaywallEnabledForObject(int $post_id): bool
    {
        $paywall_enabled = carbon_get_post_meta($post_id, PaywallInterface::PAYWALL_ENABLED);
        return (bool)filter_var($paywall_enabled, FILTER_VALIDATE_BOOL);
    }
}
