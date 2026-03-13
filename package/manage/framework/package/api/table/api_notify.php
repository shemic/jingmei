<?php
return [
    'name' => '接口回调配置',
    'struct' => [
        'api_id' => [
            'name'      => '接口id',
            'type'      => 'int(11)',
        ],

        'success' => [
            'name'      => '应答成功报文',
            'type'      => 'varchar(500)',
        ],

        'error' => [
            'name'      => '应答失败报文',
            'type'      => 'varchar(500)',
        ],

        'sign_id' => [
            'name'      => '签名',
            'type'      => 'int(11)',
        ],

        'sign_arg' => [
            'name'      => '签名参数',
            'type'      => 'varchar(2000)',
        ],
    ],
];