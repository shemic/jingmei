<?php
return [
    'name' => '验证码表',
    'struct' => [
        'mobile' => [
            'name'      => '手机号',
            'type'      => 'bigint',
        ],
        'code' => [
            'name'      => '验证码',
            'type'      => 'varchar(6)',
        ],
        'scene' => [
            'name'      => '场景',
            'type'      => 'varchar(32)',
        ],
        'used_at' => [
            'name'      => '过期时间',
            'type'      => 'bigint',
        ],
        'expires_at' => [
            'name'      => '过期时间',
            'type'      => 'bigint',
        ],
        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '正常',
                2 => '删除',
            ],
        ],
    ],
    'index' => [
        'mobile' => 'mobile,scene,status',
        'search' => 'mobile,cdate',
    ],
];