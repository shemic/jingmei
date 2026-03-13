<?php
return [
    'list' => [
        'field'      => [
            'id',
            'name',
            'module' => [
                'show' => 'Dever::call("Manage/Lib/Role.showModule", ["{module}"])'
            ],
            'menu' => [
                'show' => 'Dever::load(\\Manage\\Lib\\Role::class)->showMenu("{menu}")',
            ],
            /*
            'auth' => [
                'show' => 'Dever::load(\\Manage\\Lib\\Role::class)->showFunc("{auth}")',
            ],*/
            'cdate',
        ],
        'button' => [
            '新增' => 'add',
        ],
        'data_button' => [
            '编辑' => 'edit',
        ],
    ],
    'update' => [
        'field'    => [
            'name',
            'auth' => [
                'type' => 'tree',
            ],
        ],
        'start' => 'Manage/Lib/Role.update',
    ],
];