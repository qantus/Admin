<?php

return [
    '/' => [
        'name' => 'index',
        'callback' => '\Modules\Admin\Controllers\AdminController:index'
    ],
    '/auth/login' => [
        'name' => 'login',
        'callback' => '\Modules\Admin\Controllers\AuthController:login'
    ],
    '/auth/logout' => [
        'name' => 'logout',
        'callback' => '\Modules\Admin\Controllers\AuthController:logout'
    ],
    '/auth/recover' => [
        'name' => 'recover',
        'callback' => '\Modules\Admin\Controllers\AuthController:recover'
    ],
    '/list/{module:\w+}/{adminClass:\w+}' => [
        'name' => 'list',
        'callback' => '\Modules\Admin\Controllers\AdminController:list'
    ],
    '/list/{module:\w+}/{adminClass:\w+}/{id:\d+}' => [
        'name' => 'list_nested',
        'callback' => '\Modules\Admin\Controllers\AdminController:list'
    ],
    '/create/{module:\w+}/{adminClass:\w+}' => [
        'name' => 'create',
        'callback' => '\Modules\Admin\Controllers\AdminController:create'
    ],
    '/update/{module:\w+}/{adminClass:\w+}/{id:\d+}' => [
        'name' => 'update',
        'callback' => '\Modules\Admin\Controllers\AdminController:update'
    ],
    '/delete/{module:\w+}/{adminClass:\w+}/{id:\d+}' => [
        'name' => 'delete',
        'callback' => '\Modules\Admin\Controllers\AdminController:delete'
    ],
    '/info/{module:\w+}/{adminClass:\w+}/{id:\d+}' => [
        'name' => 'info',
        'callback' => '\Modules\Admin\Controllers\AdminController:info'
    ],
];
