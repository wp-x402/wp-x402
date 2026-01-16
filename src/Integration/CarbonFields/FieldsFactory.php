<?php

declare(strict_types=1);

namespace WpX402\WpX402\Integration\CarbonFields;

use Carbon_Fields\Field\Checkbox_Field;
use Carbon_Fields\Field\Field;

/**
 * Trait FieldsFactory
 * @package WpX402\WpX402\Integration\CarbonFields
 */
trait FieldsFactory
{

    /**
     * Create a checkbox field.
     * @param string $name
     * @param string $label
     * @return Checkbox_Field
     */
    protected function createCheckboxField(string $name, string $label): Field
    {
        return Checkbox_Field::factory(FieldsInterface::CHECKBOX, $name, $label);
    }
}
