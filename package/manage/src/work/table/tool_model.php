<?php
return [
    'name' => '工具与模型关联表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'tool_id' => [
            'name' => '工具',
            'type' => 'bigint',
            'value' => 'work/tool',   
        ],
        'model' => [
            'name'      => '平台模型',
            'type'      => 'varchar(64)',
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
        'search' => 'tool_id,sort',
    ],
];