<?php

return [
    'name' => '账户证书设置',
    'order' => 'edate desc, id asc',
    'struct' => [
        'account_id'        => [
            'type'      => 'int(11)',
            'name'      => '账户',
        ],

        'platform_cert_id' => [
            'name'      => '证书名',
            'type'      => 'int(11)',
        ],

        'number' => [
            'name'      => '证书序列号',
            'type'      => 'varchar(100)',
        ],

        'public' => [
            'name'      => '公钥内容',
            'type'      => 'varchar(3000)',
        ],

        'private' => [
            'name'      => '私钥内容',
            'type'      => 'varchar(3000)',
        ],

        'edate' => [
            'name'      => '过期时间',
            'type'      => 'int(11)',
        ],
    ],
];
