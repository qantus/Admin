<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 03/07/14.07.2014 18:50
 */

namespace Modules\Admin\Controllers;


use Mindy\Base\Mindy;
use Modules\Core\Controllers\CoreController;
use Modules\User\Forms\UserLoginForm;
use Modules\User\UserModule;

class AuthController extends CoreController
{
    public $defaultAction = 'login';

    public $defaultRedirectUrl = '/';

    public function allowedActions()
    {
        return ['login', 'logout'];
    }

    public function init()
    {
        parent::init();

        $this->defaultRedirectUrl = Mindy::app()->urlManager->reverse('admin.index');

        if (isset($_GET['redirectUrl'])) {
            Mindy::app()->auth->setReturnUrl($_GET['redirectUrl']);
        }
    }

    public function actionLogin()
    {
        $form = new UserLoginForm();

        if (!empty($_POST)) {
            $form->setAttributes($_POST);

            if ($form->isValid() && $form->login()) {
                if (Mindy::app()->request->isAjaxRequest) {
                    $this->json(array(
                        'status' => 'success',
                        'title' => UserModule::t('You have successfully logged in to the site')
                    ));
                } else {
                    $this->redirect('admin.index');
                }
            }
        }

        $data = [
            'form' => $form
        ];

        if (Mindy::app()->request->isAjaxRequest) {
            $this->json([
                'content' => $this->render('admin/_login.html', $data)
            ]);
        } else {
            echo $this->render('admin/login.html', $data);
        }
    }

    /**
     * Logout the current user and redirect to returnLogoutUrl.
     */
    public function actionLogout()
    {
        $auth = Mindy::app()->auth;
        if ($auth->isGuest) {
            $this->redirect(Mindy::app()->homeUrl);
        }

        $auth->logout();
        $this->redirect('admin.login');
    }
}
