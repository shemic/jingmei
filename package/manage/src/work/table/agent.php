<?php
return [
    'name' => '智能体表',
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
        'cate_id' => [
            'name'      => '分类',
            'type'      => 'bigint',
            'value'     => 'work/cate',
        ],
        'runtime' => [
            'name'      => '运行时',
            'type'      => 'varchar(16)',
            'default'   => 'python',
        ],
        'entry_type' => [
            'name'      => '入口类型',
            'type'      => 'varchar(32)',
            'default'   => 'activity',
        ],
        'entry_name' => [
            'name'      => '入口名称',
            'type'      => 'varchar(128)',
            'default'   => 'ExecuteAgent',
        ],
        'task_queue' => [
            'name'      => '任务队列',
            'type'      => 'varchar(64)',
            'default'   => 'LLM_TASK_QUEUE',
        ],
        'model' => [
            'name'      => '平台模型',
            'type'      => 'varchar(64)',
        ],
        'system_prompt' => [
            'name'      => '系统提示词',
            'type'      => 'text',
        ],
        'tool_list' => [
            'name'      => '工具列表',
            'type'      => 'text',
            'value'     => 'work/tool',
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
        'code' => 'code.unique',
        'search' => 'cate_id,sort',
    ],
];