<?php
return [
    'name' => '消息模板',
    'order' => 'id asc',
    'struct' => [
        'name'      => [
            'type'      => 'varchar(60)',
            'name'      => '模板名称',
        ],

        'key'       => [
            'type'      => 'varchar(60)',
            'name'      => '模板标识',
        ],

        'title'      => [
            'type'      => 'varchar(60)',
            'name'      => '通知标题',
        ],

        'type'      => [
            'type'      => 'tinyint(1)',
            'name'      => '通知类型',
            'default'   => '1',
            'value'     => [
                1 => '通知',
                2 => '验证码',
            ],
        ],

        'method'      => [
            'type'      => 'varchar(100)',
            'name'      => '通知方式',
            'default'   => '',
            'value'    => 'Dever::call("Msg/Lib/Template.method")',
        ],

        'content'      => [
            'type'      => 'varchar(2000)',
            'name'      => '通知内容',
        ],

        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '开启',
                2 => '关闭',
            ],
        ],
    ],
    'index' => [
        'search' => '`key`,status',
    ],
];
