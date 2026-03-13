<?php
return [
    'name' => '邮箱设置',
    'struct' => [
        'account_id'        => [
            'type'      => 'int(11)',
            'name'      => '账户',
        ],

        'host'      => [
            'type'      => 'varchar(100)',
            'name'      => '主机地址',
        ],

        'user'      => [
            'type'      => 'varchar(100)',
            'name'      => '邮件账户',
        ],

        'pwd'      => [
            'type'      => 'varchar(100)',
            'name'      => '账户密码',
        ],

        'username'      => [
            'type'      => 'varchar(100)',
            'name'      => '账户名称',
        ],

        'title'      => [
            'type'      => 'varchar(100)',
            'name'      => '邮件默认标题',
        ],
    ],
    'index' => [
        'search' => 'account_id',
    ],
];
