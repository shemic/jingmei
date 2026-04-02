<?php
return [
    'name' => '模型管理',
    'order' => 'sort asc,id asc',
    'struct' => [
        'system_id' => [
            'name' => '系统',
            'type' => 'bigint',
            'value' => 'shenzhu/system',   
        ],
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'model' => [
            'name'      => '模型',
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
        'search' => 'system_id,sort',
    ],
];