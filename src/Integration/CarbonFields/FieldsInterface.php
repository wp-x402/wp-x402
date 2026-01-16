<?php

declare(strict_types=1);

namespace WpX402\WpX402\Integration\CarbonFields;

/**
 * Interface TypeInterface
 * @package WpX402\WpX402\Integration
 */
interface FieldsInterface
{
    final public const string ASSOCIATION = 'association';
    final public const string CHECKBOX = 'checkbox';
    final public const string COLOR = 'color';
    final public const string DATE = 'date';
    final public const string DATE_TIME = 'date_time';
    final public const string FILE = 'file';
    final public const string HIDDEN = 'hidden';
    final public const string HTML = 'html';
    final public const string RADIO = 'radio';
    final public const string SELECT = 'select';
    final public const string TEXT = 'text';
    final public const string TEXTAREA = 'textarea';
    final public const string TIME = 'time';
}
