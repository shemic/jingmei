<?php
return [
    'name' => '工具方法',
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
        'tool_id' => [
            'name'      => '工具ID',
            'type'      => 'bigint',
            'value'     => 'work/tool',
        ],
        'info' => [
            'name'      => '描述',
            'type'      => 'text',
        ],
        'input' => [
            'name'      => '输入参数',
            'type'      => 'text',
        ],
        'output' => [
            'name'      => '输出参数',
            'type'      => 'text',
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
        'code' => 'tool_id,code.unique',
    ],
];