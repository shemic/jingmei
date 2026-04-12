<?php
return [
    'name' => '模型参数定义表',
    'struct' => [
        'model_id' => [
            'name' => '模型',
            'type' => 'bigint',
            'value' => 'work/model',   
        ],
        'name' => [
            'name'      => '原字段',
            'type'      => 'varchar(256)',
        ],
        'value' => [
            'name'      => '模型字段',
            'type'      => 'varchar(256)',
        ],
        'nid' => [
            'name'      => '节点ID',
            'type'      => 'varchar(256)',
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
        'search' => 'model_id,status',
    ],
];