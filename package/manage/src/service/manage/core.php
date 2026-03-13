<?php
return [
    'menu' => [
        /*
        'service' => [
            'name' => '业务',
            'icon' => 'file-service-line',
            'sort' => '40',
            'module' => 'platform',
        ],*/

        # 业务管理
        'service_manage' => [
            'parent'    => 'work',
            'name'      => '业务管理',
            'icon'      => 'e-bike-line',
            'sort'      => '1',
        ],

        'info' => [
            'parent'    => 'service_manage',
            'name'      => '业务列表',
            'icon'      => 'service-line',
            'sort'      => '1',
        ],

        'app' => [
            'parent'    => 'service_manage',
            'name'      => '业务应用设置',
            'sort'      => '100',
            'show'      => '3',
        ],

        'app_workflow' => [
            'parent'    => 'service_manage',
            'name'      => '应用工作流设置',
            'sort'      => '100',
            'show'      => '3',
        ],
    ],
];