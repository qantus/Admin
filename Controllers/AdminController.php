<?php

namespace Modules\Admin\Controllers;

use Mindy\Base\Mindy;
use Mindy\Base\Module;
use Mindy\Pagination\Pagination;
use Modules\Admin\Components\ModelAdmin;
use Modules\Core\Components\UserLog;
use Modules\Core\Controllers\BackendController;

class AdminController extends BackendController
{
    public function allowedActions()
    {
        return ['index'];
    }

    public function actionIndex()
    {
        $pager = new Pagination(UserLog::read(100));
        $messages = $pager->paginate();
        echo $this->render('admin/index.html', [
            'messages' => $messages,
            'pager' => $pager
        ]);
    }

    public function actionList($module, $adminClass)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        if ($this->can($module, $adminClass, 'list') === false) {
            $this->error(403);
        }

        $admin = new $className;
        if (is_string($admin->getModel()) && class_exists($admin->getModel()) === false) {
            $this->error(404);
        }

        if ($this->r->http->isPostRequest && isset($_POST['action'])) {
            $action = $_POST['action'];
            unset($_POST['action']);
            $admin->$action($_POST);
        }

        $admin->setParams($_GET);
        $moduleName = $admin->getModel()->getModuleName();

        $context = $admin->index();
        $out = $this->render($admin->indexTemplate, array_merge([
            'actions' => $admin->getActions(),
            'module' => $admin->getModule(),
            'moduleName' => $moduleName,
            'modelClass' => $admin->getModel()->classNameShort(),
            'adminClass' => $adminClass,
            'admin' => $admin,
        ], $context));

        $breadcrumbs = $this->formatBreadcrumbs($context['breadcrumbs'], $admin);
        $this->setBreadcrumbs($breadcrumbs);
        $this->convertBreadcrumbsToTitle($context['breadcrumbs']);

        if ($this->r->isAjax) {
            echo $out;
        } else {
            echo $this->render('admin/admin/list.html', array_merge(['adminClass' => $adminClass], [
                'module' => $admin->getModule(),
                'modelClass' => $admin->getModel(),
                'out' => $out,
                'admin' => $admin
            ]));
        }
    }

    protected function getAdminClassName($module, $adminClass)
    {
        $className = "\\Modules\\" . ucfirst(strtolower($module)) . "\\Admin\\" . $adminClass;
        if (class_exists($className)) {
            return $className;
        }

        return null;
    }

    public function actionInfo($module, $adminClass, $id)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        if ($this->can($module, $adminClass, 'info', ['pk' => $id]) === false) {
            $this->error(403);
        }

        $admin = new $className();
        $moduleName = $admin->getModel()->getModuleName();
        $context = $admin->info($id, $_GET);
        $breadcrumbs = $this->formatBreadcrumbs($context['breadcrumbs'], $admin);
        $this->setBreadcrumbs($breadcrumbs);
        $this->convertBreadcrumbsToTitle($context['breadcrumbs']);

        echo $this->render($admin->infoTemplate, array_merge([
            'actions' => $admin->getActions(),
            'module' => $admin->getModule(),
            'moduleName' => $moduleName,
            'modelClass' => $admin->getModel()->classNameShort(),
            'adminClass' => $adminClass,
            'admin' => $admin,
        ], $context));
    }


    public function actionCreate($module, $adminClass)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        if ($this->can($module, $adminClass, 'create') === false) {
            $this->error(403);
        }

        /** @var \Modules\Admin\Components\ModelAdmin|\Modules\Admin\Components\NestedAdmin $admin */
        $admin = new $className();
        $context = $admin->create($_POST, $_FILES);
        $breadcrumbs = $this->formatBreadcrumbs($context['breadcrumbs'], $admin);
        $this->setBreadcrumbs($breadcrumbs);
        $this->convertBreadcrumbsToTitle($context['breadcrumbs']);

        echo $this->render($admin->createTemplate, array_merge([
            'module' => $module,
            'adminClass' => $adminClass
        ], $context));
    }

    public function actionUpdate($module, $adminClass, $id)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        if ($this->can($module, $adminClass, 'update', ['pk' => $id]) === false) {
            $this->error(403);
        }

        /** @var \Modules\Admin\Components\ModelAdmin|\Modules\Admin\Components\NestedAdmin $admin */
        $admin = new $className();
        $context = $admin->update($id, $_POST, $_FILES);
        $breadcrumbs = $this->formatBreadcrumbs($context['breadcrumbs'], $admin);
        $this->setBreadcrumbs($breadcrumbs);
        $this->convertBreadcrumbsToTitle($context['breadcrumbs']);

        echo $this->render($admin->updateTemplate, array_merge([
            'module' => $module,
            'adminClass' => $adminClass
        ], $context));
    }

    public function actionDelete($module, $adminClass, $id)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        if ($this->can($module, $adminClass, 'delete', ['pk' => $id]) === false) {
            $this->error(403);
        }

        $admin = new $className();
        $admin->delete($id);
        $this->redirect(Mindy::app()->urlManager->reverse('admin.list', [
            'module' => $module,
            'adminClass' => $adminClass
        ]));
    }

    protected function can($module, $adminClass, $actionId, $params = [])
    {
        $code = $module . '.admin.' . strtolower($adminClass) . '.' . $actionId;
        return Mindy::app()->user->can($code, $params);
    }

    protected function menuToBreadcrumbs(Module $module)
    {
        $menu = $module->getMenu();
        $breadcrumbs = [];
        foreach ($menu['items'] as $menu) {
            $breadcrumbs[] = [
                'name' => $menu['name'],
                'url' => Mindy::app()->urlManager->reverse('admin.list', [
                        'module' => $module->getId(),
                        'adminClass' => $menu['adminClass']
                    ])
            ];
        }
        return $breadcrumbs;
    }

    public function formatBreadcrumbs(array $breadcrumbs, ModelAdmin $admin)
    {
        foreach ($breadcrumbs as $i => &$item) {
            if ($i == 1) {
                $item['items'] = $this->menuToBreadcrumbs($admin->getModule());
                break;
            }
            continue;
        }

        return $breadcrumbs;
    }

    protected function convertBreadcrumbsToTitle($breadcrumbs)
    {
        foreach ($breadcrumbs as $bc) {
            $this->addTitle($bc['name']);
        }
    }
}
