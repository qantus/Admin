<?php
/**
 * Author: Falaleev Maxim
 * Email: max@studio107.ru
 * Company: http://en.studio107.ru
 * Date: 01/03/16
 * Time: 14:37
 */

namespace Modules\Admin\Library;

use Mindy\Template\Library;
use Modules\Admin\Helpers\AdminHelper;

class AdminLibrary extends Library
{
    /**
     * @return array
     */
    public function getHelpers()
    {
        return [
            'get_admin_user_menu' => [AdminHelper::class, 'renderUser'],
            'get_admin_main_menu' => [AdminHelper::class, 'renderMain'],
        ];
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return [];
    }
}