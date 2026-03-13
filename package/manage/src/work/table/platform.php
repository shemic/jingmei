<?php
return [
    'name' => '平台表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'host' => [
            'name'      => '主机域名',
            'type'      => 'varchar(256)',
        ],
        'api_key' => [
            'name'      => 'apiKey',
            'type'      => 'varchar(256)',
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
                1 => '正常',
                2 => '封禁',
            ],
        ],
    ],
    'index' => [
        //'code' => 'code.unique',
        'search' => 'sort',
    ],
];