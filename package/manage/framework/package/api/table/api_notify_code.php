<?php
return [
    'name' => '回调参数配置',
    'order' => 'id asc',
    'struct' => [
        'api_id' => [
            'name'      => '接口id',
            'type'      => 'int(11)',
        ],

        'key' => [
            'name'      => '状态码标识',
            'type'      => 'varchar(32)',
        ],

        'value' => [
            'name'      => '状态码值',
            'type'      => 'varchar(150)',
        ],

        'type' => [
            'name'      => '状态码类型',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '成功',
                2 => '失败',
                3 => '无状态',
            ],
        ],
    ],
];