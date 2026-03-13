<?php
return [
    'name' => '项目运行表',
    'order' => 'id desc',
    'struct' => [
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'project_code' => [
            'name'      => '项目',
            'type'      => 'varchar(64)',
        ],
        'content_version_id' => [
            'name'      => '内容版本ID',
            'type' => 'bigint',
        ],
        'type' => [
            'name'      => '类型',
            'type'      => 'varchar(32)',
            'default'   => 'app',
            'value'     => [
                'app' => '工作流',
                'agent' => '智能体',
            ],
        ],
        'type_code' => [
            'name'      => '类型标识',
            'type'      => 'varchar(64)',
        ],
        'input_type' => [
            'name'      => '输入类型',
            'type'      => 'varchar(32)',
            'default'   => 'text',
            'value'     => [
                'text' => '文本',
                'file' => '文件',
            ],
        ],
        'input' => [
            'name'      => '输入',
            'type'      => 'jsonb',
        ],
        'result' => [
            'name'      => '结果',
            'type'      => 'jsonb',
        ],
        'current_node' => [
            'name'      => '当前节点',
            'type'      => 'varchar(64)',
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
        'code' => 'code.unique',
        'project_code' => 'project_code,type,type_code',
        'content_version_id' => 'content_version_id',
        'status' => 'status',
    ],
];