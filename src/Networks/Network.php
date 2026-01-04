<?php

declare(strict_types=1);

namespace WpX402\WpX402\Networks;

/**
 * Interface Network
 * @package WpX402\WpX402\Networks
 */
interface Network
{

    /**
     * Get Enum member fetch.
     * @ref https://php.watch/versions/8.3/dynamic-class-const-enum-member-syntax-support
     * @param string $asset
     * @return self
     */
    public static function getAsset(string $asset): self;

    /**
     * Get Enum member fetch.
     * @ref https://php.watch/versions/8.3/dynamic-class-const-enum-member-syntax-support
     * @param string $asset
     * @return self
     */
    public static function getBase(string $asset): self;
}
