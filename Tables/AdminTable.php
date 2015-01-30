<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 30/01/15 11:14
 */

namespace Modules\Admin\Tables;

use Mindy\Table\Columns\TemplateColumn;
use Mindy\Table\Table;
use Modules\Admin\Components\ModelAdmin;

class AdminTable extends Table
{
    private $_dynamicColumns = [];
    /**
     * @var ModelAdmin
     */
    protected $admin;

    public $sortingColumn;

    public $html = [
        'id' => 'table-main',
        'data-toggle' => 'checkboxes',
        'data-range' => 'true'
    ];

    public function setAdmin(ModelAdmin $admin)
    {
        $this->admin = $admin;
    }

    public function getColumns()
    {
        $columns = array_merge($this->_dynamicColumns, [
            'actions' => [
                'class' => TemplateColumn::className(),
                'template' => "admin/admin/_actions.html",
                'html' => [
                    'class' => 'actions'
                ],
                'extra' => [
                    'admin' => $this->admin,
                    'adminClass' => $this->admin->classNameShort(),
                    'moduleName' => $this->admin->getModule()->classNameShort()
                ],
                'virtual' => true
            ]
        ]);

        $columns = array_merge([
            'check' => [
                'class' => CheckColumn::className(),
                'length' => $this->count()
            ]
        ], $columns);

        if ($this->sortingColumn) {
            $columns = array_merge([
                'sorting' => [
                    'class' => CheckColumn::className(),
                    'template' => "admin/admin/_sorting_column.html",
                    'html' => [
                        'class' => 'sorting',
                    ],
                    'virtual' => true
                ]
            ], $columns);
        }

        return $columns;
    }

    /**
     * @param $record
     * @return array
     */
    public function getRowHtmlAttributes($record)
    {
        return [
            // For checkboxes
            'data-pk' => $record->pk
        ];
    }

    public function setColumns(array $columns)
    {
        $this->_dynamicColumns = $columns;
    }
}
