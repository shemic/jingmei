<?php
return [
    'name' => '项目内容版本表',
    'order' => 'id desc',
    'struct' => [
        'version_no' => [
            'name' => '版本号',
            'type' => 'bigint',
        ],
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(256)',
        ],
        //内容标识相同
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'run_code' => [
            'name'      => '运行标识',
            'type'      => 'varchar(64)',
        ],
        'workflow_id' => [
            'name'      => '工作流ID',
            'type'      => 'bigint',
            'value'     => 'work/workflow',
        ],
        'type' => [
            'name'      => '类型',
            'type'      => 'varchar(20)',
            'default'   => 'text',
            'value'     => Dever::config('setting')['content'],
        ],
        'search' => [
            'name'      => '筛选项',
            'type'      => 'jsonb',
        ],
        'input' => [
            'name'      => '输入',
            'type'      => 'jsonb',
        ],
        'content' => [
            'name'      => '内容',
            'type'      => 'text',
        ],
        'cate_id' => [
            'name' => '分类',//这里对应的是content_workflow表里的cate_id
            'type' => 'bigint',
            'value' => 'work/cate',
            'default' => 0,
        ],
        'is_fragment' => [
            'name'      => '是否片段',
            'type'      => 'tinyint(1)',
            'default'   => 2,
            'value'     => [
                1 => '是',
                2 => '否',
            ],
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
        'deleted' => [
            'name'      => '删除状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '正常',
                2 => '已删除',
            ],
        ],
        'udate' => [
            'name'      => '更新内容时间',
            'type'      => 'bigint',
        ],
    ],
    'index' => [
        'code' => 'code,cate_id,deleted',
    ],
];