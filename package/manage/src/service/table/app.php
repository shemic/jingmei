<?php
return [
    'name' => '应用表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'icon' => [
            'name'      => '图标',
            'type'      => 'varchar(32)',
        ],
        'service_id' => [
            'name'      => '业务',
            'type'      => 'bigint',
            'value'     => 'service/info',
        ],
        'mode' => [
            'name'      => '模式',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '内容模式',
                2 => '聊天模式',
                3 => '写作模式',
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
                1 => '正常',
                2 => '封禁',
            ],
        ],
    ],
    'index' => [
        'code' => 'code.unique',
        'search' => 'service_id,sort',
    ],
];