<?php
return [
    'name' => '验证码',
    'partition' => 'Dever::call("Manage/Lib/Util.system")',
    'order' => 'cdate desc',
    'struct' => [
        'template_id'        => [
            'type'      => 'int(11)',
            'name'      => '模板',
        ],

        'account'      => [
            'type'      => 'varchar(50)',
            'name'      => '账户',
        ],

        'code'      => [
            'type'      => 'varchar(50)',
            'name'      => '验证码',
        ],

        'day'      => [
            'type'      => 'int(11)',
            'name'      => '发送的时间',
            'default'   => '5',
        ],

        'status'      => [
            'type'      => 'tinyint(1)',
            'name'      => '状态',
            'default'   => '1',
            'value'     => [
                1 => '未使用',
                2 => '已使用',
            ]
        ],

        'record'        => [
            'type'      => 'text(255)',
            'name'      => '返回记录',
        ],
    ],
    'index' => [
        'search' => 'template_id,account',
    ],
];
