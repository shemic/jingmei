<?php
return [
    'name' => '智能体与模型关联表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'agent_id' => [
            'name' => '智能体',
            'type' => 'bigint',
            'value' => 'work/agent',   
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
        'search' => 'agent_id,sort',
    ],
];