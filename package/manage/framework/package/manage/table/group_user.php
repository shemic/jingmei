<?php
return [
    'name' => '集团账户',
    # 定义数据分离
    'partition' => 'Dever::load("Manage/Util")->system()',
    'struct' => [
        'name' => [
            'name'      => '姓名',
            'type'      => 'varchar(32)',
        ],
        'mobile' => [
            'name'      => '手机号',
            'type'      => 'varchar(11)',
        ],
        'password' => [
            'name'      => '密码',
            'type'      => 'varchar(64)',
        ],
        'salt' => [
            'name'      => '密码salt',
            'type'      => 'varchar(32)',
        ],
        'role' => [
            'name'      => '角色',
            'type'      => 'varchar(100)',
            'value'    => 'group_role',
        ],
        'module_data' => [
            'name'      => '模块数据',
            'type'      => 'varchar(2000)',
        ],
        'avatar' => [
            'name'      => '头像',
            'type'      => 'varchar(150)',
        ],
        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '正常',
                2 => '封禁',
            ],
        ],
    ],
    'index' => [
        'mobile' => 'mobile.unique',
    ],
];