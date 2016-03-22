<?php

namespace Modules\Admin;

use Mindy\Base\Mindy;
use Mindy\Base\Module;
use Mindy\Helper\Alias;
use Modules\Admin\Library\AdminLibrary;

class AdminModule extends Module
{
    /**
     * @var array
     */
    protected $dashboards = [];

    public static function preConfigure()
    {
        Mindy::app()->template->addLibrary(new AdminLibrary());
    }

    /**
     * @return array
     */
    public function getDashboardClasses()
    {
        if (empty($this->dashboards)) {
            $path = Alias::get('application.config.dashboard') . '.php';
            if (is_file($path)) {
                $this->dashboards = include_once($path);
            }
        }
        return $this->dashboards;
    }

    public function getMenu()
    {
        return [
            'name' => $this->getName(),
            'items' => [
                [
                    'name' => self::t('Settings'),
                    'url' => 'admin:settings'
                ],
            ]
        ];
    }
}
