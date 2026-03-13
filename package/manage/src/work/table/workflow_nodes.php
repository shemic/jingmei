<?php
return [
    'name' => '工作流节点表',
    'order' => 'id asc',
    'struct' => [
        'workflow_id'       => [
            'type'      => 'bigint',
            'name'      => '工作流ID',
            'value'     => 'work/workflow',
        ],
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
            'default'   => '默认节点',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
            'default'   => 'main',
        ],
        'type' => [
            'name'      => '类型',
            'type'      => 'varchar(20)',
            'default'   => 'agent',
            'value'     => [
                'agent' => '智能体',
                'tool' => '工具',
                //'workflow' => '工作流',
                //'parallel' => '并行处理',
                //'review' => '用户反馈',
                'shenzhu' => 'AI助理',
            ],
        ],
        'type_value' => [
            'name'      => '类型值',
            'type'      => 'varchar(500)',
        ],
        'next' => [
            'name'      => '下一节点',
            'type'      => 'varchar(64)',
            'default'   => 'end',
        ],
        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '启用',
                2 => '关闭',
            ],
        ],
    ],
    'index' => [
        'workflow_id' => 'workflow_id',
    ],
];