<?php
return [
    'name' => '工具配置',
    'order' => 'id asc',
    'struct' => [
        'tool_id' => [
            'name'      => '工具ID',
            'type'      => 'bigint',
            'value'     => 'work/tool',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'name' => [
            'name'      => '参数名',
            'type'      => 'varchar(32)',
        ],
        'value' => [
            'name'      => '参数值',
            'type'      => 'varchar(500)',
        ],
    ],
    'index' => [
        'tool_id' => 'tool_id',
    ],
];