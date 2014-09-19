<?php

namespace Modules\Admin\Components;

use Exception;
use Mindy\Base\ApplicationList;
use Mindy\Base\Mindy;
use Mindy\Form\ManagedForm;
use Mindy\Form\ModelForm;
use Mindy\Helper\Text;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Orm\Model;
use Mindy\Orm\QuerySet;
use Mindy\Pagination\Pagination;
use Modules\Admin\AdminModule;
use Modules\Core\CoreModule;
use Modules\Meta\Components\MetaTrait;

abstract class ModelAdmin
{
    use Accessors, Configurator, MetaTrait, ApplicationList;

    public $sortingColumn;

    public $pageSize;

    public $params = [];

    public $indexTemplate = 'admin/admin/_list.html';

    public function getSearchFields()
    {
        return [];
    }

    public function getActions()
    {
        return [
            'remove' => AdminModule::t('Remove')
        ];
    }

    public function getActionsList()
    {
        return ['update', 'delete', 'view'];
    }

    public function getColumns()
    {
        $model = $this->getModel();
        return array_keys($model->getFieldsInit());
    }

    public function getVerboseNameList()
    {
        return Text::mbUcfirst($this->getVerboseNamePlural());
    }

    public function verboseName($column)
    {
        $model = $this->getModel();
        if (array_key_exists($column, $this->verboseNames())) {
            return $this->verboseNames()[$column];
        } elseif ($model->hasField($column)) {
            $field = $model->getField($column);
            if ($field) {
                return $field->getVerboseName($model);
            }
        }
        return $column;
    }

    /**
     * Verbose names for custom columns
     * @return array
     */
    public function verboseNames()
    {
        return [];
    }

    public function orderColumn($column)
    {
        $model = $this->getModel();
        if (array_key_exists($column, $this->orderColumns())) {
            return $this->orderColumns()[$column];
        } elseif ($model->hasField($column)) {
            return $column;
        }
        return null;
    }

    public function orderColumns()
    {
        return [];
    }

    /**
     * @param $column
     * @param $model
     * @return mixed
     */
    public function getColumnValue($column, $model)
    {
        if($column == 'pk') {
            $column = $model->getPkName();
        }
        if ($model->hasAttribute($column)) {
            return $model->getAttribute($column);
        } else {
            if($model->hasField($column)) {
                return $model->__get($column);
            } else {
                $method = 'get' . ucfirst($column);
                if (method_exists($model, $method)) {
                    return $model->{$method}();
                }
            }
        }
        return null;
    }

    /**
     * @param null|int $pageSize
     * @return $this
     */
    public function setPageSize($pageSize = null)
    {
        $this->pageSize = $pageSize;
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params = [])
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @param Model $model
     * @return QuerySet
     */
    public function getQuerySet(Model $model)
    {
        return $model->objects()->getQuerySet();
    }

    /**
     * @return array
     */
    public function index()
    {
        $modelClass = $this->getModel();

        /* @var $model \Mindy\Orm\Model */
        $model = new $modelClass();

        /* @var $qs \Mindy\Orm\QuerySet */
        $qs = $this->getQuerySet($model);

        $this->initBreadcrumbs($model);

        if ($this->sortingColumn) {
            $qs->order([$this->sortingColumn]);
        }

        $currentOrder = null;
        if (isset($this->params['order'])) {
            $column = $this->params['order'];
            $currentOrder = $column;
            if (substr($column, 0, 1) === '-') {
                $column = ltrim($column, '-');
                $sort = "-";
            } else {
                $sort = "";
            }
            $qs = $qs->order([$sort . $column]);
        }

        if (isset($this->params['search'])) {
            $qs = $this->search($qs);
        }

        $pager = new Pagination($qs);
        $models = $pager->paginate();

        return [
            'columns' => $this->getColumns(),
            'models' => $models,
            'pager' => $pager,
            'breadcrumbs' => $this->getBreadcrumbs(),
            'currentOrder' => $currentOrder,
            'sortingColumn' => $this->sortingColumn
        ];
    }

    /**
     * @return string
     */
    public function getCreateForm()
    {
        return ModelForm::className();
    }

    /**
     * @return string
     */
    public function getUpdateForm()
    {
        return $this->getCreateForm();
    }

    /**
     * @return \Mindy\Orm\Model
     */
    abstract public function getModel();

    /**
     * @return \Mindy\Base\Module
     */
    public function getModule()
    {
        return $this->getModel()->getModule();
    }

    public function formatBreadcrumbs(array $menu = [])
    {
        $name = Mindy::app()->getModule($this->getModule()->id)->getName();
        foreach ($menu as $item) {
            if ($item['name'] == $name) {
                return $item['items'];
            }
        }
        return [];
    }

    public function initBreadcrumbs($model)
    {
        $this->addBreadcrumb(
            Text::mbUcfirst($this->getVerboseNamePlural()),
            Mindy::app()->urlManager->reverse('admin.list', [
                'module' => $model->getModuleName(),
                'adminClass' => $this->classNameShort()
            ])
        );
    }

