<?php
return [
    'name' => '项目内容分类表',
    'order' => 'sort desc,id desc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'uid' => [
            'name' => '用户ID',
            'type' => 'bigint',
        ],
        'project_id' => [
            'name'      => '项目ID',
            'type'      => 'bigint',
            'value'     => 'user/project',
        ],
        'app_id' => [
            'name'      => '应用ID',
            'type'      => 'bigint',
            'value'     => 'service/app',
        ],
        'workflow_input_id' => [
            'name'      => '工作流选项ID',
            'type'      => 'bigint',
            'value'     => 'work/workflow_input',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
        'status' => [
            'name'      => '删除状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '正常',
                2 => '已删除',
            ],
        ],
    ],
    'index' => [
        'search' => 'uid,project_id,app_id,workflow_input_id,sort',
    ],
];