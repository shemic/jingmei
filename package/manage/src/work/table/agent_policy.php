<?php
return [
    'name' => '智能体策略',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'tool_id' => [
            'name'      => '工具ID',
            'type'      => 'bigint',
        ],
        'info' => [
            'name'      => '描述',
            'type'      => 'varchar(255)',
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
        'tool_id' => 'tool_id.unique',
    ],
];