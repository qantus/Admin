<?php

namespace Modules\Admin\Controllers;

use Mindy\Base\Mindy;
use Modules\Core\Controllers\BackendController;

class SettingsController extends BackendController
{
    public $defaultAction = 'setting';

    public $formName = 'setting-form';

    protected function getSettingsModels()
    {
        $modulesPath = Mindy::getPathOfAlias('application.modules');
        $modules = Mindy::app()->modules;
        $modelsPath = [];
        foreach($modules as $name => $params) {
            $tmpPath = $modulesPath . '/' . $name . '/models/';
            $paths = glob($tmpPath . '*Settings.php');

            if(!array_key_exists($name, $modelsPath)) {
                $modelsPath[$name] = [];
            }

            $modelsPath[$name] = array_merge($modelsPath[$name], array_map(function($path) use ($tmpPath) {
                return str_replace('.php', '', str_replace($tmpPath, '', $path));
            }, $paths));
        }
        return MMap::recursiveClear($modelsPath);
    }

    protected function reformatModels(array $moduleModels)
    {
        $models = [];
        foreach($moduleModels as $tmpModels) {
            foreach($tmpModels as $model) {
                $modelSetting = new $model();
                $models[$model] = array(
                    'model' => $modelSetting,
                    'form' => $this->getForm($modelSetting)
                );
            }
        }

        return $models;
    }

    public function actionIndex()
    {
        $models = $this->reformatModels($this->getSettingsModels());

        $this->ajaxValidation($models, $this->formName);

        $success = false;
        foreach($models as $modelRaw => $modelAndForm) {
            $model = $modelAndForm['model'];
            if (isset($_POST[$modelRaw])) {
                $model->attributes = $_POST[$modelRaw];
                $success = $model->validate() && $model->save();
            }
        }

        if($success) {
            Mindy::app()->user->setFlash(WFlashMessages::SUCCESS, CoreModule::t('Settings saved successfully.', [], 'settings'));
        } else {
            Mindy::app()->user->setFlash(WFlashMessages::ERROR, CoreModule::t('Settings save fail.', [], 'settings'));
        }

        echo $this->render('admin/settings.twig', array(
            'models' => $models,
        ));
    }

    public function render($view, array $data = [])
    {
        $data['apps'] = $this->getApplications();
        return parent::render($view, $data);
    }

    public function getFormName($model)
    {
        return strtolower(get_class($model)) . "-form";
    }
}
