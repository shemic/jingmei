<?php
return [
    'name' => '帮助分类表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'info' => [
            'name'      => '描述',
            'type'      => 'varchar(200)',
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
];