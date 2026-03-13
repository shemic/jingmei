<?php
return [
    'name' => '项目运行节点表',
    'order' => 'id desc',
    'struct' => [
        'project_code' => [
            'name'      => '项目',
            'type'      => 'varchar(64)',
        ],
        'run_code' => [
            'name'      => '运行标识',
            'type'      => 'varchar(64)',
        ],
        'node_id' => [
            'name'      => '节点ID',
            'type'      => 'varchar(64)',
        ],
        'output' => [
            'name'      => '结果',
            'type'      => 'jsonb',
        ],
        'start_date' => [
            'name'      => '开始时间',
            'type'      => 'bigint',
        ],
        'end_date' => [
            'name'      => '结束时间',
            'type'      => 'bigint',
        ],
        'status' => [
            'name'      => '状态',
            'type'      => 'varchar(32)',
            'default'   => 'running',
            'value'     => [
                'running' => '待处理',
                'waiting' => '等待中',
                'succeeded' => '成功',
                'failed' => '失败',
            ],
        ],
    ],
    'index' => [
        'project_code' => 'project_code',
        'run_code' => 'run_code,status',
        'node_id' => 'node_id',
    ],
];