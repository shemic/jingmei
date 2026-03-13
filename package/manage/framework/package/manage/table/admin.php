<?php
return [
    'name' => '平台管理员',
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
            # 该字段的值 radio、select、checkbox有效，定义从哪个表获取数据
            'value'    => 'Manage/role',
            /*
            'value'    => 'Dever::load("Manage/Lib/Role")->get()',//调用某个类的方法
            'value'    => [1 => 'a', 2 => 'b'],//直接设置可选项
            */
        ],
        'module_data' => [
            'name'      => '系统模块',
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