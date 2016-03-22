<?php
/**
 * Author: Falaleev Maxim
 * Email: max@studio107.ru
 * Company: http://en.studio107.ru
 * Date: 01/03/16
 * Time: 14:43
 */

namespace Modules\Admin\Helpers;

use Mindy\Base\Mindy;
use Mindy\Helper\Alias;
use Mindy\Utils\RenderTrait;

class AdminHelper
{
    use RenderTrait;

    public static function renderMain($template = 'admin/menu/main.html')
    {
        return self::renderTemplate($template, [
            'apps' => self::fetchMenu()
        ]);
    }

    public static function renderUser($template = 'admin/menu/user.html')
    {
        static $userMenu = [];
        if (empty($userMenu)) {
            $path = Alias::get('application.config.user_menu') . '.php';
            if (is_file($path)) {
                $userMenu = include_once($path);
            }
        }
        return self::renderTemplate($template, [
            'apps' => $userMenu
        ]);
    }

    public static function fetchMenu()
    {
        $modules = Mindy::app()->getModules();
        $user = Mindy::app()->user;

        $array = [];
        foreach ($modules as $name => $config) {
            $adminCode = strtolower($name) . '.admin';
            $name = is_array($config) ? $name : $config;
            $module = Mindy::app()->getModule($name);

            if (method_exists($module, 'getMenu')) {
                $items = $module->getMenu();
                if (!empty($items)) {
                    $items['version'] = $module->getVersion();

                    $resultItems = [];

                    if (!isset($items['items'])) {
                        continue;
                    } else {
                        foreach ($items['items'] as $item) {
                            if (
                                isset($item['adminClass']) &&
                                $user->can($adminCode . '.' . strtolower($item['adminClass'])) ||
                                !isset($item['code']) && $user->is_superuser
                            ) {
                                $resultItems[] = $item;
                            }
                        }
                    }

                    if (empty($resultItems)) {
                        continue;
                    }

                    $items['module'] = $name;
                    $items['items'] = $resultItems;
                    $array[] = $items;
                }
            }
        }

        return $array;
    }
}