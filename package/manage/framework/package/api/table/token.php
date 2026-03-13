<?php
return [
    'name' => 'token表',
    'struct' => [
        'appid'        => [
            'type'      => 'varchar(100)',
            'name'      => 'appid',
        ],

        'token'        => [
            'type'      => 'varchar(1000)',
            'name'      => 'token值',
        ],

        'edate'        => [
            'type'      => 'int(11)',
            'name'      => '过期时间',
        ],
    ],

    'index' => [
        'search' => 'appid',
    ],
];
