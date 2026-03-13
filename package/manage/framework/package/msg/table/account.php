<?php
return [
    'name' => '消息账户',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name'      => [
            'type'      => 'varchar(60)',
            'name'      => '账户名称',
        ],

        'method'      => [
            'type'      => 'varchar(100)',
            'name'      => '账户功能',
            'default'   => 'Sms',
            'value'    => 'Dever::call("Msg/Lib/Template.method")',
        ],

        'api_account_id'      => [
            'type'      => 'int(11)',
            'name'      => '万接账户',
            'value'     => 'api/account',
        ],

        'test' => [
            'name'      => '测试',
            'type'      => 'tinyint(1)',
            'default'   => 2,
            'value'     => [
                1 => '开启',
                2 => '关闭',
            ],
        ],

        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],

        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '展示',
                2 => '不展示',
            ],
        ],
    ],

    'index' => [
        'method' => 'method',
    ],
];
