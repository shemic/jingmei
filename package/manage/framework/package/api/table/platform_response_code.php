<?php
return [
    'name' => '响应状态码列表',
    'struct' => [
        'platform_id' => [
            'name'      => '平台id',
            'type'      => 'int(11)',
        ],

        'key' => [
            'name'      => '状态码名',
            'type'      => 'varchar(150)',
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
            ],
        ],

        'msg' => [
            'name'      => '返回信息',
            'type'      => 'varchar(150)',
        ],
    ],
];