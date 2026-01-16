<?php

declare(strict_types=1);

namespace WpX402\WpX402\Integration\CarbonFields;

/**
 * Interface TypeInterface
 * @package WpX402\WpX402\Integration\CarbonFields
 */
interface TypeInterface
{
    final public const string COMMENT_META = 'comment_meta';
    final public const string POST_META = 'post_meta';
    final public const string TERM_META = 'term_meta';
    final public const string THEME_OPTIONS = 'theme_options';
    final public const string USER_META = 'user_meta';
}
