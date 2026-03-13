<?php
return [
    'name' => '账户管理',
    'struct' => [
        'name'      => [
            'type'      => 'varchar(32)',
            'name'      => '账户名称',
        ],

        'key'       => [
            'type'      => 'varchar(32)',
            'name'      => '账户标识',
        ],

        'desc' => [
            'name'      => '账户介绍',
            'type'      => 'text',
        ],

        'app_platform'       => [
            'type'      => 'varchar(30)',
            'name'      => '应用与平台',
        ],

        'app_id'       => [
            'type'      => 'int(11)',
            'name'      => '应用',
        ],

        'platform_id'       => [
            'type'      => 'int(11)',
            'name'      => '平台',
        ],

        'sync' => [
            'name'      => '自动同步',
            'type'      => 'tinyint(1)',
            'default'   => 2,
            'value'     => [
                1 => '开启',
                2 => '关闭',
            ],
        ],

        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '上架',
                2 => '下架',
            ],
        ],
    ],
    'index' => [
        'search' => '`key`.unique',
    ],
];
