<?php
return [
    'menu' => [
        'connect' => [
            'name' => '万接',
            'icon' => 'contacts-line',
            'sort' => '900',
            'module' => 'platform',
            'app' => 'api',
        ],

        'api_manage' => [
            'parent' => 'connect',
            'name' => '核心设置',
            'icon' => 'compasses-line',
            'sort' => '1',
        ],

        'platform_info' => [
            'parent'    => 'api_manage',
            'name'      => '平台管理',
            'icon'      => 'tools-line',
            'sort'      => '3',
        ],

        'format' => [
            'parent'    => 'api_manage',
            'name'      => '参数格式转换',
            'icon'      => 'tools-line',
            'sort'      => '4',
            'show'      => 3,
        ],

        'api' => [
            'parent'    => 'api_manage',
            'name'      => '平台接口管理',
            'icon'      => 'tools-line',
            'sort'      => '4',
            'show'      => 3,
        ],

        'platform_ssl' => [
            'parent'    => 'api_manage',
            'name'      => '平台加密管理',
            'icon'      => 'tools-line',
            'sort'      => '5',
            'show'      => 3,
        ],

        'platform_sign' => [
            'parent'    => 'api_manage',
            'name'      => '平台签名管理',
            'icon'      => 'tools-line',
            'sort'      => '6',
            'show'      => 3,
        ],

        'app' => [
            'parent'    => 'api_manage',
            'name'      => '应用管理',
            'icon'      => 'file-list-2-line',
            'sort'      => '7',
        ],

        'app_func' => [
            'parent'    => 'api_manage',
            'name'      => '应用功能管理',
            'icon'      => 'file-list-2-line',
            'sort'      => '8',
            'show'      => 3,
        ],

        'account' => [
            'parent'    => 'api_manage',
            'name'      => '账户管理',
            'icon'      => 'contacts-line',
            'sort'      => '9',
        ],

        'account_cert' => [
            'parent'    => 'api_manage',
            'name'      => '账户证书',
            'icon'      => 'contacts-line',
            'sort'      => '10',
            'show'      => 3,
        ],
    ],
];