    /**
     * @param $pk
     * @param array $data
     * @param array $files
     * @return array
     */
    public function update($pk, array $data = [], array $files = [])
    {
        $modelClass = $this->getModel();
        $model = $modelClass::objects()->filter(['pk' => $pk])->get();

        if (!is_string($modelClass)) {
            $modelClass = get_class($model);
        }
        $this->initBreadcrumbs($model);

        $formClass = $this->getUpdateForm();
        /* @var $form \Mindy\Form\ModelForm */
        $form = new $formClass([
            'instance' => $model
        ]);

        if ($form instanceof ManagedForm) {
            if (!empty($data) || !empty($files)) {
                $form->setAttributes($data, $files);
            }
        } else {
            if (!empty($data)) {
                $form->setAttributes($data);
            }

            if (!empty($files)) {
                $form->setAttributes($files);
            }
        }

        if (!empty($data) && $form->isValid() && $form->save()) {
            Mindy::app()->flash->success(CoreModule::t('Changes saved'));
            $this->redirectNext($data, $form);
        }

        return [
            'admin' => $this,
            'model' => $model,
            'form' => $form,
            'modelClass' => $modelClass,
            'breadcrumbs' => array_merge($this->getBreadcrumbs(), [
                ['name' => (string)$model]
            ])
        ];
    }

    public function redirectNext($data, $form)
    {
        list($route, $params) = $this->getNextRoute($data, $form);
        $this->redirect($route, $params);
    }

    public function getNextRoute(array $data, $form)
    {
        $model = $form->getInstance();
        if (array_key_exists('save_continue', $data)) {
            return [
                'admin.update', [
                    'module' => $model->getModuleName(),
                    'adminClass' => $this->classNameShort(),
                    'id' => $model->pk
                ]
            ];
        }

        if (array_key_exists('save_create', $data)) {
            return [
                'admin.create', [
                    'module' => $model->getModuleName(),
                    'adminClass' => $this->classNameShort()
                ]
            ];
        }

        $data = [
            'module' => $model->getModuleName(),
            'adminClass' => $this->classNameShort()
        ];
        return ['admin.list', $data];
    }

    public function create(array $data = [], array $files = [])
    {
        $modelClass = $this->getModel();
        if (is_string($modelClass)) {
            $model = new $modelClass;
        } else {
            $model = $modelClass;
            $modelClass = get_class($model);
        }
        $this->initBreadcrumbs($model);

        $formClass = $this->getCreateForm();
        /* @var $form \Mindy\Form\ModelForm */
        $form = new $formClass([
            'instance' => $model
        ]);

        if ($form instanceof ManagedForm) {
            if (!empty($data) || !empty($files)) {
                $form->setAttributes($data, $files);
            }
        } else {
            if (!empty($data)) {
                $form->setAttributes($data);
            }

            if (!empty($files)) {
                $form->setAttributes($files);
            }
        }

        if (!empty($data) && $form->isValid() && $form->save()) {
            Mindy::app()->flash->success(CoreModule::t('Changes saved'));
            $this->redirectNext($data, $form);
        }

        return [
            'admin' => $this,
            'form' => $form,
            'modelClass' => $modelClass,
            'breadcrumbs' => array_merge($this->getBreadcrumbs(), [
                ['name' => AdminModule::t('Create')]
            ])
        ];
    }

    public function remove(array $data = [])
    {
        /* @var $qs \Mindy\Orm\QuerySet */
        $modelClass = $this->getModel();
        foreach ($data as $pk) {
            $model = $modelClass::objects()->get(['pk' => $pk]);
            if ($model) {
                $model->delete();
            }
        }

        $this->redirect('admin.list', ['module' => $this->getModel()->getModuleName(), 'adminClass' => $this->classNameShort()]);
    }

    public function sorting(array $data = [])
    {
        /* @var $qs \Mindy\Orm\QuerySet */
        $modelClass = $this->getModel();
        if(isset($data['models'])) {
            $models = $data['models'];
        } else {
            throw new Exception("Failed to receive models");
        }

        foreach ($models as $position => $pk) {
            $modelClass::objects()->filter(['pk' => $pk])->update([$this->sortingColumn => $position]);
        }
        if (Mindy::app()->request->getIsAjax()) {
            Mindy::app()->controller->json(['success' => true]);
        } else {
            $this->redirect('admin.list', ['module' => $this->getModel()->getModuleName(), 'adminClass' => $this->classNameShort()]);
        }
    }

    /**
     * @param \Mindy\Orm\QuerySet|\Mindy\Orm\Manager $qs
     * @return mixed
     */
    public function search($qs)
    {
        $fields = $this->getSearchFields();
        if (isset($this->params['search']) && !empty($fields)) {
            $filter = [];
            foreach ($fields as $field) {
                $lookup = 'contains';
                $field_name = $field;
                if (strpos($field, '=') === 0) {
                    $field_name = substr($field, 1);
                    $lookup = 'exact';
                }
                $filter[join('__', [$field_name, $lookup])] = $this->params['search'];
            }
            return $qs->filter($filter);
        }
        return $qs;
    }

    public function redirect($route, $data = null)
    {
        $app = Mindy::app();
        $app->request->redirect($app->urlManager->reverse($route, $data));
    }

    public function getNames()
    {
        return ['object', 'objects', 'objects'];
    }

    public function getVerboseName()
    {
        return isset($this->names[0]) ? $this->names[0] : 'object';
    }

    public function getVerboseNamePlural()
    {
        return isset($this->names[1]) ? $this->names[1] : 'object';
    }
}
