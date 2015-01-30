<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 30/01/15 16:26
 */

namespace Modules\Admin\Tables;

use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Table\Columns\Column;

class DefaultColumn extends Column
{
    /**
     * @var \Modules\Admin\Components\ModelAdmin|\Modules\Admin\Components\NestedAdmin
     */
    public $admin;

    public function getTitle()
    {
        return $this->admin->verboseName($this->name);
    }

    public function getValue($record)
    {
        $value = $this->admin->getColumnValue($this->name, $record);
        $field = $record->getField($this->name, false);
        if ($field) {
            if (is_a($field, HasManyField::className()) || is_a($field, ManyToManyField::className())) {
                return null;
            } else if (is_a($field, BooleanField::className())) {
                return '<i class="icon-' . ($value ? 'ok' : 'cancel') . '"></i>';
            } else if (!empty($field->choices) && array_key_exists($value, $field->choices)) {
                return $field->choices[$value];
            } else {
                return $value;
            }
        } else {
            return $record->{$this->name};
        }
    }
}
