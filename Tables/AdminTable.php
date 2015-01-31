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

use Mindy\Base\Mindy;
use Mindy\Orm\TreeModel;
use Mindy\Table\Columns\LinkColumn;
use Mindy\Table\Columns\TemplateColumn;
use Mindy\Table\Table;
use Modules\Admin\Components\ModelAdmin;

class AdminTable extends Table
{
    /**
     * @var string
     */
    public $moduleName;
    /**
     * @var string
     */
    public $sortingColumn;
    /**
     * @var array
     */
    public $html = [
        'id' => 'table-main',
        'data-toggle' => 'checkboxes',
        'data-range' => 'true'
    ];
    /**
     * @var string
     */
    public $currentOrder;
    /**
     * @var ModelAdmin
     */
    protected $admin;
    /**
     * @var array
     */
    private $_dynamicColumns = [];

    public function init()
    {
        $this->html = array_merge($this->html, [
            'class' => $this->sortingColumn ? 'sortingColumn' : ''
        ]);
    }

    public function setAdmin(ModelAdmin $admin)
    {
        $this->admin = $admin;
    }

    public function getColumns()
    {
        $admin = $this->admin;
        $adminClass = $admin->classNameShort();
        $moduleName = $this->moduleName;

        $rawColumns = [];
        foreach($this->_dynamicColumns as $column) {
            $rawColumns[$column] = [
                'class' => AdminRawColumn::className(),
                'name' => $column,
                'admin' => $admin,
                'moduleName' => $moduleName,
                'currentOrder' => $this->currentOrder
            ];
        }

        $columns = array_merge([
            'pk' => [
                'class' => AdminLinkColumn::className(),
                'name' => $admin->getModel()->getPkName(),
                'admin' => $admin,
                'moduleName' => $moduleName,
                'currentOrder' => $this->currentOrder,
                'html' => [
                    'class' => 'td-id',
                    'align' => 'left'
                ],
                'route' => function($record) use ($moduleName, $adminClass) {
                    // 'admin:update' moduleName adminClass model.pk
                    // 'admin:list_nested' moduleName adminClass model.pk

                    $urlManager = Mindy::app()->urlManager;
                    if (is_a($record, TreeModel::className())) {
                        if ($record->isLeaf() === false) {
                            return $urlManager->reverse('admin:list_nested', [
                                'moduleName' => $moduleName,
                                'adminClass' => $adminClass,
                                'pk' => $record->pk
                            ]);
                        } else {
                            return null;
                        }
                    } else {
                        return Mindy::app()->urlManager->reverse('admin:update', [
                            'moduleName' => $moduleName,
                            'adminClass' => $adminClass,
                            'pk' => $record->pk
                        ]);
                    }
                }
            ]
        ], $rawColumns);

        if ($this->sortingColumn) {
            $columns = array_merge([
                'check' => [
                    'class' => CheckColumn::className(),
                    'length' => $this->count()
                ],
                'sorting' => [
                    'class' => SortingColumn::className(),
                ]
            ], $columns);
        } else {
            $columns = array_merge([
                'check' => [
                    'class' => CheckColumn::className(),
                    'length' => $this->count()
                ],
            ], $columns);
        }

        $columns = array_merge($columns, [
            'actions' => [
                'class' => TemplateColumn::className(),
                'template' => "admin/admin/_actions.html",
                'title' => '',
                'html' => [
                    'class' => 'actions'
                ],
                'extra' => [
                    'admin' => $admin,
                    'adminClass' => $adminClass,
                    'moduleName' => $moduleName
                ],
                'virtual' => true
            ]
        ]);

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

    /**
     * @param array $columns
     */
    public function setColumns(array $columns)
    {
        $this->_dynamicColumns = $columns;
    }
}
